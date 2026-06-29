/*
 * Alpine.js component for the service-report form (TSP only).
 *
 * The form is server-rendered through Livewire/Volt. After a
 * successful submit the controller dispatches a
 * `service-report-submitted` event which the wrapper catches to
 * show a brief success indicator and — when redirect is set —
 * reload the page so the form flips into read-only summary mode
 * (existingReport is populated from the new row).
 *
 * Pusher echoes (`service-report.submitted`) on the
 * `ticket.{id}.internal` channel also surface here so other TSP
 * tabs see the new report without a refresh.
 */
window.serviceReportForm = function ({ ticketId, currentUserEmail, existingReport }) {
    return {
        ticketId,
        currentUserEmail,
        existingReport: existingReport || null,
        submitting: false,
        _initialized: false,

        init() {
            if (this._initialized) return;
            this._initialized = true;

            // Pre-fill the call_login_time with the current local time
            // the first time the form mounts, but only if the user
            // hasn't typed anything yet.
            const timeInput = document.querySelector('#call_login_time');
            if (timeInput && ! timeInput.value) {
                const d = new Date();
                const hh = String(d.getHours()).padStart(2, '0');
                const mm = String(d.getMinutes()).padStart(2, '0');
                timeInput.value = `${hh}:${mm}`;
                timeInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            const tryConnect = () => {
                if (! window.echo) {
                    setTimeout(tryConnect, 80);
                    return;
                }
                const echo = window.echo();

                // Subscribe to the internal channel so other TSP tabs
                // see the report drop in. The event payload comes
                // from ServiceReportSubmitted::broadcastWith(), which
                // exposes only customer-safe fields.
                const registry = (window.__serviceReportFormListeners ||= new Map());
                const key = `internal:${ticketId}`;
                let channel = registry.get(key);
                if (! channel) {
                    channel = echo.private(`ticket.${ticketId}.internal`);
                    registry.set(key, channel);
                }
                channel.listen('.service-report.submitted', (e) => {
                    // If this tab is the one that just submitted,
                    // the dispatched `service-report-submitted`
                    // event already handled the redirect.
                    if (this.existingReport && this.existingReport.id === e.id) {
                        return;
                    }
                    this.existingReport = e;
                });
            };
            tryConnect();
        },

        onSubmitted(detail) {
            this.submitting = false;
            // We could redirect, but a Livewire refresh keeps Alpine
            // state and Livewire prop in sync (the parent view passes
            // existingReport from the new row).
            if (detail && detail.redirect) {
                window.location.assign(detail.redirect);
            }
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
    };
};
