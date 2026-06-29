{{-- Service worker registration. Rendered in the main layout once. --}}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .catch(console.error);
        });
    }
</script>
