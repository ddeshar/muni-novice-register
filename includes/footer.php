    <!-- JavaScript Dependencies (loaded async for performance) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

    <script>
        // Basic error and success handlers
        function handleError(error, title = 'Error') {
            console.error(error);
            alert(error.message || 'An error occurred. Please try again.');
        }

        function handleSuccess(message, title = 'Success') {
            alert(message);
        }

        // Initialize Bootstrap components on load
        window.addEventListener('load', function() {
            // Mobile detection
            if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
                document.body.classList.add('mobile-device');
            }

            // Initialize tooltips if any
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            if (tooltipTriggerList.length > 0) {
                tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
            }
        });
    </script>
    </body>

    </html><?php ?>