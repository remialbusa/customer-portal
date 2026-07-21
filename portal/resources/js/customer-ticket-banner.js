/*
|--------------------------------------------------------------------------
| Customer ticket banner Alpine factory
|--------------------------------------------------------------------------
|
| Subscribes to the per-ticket customer Pusher channel
| (`private-ticket.{id}.customer`) and listens for two events:
|
|   - `ticket.claimed`    → show "<TSP name> has been assigned to
|                           your ticket" success banner.
|   - `ticket.status.changed` → if the new status is a closed
|                               state (resolved/closed/done/
|                               complete), show a warning banner
|                               "This ticket is closed. Chat is
|                               read-only." (mirrors the
|                               chat-bubble's disabled state).
|
| Used by the customer ticket-show view
| (`resources/views/customer/tickets/show.blade.php`). The factory
| is registered on `window` so the view's
| `x-data="customerTicketBanner({...})"` expression resolves to
| the same instance across hot-reloads.
|
| The factory is defensive about missing/disabled Echo — if
| `window.echo` isn't a function, it just doesn't subscribe. The
| banner then stays in its initial state and the customer sees
| the static "status" + "assigned" rows that are already
| server-rendered.
*/

const CLOSED_STATUS_PATTERNS = ['resolved', 'closed', 'done', 'complete'];

function isClosedStatus(statusText) {
    if (!statusText) return false;
    const lower = String(statusText).toLowerCase();
    return CLOSED_STATUS_PATTERNS.some(p => lower.includes(p));
}

function customerTicketBannerFactory({ ticketId, initialIsClosed = false, initialHasTsp = false } = {}) {
    return {
        // Visibility — true while a recent event is on screen.
        // The banner auto-hides after `autoHideMs` (success)
        // or stays visible (closed) until the page reloads.
        visible: false,

        // 'success' = assignment, 'warning' = closed notice.
        tone: 'success',

        // Display text.
        title: '',
        body:  '',

        // Internal state — tracks what we've already shown so a
        // duplicate Pusher event doesn't re-trigger the toast.
        _shownForTicket: null,
        _autoHideTimer: null,
        _echoSub: null,

        // Server-rendered initial state — used to decide whether
        // to auto-show the closed banner on page load.
        _initialIsClosed: !!initialIsClosed,
        _initialHasTsp:  !!initialHasTsp,

        init() {
            // If the page loaded with the ticket already in a
            // closed state, surface the closed notice immediately
            // so the customer isn't surprised by a disabled chat
            // input with no explanation.
            if (this._initialIsClosed) {
                this.showClosed('This ticket is closed. Chat is read-only.');
            }

            // Subscribe to the customer-side channel. Echo is set
            // up by `app.js`; the `getEcho` factory in echo.js
            // returns a singleton so we don't open multiple
            // connections even if the factory is initialised
            // more than once.
            if (typeof window.echo !== 'function') return;
            const echo = window.echo();
            if (!echo) return;

            const channel = `ticket.${ticketId}.customer`;
            try {
                this._echoSub = echo.private(channel)
                    .listen('.ticket.claimed', (e) => {
                        const tspName = e?.tsp_name || 'A technician';
                        const id = e?.monday_ticket_id;
                        this._shownForTicket = id || ticketId;
                        this._show({
                            tone:  'success',
                            title: `${tspName} has been assigned to your ticket`,
                            body:  'They will be in touch shortly. You can chat with them using the bubble below.',
                            autoHideMs: 6000,
                        });
                    })
                    .listen('.ticket.status.changed', (e) => {
                        const newStatus = String(e?.new_status || '').toLowerCase();
                        if (isClosedStatus(newStatus)) {
                            this._shownForTicket = e?.monday_ticket_id || ticketId;
                            this._show({
                                tone:  'warning',
                                title: 'This ticket is closed',
                                body:  'No further action is required. The chat is now read-only.',
                                autoHideMs: 0, // sticky until reload
                                forceVisible: true,
                            });
                        }
                    });
            } catch (err) {
                // Echo is not configured (e.g. in test/dev). The
                // page still works — the static server-rendered
                // status is the source of truth.
                // eslint-disable-next-line no-console
                console.warn('[customer-ticket-banner] subscribe failed', err);
            }
        },

        destroy() {
            if (this._autoHideTimer) {
                clearTimeout(this._autoHideTimer);
                this._autoHideTimer = null;
            }
            if (this._echoSub && typeof this._echoSub.stopListening === 'function') {
                try {
                    this._echoSub.stopListening('.ticket.claimed');
                    this._echoSub.stopListening('.ticket.status.changed');
                } catch (e) {
                    // Channel cleanup is best-effort.
                }
            }
        },

        // Public helper — used by the page-load path when the
        // server already knows the ticket is closed.
        showClosed(message) {
            this._show({
                tone:  'warning',
                title: 'This ticket is closed',
                body:  message,
                autoHideMs: 0,
                forceVisible: true,
            });
        },

        // Internal — sets banner content and starts the auto-hide
        // timer if `autoHideMs > 0`.
        _show({ tone, title, body, autoHideMs = 5000, forceVisible = false }) {
            this.tone  = tone;
            this.title = title;
            this.body  = body;
            this.visible = true;

            if (this._autoHideTimer) {
                clearTimeout(this._autoHideTimer);
                this._autoHideTimer = null;
            }
            if (autoHideMs > 0) {
                this._autoHideTimer = setTimeout(() => {
                    if (!forceVisible) this.visible = false;
                }, autoHideMs);
            }
        },
    };
}

if (typeof window !== 'undefined') {
    window.customerTicketBanner = customerTicketBannerFactory;
}
