// Alpine factory for the time-tracker Livewire component.
//
// Owns:
//   - 1Hz ticker that recomputes `elapsedSeconds` from `active`.
//   - Updates `active` and `total` from the JSON returned by
//     start/pause/resume/stop. We listen on the standard
//     `livewire:update` event by reaching into Livewire's internal
//     component state, but a simpler approach is used here: the
//     component's wire:click handlers update server state, the
//     response replaces the Alpine `active`/`total` via a custom
//     window event 'time-tracker-state' dispatched from each action.
//
// We also use the registry pattern to avoid double-init (Livewire 3
// fires Alpine `init()` twice during hydration).
//
// Public API (called from time-tracker.blade.php via x-data="timeTracker({...})"):
//   init()                       — register listeners and start ticker
//   active, total                — mirrors of Livewire public props
//   elapsedSeconds               — derived, updated by the ticker
//   activeLabel                  — "Started 10:23" or "Paused at 0:14:32"
//   formatTotal(s)               — "1h 23m" or "23m" or "0m"
//   formatElapsed(s)             — "1h 23m 04s" or "23m 04s" or "0:04"

window.timeTracker = function (initial) {
    return {
        active: initial.active || null,
        total: Number(initial.total || 0),
        elapsedSeconds: 0,
        activeLabel: '',
        _ticker: null,
        _initialized: false,
        _listeners: [],

        init() {
            // Livewire 3 / Alpine sometimes fires init() twice. Bail early
            // on the second call; the first call's listeners are still
            // installed.
            if (this._initialized) return;
            this._initialized = true;

            // Compute initial elapsed from the server-provided active
            // entry, then start the 1Hz ticker.
            this.recompute();
            this._ticker = setInterval(() => this.recompute(), 1000);

            // Listen for state updates pushed from Livewire actions.
            // The template wires @time-tracker-state.window="onState($event.detail)",
            // but we also keep a window-level listener for any external
            // dispatch (e.g. tests, or other components).
            const onState = (ev) => this.onState(ev.detail || {});
            window.addEventListener('time-tracker-state', onState);
            this._listeners.push(['time-tracker-state', onState]);
        },

        onState(detail) {
            if (typeof detail !== 'object' || detail === null) return;
            if (typeof detail.active !== 'undefined') this.active = detail.active;
            if (typeof detail.total === 'number')    this.total  = detail.total;
            this.recompute();
        },

        destroy() {
            if (this._ticker) {
                clearInterval(this._ticker);
                this._ticker = null;
            }
            for (const [type, fn] of this._listeners) {
                window.removeEventListener(type, fn);
            }
            this._listeners = [];
            this._initialized = false;
        },

        recompute() {
            if (! this.active) {
                this.elapsedSeconds = 0;
                this.activeLabel = '';
                return;
            }
            const base = Number(this.active.elapsed_seconds || 0);
            const status = this.active.status;
            if (status === 'open') {
                // We don't know the server's "now", but we do know the
                // resumed_at/start offset. Add wall-clock seconds since
                // the page loaded — that's accurate enough for a
                // human-readable display, and the server is still the
                // source of truth for totals.
                const startedWall = this._activeStartedWall();
                if (startedWall) {
                    this.elapsedSeconds = base + Math.floor((Date.now() - startedWall) / 1000);
                } else {
                    this.elapsedSeconds = base;
                }
                this.activeLabel = 'Started ' + this._formatClock(startedWall);
            } else if (status === 'paused') {
                this.elapsedSeconds = base;
                this.activeLabel = 'Paused at ' + this.formatElapsed(base);
            } else {
                this.elapsedSeconds = base;
                this.activeLabel = '';
            }
        },

        _activeStartedWall() {
            // The server returns elapsed_seconds (a count) but not
            // resumed_at as a timestamp. We approximate by treating
            // the active object's "started_at" server string as
            // the offset anchor and only counting wall-clock time
            // since the page was loaded, by storing a local anchor
            // on first recompute.
            if (! this._anchor) {
                this._anchor = Date.now() - (Number(this.active.elapsed_seconds || 0) * 1000);
            }
            return this._anchor;
        },

        _formatClock(ms) {
            if (! ms) return '';
            const d = new Date(ms);
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        formatTotal(s) {
            s = Number(s || 0);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            if (h > 0) return `${h}h ${m}m`;
            return `${m}m`;
        },

        formatElapsed(s) {
            s = Number(s || 0);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            if (h > 0) return `${h}h ${String(m).padStart(2,'0')}m ${String(sec).padStart(2,'0')}s`;
            return `${m}m ${String(sec).padStart(2,'0')}s`;
        },
    };
};
