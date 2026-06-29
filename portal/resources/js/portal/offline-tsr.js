// =========================================================================
//  TSR offline queue (self-contained, IndexedDB-or-localStorage)
//  -------------------------------------------------------------
//  Stores TSR payloads in the browser while the TSP is offline so the
//  Save button never silently drops the report.
//
//  Storage backend is picked at runtime:
//    1. If the browser has IndexedDB we use it directly (no extra
//       dependency — we just wrap the raw IDB API).
//    2. Otherwise we fall back to localStorage so the form still
//       queues while the user is offline on a small-screen device.
//
//  Drain triggers:
//    * the `online` window event (immediate)
//    * a manual call from the Alpine controller (the "Sync to Monday"
//      button on the sticky bar)
//    * an interval poll every 60s
//
//  The drainer always POSTs to the same endpoint the form would have
//  used when online: /tsp/tickets/{ticket}/tsr. The server returns
//  200 with the new sync_state, which the form then surfaces via
//  /tsp/tickets/{ticket}/tsr/status (polled separately by the form).
// =========================================================================

const STORE = 'pending';
const POLL_MS = 60_000;

// -----------------------------------------------------------------------
//  Tiny IndexedDB wrapper. Same shape as Dexie's `db()` so swapping
//  back to Dexie later is a one-line change.
// -----------------------------------------------------------------------
function openIdb() {
    return new Promise((resolve, reject) => {
        if (typeof indexedDB === 'undefined') {
            reject(new Error('IndexedDB unavailable'));
            return;
        }
        const req = indexedDB.open('portal-tsr', 1);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (! db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'local_id' });
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    });
}

function idbPut(db, value) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).put(value);
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}
function idbGetAll(db) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readonly');
        const req = tx.objectStore(STORE).getAll();
        req.onsuccess = () => resolve(req.result || []);
        req.onerror   = () => reject(req.error);
    });
}
function idbDelete(db, key) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).delete(key);
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}
function idbCount(db) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readonly');
        const req = tx.objectStore(STORE).count();
        req.onsuccess = () => resolve(req.result || 0);
        req.onerror   = () => reject(req.error);
    });
}

// -----------------------------------------------------------------------
//  localStorage fallback (older browsers, very small quota).
// -----------------------------------------------------------------------
const LS_KEY = 'portal-tsr-pending';
function lsGetAll() {
    try { return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); }
    catch (e) { return []; }
}
function lsSetAll(items) {
    try { localStorage.setItem(LS_KEY, JSON.stringify(items)); }
    catch (e) { /* quota or disabled — silently drop */ }
}
function lsCount() { return lsGetAll().length; }

// -----------------------------------------------------------------------
//  Backend selection. We try IDB once and remember the result.
// -----------------------------------------------------------------------
let _backend = null; // 'idb' | 'ls'
let _db      = null;

async function backend() {
    if (_backend === 'idb' && _db) return { kind: 'idb', db: _db };
    if (_backend === 'ls')           return { kind: 'ls' };
    try {
        _db = await openIdb();
        _backend = 'idb';
        return { kind: 'idb', db: _db };
    } catch (e) {
        _backend = 'ls';
        return { kind: 'ls' };
    }
}

async function put(value) {
    const b = await backend();
    if (b.kind === 'idb') return idbPut(b.db, value);
    const items = lsGetAll();
    const i = items.findIndex(it => it.local_id === value.local_id);
    if (i >= 0) items[i] = value; else items.push(value);
    lsSetAll(items);
}
async function getAll() {
    const b = await backend();
    if (b.kind === 'idb') return idbGetAll(b.db);
    return lsGetAll();
}
async function remove(key) {
    const b = await backend();
    if (b.kind === 'idb') return idbDelete(b.db, key);
    lsSetAll(lsGetAll().filter(it => it.local_id !== key));
}
export async function queueCount() {
    const b = await backend();
    return b.kind === 'idb' ? idbCount(b.db) : lsCount();
}

// -----------------------------------------------------------------------
//  Network
// -----------------------------------------------------------------------
function syncUrlFor(ticket) {
    // The form sets window.__tsrSyncUrl via the route helper. We fall
    // back to building the URL ourselves if the helper wasn't set yet
    // (e.g. when the module is loaded outside the form).
    if (typeof window !== 'undefined' && window.__tsrSyncUrl) {
        return window.__tsrSyncUrl;
    }
    return '/tsp/tickets/' + encodeURIComponent(ticket) + '/tsr/sync';
}

async function postOnce(payload) {
    return fetch('/tsp/tickets/' + encodeURIComponent(payload.ticket_number) + '/tsr', {
        method:  'POST',
        headers: {
            'Content-Type':   'application/json',
            'Accept':         'application/json',
            'X-TSR-Local-Id': payload.local_id,
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
    });
}

// -----------------------------------------------------------------------
//  Public API
// -----------------------------------------------------------------------

/**
 * Submit a TSR. If we're online we try the server first; on 5xx
 * or network error we queue. If we're offline we queue immediately.
 * Returns the path so the form can show the right success message.
 */
export async function submitTsr(payload) {
    if (typeof navigator !== 'undefined' && navigator.onLine) {
        try {
            const r = await postOnce(payload);
            if (r.ok) return { path: 'live', localId: payload.local_id };
            if (r.status >= 500) throw new Error('server 5xx, queueing');
        } catch (e) {
            // fall through to queue
        }
    }
    await put({ ...payload, queued_at: Date.now() });
    return { path: 'queued', localId: payload.local_id };
}

async function drain() {
    if (typeof navigator !== 'undefined' && ! navigator.onLine) {
        return { drained: 0, skipped: 'offline' };
    }
    const items = await getAll();
    let drained = 0;
    for (const it of items) {
        try {
            const r = await postOnce(it);
            if (r.ok) {
                await remove(it.local_id);
                drained++;
                if (typeof window !== 'undefined') {
                    window.dispatchEvent(new CustomEvent('tsr.synced', {
                        detail: it.local_id,
                    }));
                }
            } else if (r.status >= 400 && r.status < 500) {
                // Validation error — drop it; the form has to be fixed
                // by the user before we can ever post it.
                await remove(it.local_id);
                if (typeof window !== 'undefined') {
                    window.dispatchEvent(new CustomEvent('tsr.sync_failed', {
                        detail: { localId: it.local_id, reason: 'validation' },
                    }));
                }
            }
        } catch (e) {
            // network blip — leave it queued
        }
    }

    // The server-side drainer handles DB rows. We only invoke it
    // when the *server* has its own pending rows (e.g. the form
    // submit went through Livewire and the row was written but
    // Monday's API was down at the time).
    if (drained === 0 && items.length === 0) {
        const ticket = (typeof window !== 'undefined' && window.__tsrTicketNumber) || '';
        if (ticket) {
            try {
                await fetch(syncUrlFor(ticket), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
            } catch (e) { /* silent */ }
        }
    }

    return { drained };
}

// -----------------------------------------------------------------------
//  Wiring
// -----------------------------------------------------------------------

if (typeof window !== 'undefined') {
    window.addEventListener('online',  () => { drain(); });
    window.addEventListener('offline', () => {
        window.dispatchEvent(new CustomEvent('tsr.offline'));
    });
    setInterval(() => drain(), POLL_MS);
    document.addEventListener('DOMContentLoaded', () => drain());

    // Expose a manual trigger so the form's "Sync to Monday" button
    // can call us without us re-implementing the fetch.
    window.__tsrOfflineDrain = drain;
}
