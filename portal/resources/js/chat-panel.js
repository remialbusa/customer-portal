/*
 * Alpine.js component for the chat panel. Subscribes the panel to
 * its Pusher private channel and appends incoming messages to the
 * scrollable log.
 *
 *   <div x-data="chatPanel({ ticketId, currentUserName, currentUserRole })"
 *        x-init="init()"> … </div>
 *
 * Implementation notes
 * --------------------
 * 1. The Pusher echo is the single source of truth for new message
 *    rows in the sender's tab — we never append optimistically, so
 *    there's no chance of double-rendering if both the Pusher echo
 *    and a Livewire re-render race.
 * 2. `MessageSent` is broadcast with `->toOthers()` server-side, but
 *    in this app the sender's private subscription still receives
 *    the echo (Pusher has no concept of "send to everyone except
 *    this specific socket" without a `socket_id` parameter). The
 *    dedup `_seenIds` set absorbs that echo without re-appending.
 * 3. Alpine runs `init()` twice on this component in some Livewire
 *    hydration paths. The `_initialized` flag plus the
 *    `__chatPanelListeners` registry guarantee that exactly one
 *    `.listen()` is bound per (ticketId, page lifetime).
 * 4. The Livewire `send()` method calls `$this->skipRender()` so
 *    Livewire's response payload doesn't re-render the chat log
 *    (which would wipe any rows Alpine has just appended). The
 *    `chat-sent-ack` window event is what clears the input box.
 */
window.chatPanel = function ({ ticketId, currentUserName, currentUserRole }) {
    return {
        ticketId,
        currentUserName,
        currentUserRole,
        connecting: true,
        connected:  false,

        // Track which message ids we have already rendered so any
        // duplicate Pusher echo (from a stale subscription, an
        // Alpine re-init, or our own broadcast coming back to the
        // sender's tab) is dropped instead of appending a second row.
        _seenIds:     new Set(),
        _initialized: false,

        init() {
            // Guard against Alpine re-running init() on the same
            // data object (Livewire 3's hydration can fire init()
            // twice). The Pusher listener is wired only once.
            if (this._initialized) return;
            this._initialized = true;

            // Seed the dedup set with server-rendered message ids so
            // the first Pusher echo for an old message doesn't
            // double up.
            if (Array.isArray(this.messages)) {
                for (const m of this.messages) {
                    if (m && m.id != null) this._seenIds.add(m.id);
                }
            }

            // Build a channel-bound helper once Echo is available.
            const tryConnect = () => {
                if (! window.echo) {
                    setTimeout(tryConnect, 80);
                    return;
                }

                this.connecting = true;
                const echo = window.echo();

                // Module-level channel registry: even if a second
                // Alpine instance on the same page tries to call
                // `echo.private(...)` for the same ticket, we hand
                // it the already-subscribed channel object so it
                // doesn't register a second listener.
                const registry = (window.__chatPanelListeners ||= new Map());
                const key = String(ticketId);
                let channel = registry.get(key);
                if (! channel) {
                    channel = echo.private(`ticket.${ticketId}`);
                    registry.set(key, channel);
                }

                channel.listen('.message.sent', (e) => {
                    // The sender's own tab also receives the
                    // broadcast — `toOthers()` is a no-op in this
                    // scenario, but the dedup set guarantees the
                    // message is rendered exactly once.
                    if (e.id != null && this._seenIds.has(e.id)) {
                        return;
                    }
                    if (e.id != null) this._seenIds.add(e.id);
                    this.appendMessage({
                        id:          e.id,
                        body:        e.body,
                        sender_role: e.sender_role,
                        sender_name: e.sender_name,
                        created_at:  e.created_at,
                        mine:        e.sender_name === currentUserName
                                    && e.sender_role === currentUserRole,
                    });
                });

                // Echo's `subscription_succeeded` is per-channel.
                channel.subscribed(() => {
                    this.connecting = false;
                    this.connected  = true;
                });
                channel.error(() => {
                    this.connecting = false;
                    this.connected  = false;
                });
            };
            tryConnect();

            // Livewire → DOM bridge: after `send()` runs server-side,
            // clear the input. We DO NOT append optimistically — the
            // Pusher echo renders the message exactly once. (Earlier
            // versions appended in both places, which produced a
            // duplicate whenever Pusher beat Livewire to the JS
            // thread.) The `skipRender()` on the server side ensures
            // Livewire doesn't morph the chat-log back to its
            // pre-send state.
            window.addEventListener('chat-sent-ack', () => {
                const input = document.querySelector(
                    'form[wire\\:submit\\.prevent] input[wire\\:model="body"]'
                );
                if (input) {
                    input.value = '';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        },

        appendMessage(msg) {
            const log = document.getElementById(`chat-log-${this.ticketId}`);
            if (! log) return;

            // Clear the "no messages yet" placeholder on first message.
            const empty = log.querySelector('.chat-empty');
            if (empty) empty.remove();

            const row = document.createElement('div');
            row.className = `flex ${msg.mine ? 'justify-end' : 'justify-start'}`;
            if (msg.id !== undefined && msg.id !== null) row.dataset.serverId = msg.id;

            const bubble = document.createElement('div');
            bubble.className = `max-w-[80%] rounded-lg px-4 py-2 text-sm ${msg.mine
                ? 'bg-indigo-600 text-white'
                : 'bg-gray-100 text-gray-900'}`;

            if (! msg.mine) {
                const head = document.createElement('div');
                head.className = 'text-xs font-semibold mb-1 opacity-70';
                head.innerHTML = `${escapeHtml(msg.sender_name)} <span class="ml-1 px-1.5 py-0.5 rounded bg-white/60 text-[10px] uppercase tracking-wider">${escapeHtml(msg.sender_role)}</span>`;
                bubble.appendChild(head);
            }

            const body = document.createElement('div');
            body.className = 'whitespace-pre-wrap break-words chat-msg-body';
            body.textContent = msg.body;
            bubble.appendChild(body);

            const ts = document.createElement('div');
            ts.className = `text-[10px] mt-1 chat-msg-ts ${msg.mine ? 'text-indigo-100' : 'text-gray-400'}`;
            ts.textContent = formatTs(msg.created_at);
            bubble.appendChild(ts);

            row.appendChild(bubble);
            log.appendChild(row);
            this.scrollToBottom();
        },

        scrollToBottom() {
            const log = document.getElementById(`chat-log-${this.ticketId}`);
            if (! log) return;
            requestAnimationFrame(() => {
                log.scrollTop = log.scrollHeight;
            });
        },
    };
};

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatTs(iso) {
    try {
        const d  = new Date(iso);
        const m  = d.toLocaleString('en-US', { month: 'short' });
        const dd = d.getDate();
        const t  = d.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        return `${m} ${dd}, ${t}`;
    } catch (_) {
        return '';
    }
}
