/*
 * Alpine.js component for the customer-side ticket page.
 *
 * Subscribes to the `ticket.{id}.customer` private channel and
 * reacts to two Pusher events:
 *
 *   - service-report.submitted  → TSP just filed a service report.
 *                                 We flash a "Service report
 *                                 submitted" banner with a public
 *                                 summary and (if applicable) a
 *                                 "Service complete" status flip.
 *   - ticket.status.changed     → Ticket status95 was changed by
 *                                 the controller when the report
 *                                 was submitted. We update the
 *                                 status pill in place and, if
 *                                 the new status is "Resolved",
 *                                 surface a resolution summary.
 *
 * Usage:
 *   <div x-data="ticketStatusBanner({ ticketId, currentStatus })"
 *        x-init="init()"> … </div>
 */
window.ticketStatusBanner = function ({ ticketId, currentStatus }) {
    return {
        ticketId,
        currentStatus: currentStatus || null,
        previousStatus: currentStatus || null,
        newStatus: null,
        flash: null,            // { kind, message }
        resolution: null,       // { author_name, customer_summary, completed_at }
        _initialized: false,
        _resolvedDismissed: false,

        init() {
            if (this._initialized) return;
            this._initialized = true;

            const tryConnect = () => {
                if (! window.echo) {
                    setTimeout(tryConnect, 80);
                    return;
                }
                const echo = window.echo();

                const registry = (window.__ticketStatusBannerListeners ||= new Map());
                const key = `customer:${ticketId}`;
                let channel = registry.get(key);
                if (! channel) {
                    channel = echo.private(`ticket.${ticketId}.customer`);
                    registry.set(key, channel);
                }

                channel.listen('.ticket.status.changed', (e) => {
                    this.previousStatus = e.previous_status || this.currentStatus;
                    this.newStatus      = e.new_status;
                    this.currentStatus  = e.new_status;
                    this.flash = {
                        kind:    'status',
                        message: `Ticket status updated: ${e.previous_status || '—'} → ${e.new_status}`,
                    };
                });

                channel.listen('.service-report.submitted', (e) => {
                    // Show a resolution summary card if the report
                    // marked the ticket completed.
                    if (e.service_status === 'completed' && e.customer_summary) {
                        this.resolution = {
                            author_name:      e.author_name,
                            customer_summary: e.customer_summary,
                            completed_at:     e.completed_at,
                        };
                    }
                    this.flash = {
                        kind:    'report',
                        message: `A service report was submitted (status: ${e.service_status_label}).`,
                    };
                });
            };
            tryConnect();
        },

        dismissFlash() {
            this.flash = null;
        },
    };
};
