/*
 * Alpine.js component for the internal-notes panel (TSP-only).
 * Subscribes to the ticket.{id}.internal private channel and
 * appends incoming notes to the scrollable log. Mirrors the
 * dedup / single-init pattern of chat-panel.js.
 *
 *   <div x-data="internalNotesPanel({ ticketId, currentUserName, currentUserRole })"
 *        x-init="init()"> … </div>
 */
window.internalNotesPanel = function ({ ticketId, currentUserName, currentUserRole }) {
    return {
        ticketId,
        currentUserName,
        currentUserRole,
        notes: [],
        pusherConnected: false,

        // Dedup set keyed on note id. The Pusher subscription is
        // re-used across Alpine re-inits (registry on window), and
        // the seen-ids set absorbs duplicate echoes.
        _seenIds:     new Set(),
        _initialized: false,

        init() {
            if (this._initialized) return;
            this._initialized = true;

            // Seed the log with the notes that were server-rendered
            // by the Livewire mount. The data lives in a `[x-data]`
            // attribute scope as Alpine's `notes` is bound to the
            // `public array $notes` prop on the Volt component.
            if (Array.isArray(this.notes)) {
                for (const n of this.notes) {
                    if (n && n.id != null) this._seenIds.add(n.id);
                }
            }

            // Pull the initial history from the Livewire component
            // (server-rendered) so we have the same source of truth
            // for the first paint.
            const wire = this.$wire;
            if (wire && Array.isArray(wire.notes)) {
                this.notes = wire.notes.map((n) => ({
                    id:          n.id,
                    body:        n.body,
                    author_role: n.author_role,
                    author_name: n.author_name,
                    created_at:  n.created_at,
                }));
                for (const n of this.notes) {
                    if (n && n.id != null) this._seenIds.add(n.id);
                }
            }

            const tryConnect = () => {
                if (! window.echo) {
                    setTimeout(tryConnect, 80);
                    return;
                }

                const echo = window.echo();

                // Module-level registry: even if a second Alpine
                // instance on the same page tries to subscribe to
                // the same channel, we hand it the already-bound
                // channel object so only one listener fires.
                const registry = (window.__internalNotesPanelListeners ||= new Map());
                const key = String(ticketId);
                let channel = registry.get(key);
                if (! channel) {
                    channel = echo.private(`ticket.${ticketId}.internal`);
                    registry.set(key, channel);
                }

                channel.listen('.internal-note.added', (e) => {
                    if (e.id != null && this._seenIds.has(e.id)) {
                        return;
                    }
                    if (e.id != null) this._seenIds.add(e.id);
                    this.appendNote({
                        id:          e.id,
                        body:        e.body,
                        author_role: e.author_role,
                        author_name: e.author_name,
                        created_at:  e.created_at,
                    });
                });

                channel.subscribed(() => { this.pusherConnected = true; });
                channel.error(()     => { this.pusherConnected = false; });
            };
            tryConnect();

            // Livewire → DOM bridge: after `submit()` runs
            // server-side, clear the textarea. We do NOT append
            // optimistically — the Pusher echo renders the note
            // exactly once.
            window.addEventListener('note-sent-ack', () => {
                const input = document.querySelector(
                    '#internal-note-body'
                );
                if (input) {
                    input.value = '';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        },

        appendNote(note) {
            // The template is bound to the Alpine `notes` array.
            // Push the new note and let Alpine re-render. Dedup
            // upstream guarantees this happens exactly once.
            this.notes = [...this.notes, note];
            this.$nextTick(() => this.scrollToBottom());
        },

        formatTime(iso) {
            if (! iso) return '';
            try {
                const d = new Date(iso);
                return d.toLocaleString();
            } catch {
                return iso;
            }
        },

        scrollToBottom() {
            const log = this.$refs.log;
            if (! log) return;
            requestAnimationFrame(() => {
                log.scrollTop = log.scrollHeight;
            });
        },
    };
};
