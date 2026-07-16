/**
 * Error Suppressor
 * This script is loaded synchronously in the <head> to intercept and suppress
 * harmless browser extension errors (like "message channel closed") before they clutter the console.
 */
(function() {
    var originalError = console.error;
    console.error = function(...args) {
        if (args[0]) {
            var msg = typeof args[0] === 'string' ? args[0] : (args[0].message || args[0].toString());
            if (msg.includes('message channel closed') || msg.includes('asynchronous response') || msg.includes('extension')) {
                return; // suppress
            }
        }
        originalError.apply(console, args);
    };

    window.addEventListener('unhandledrejection', function(e) {
        if (e.reason) {
            var msg = e.reason.message || e.reason.toString() || '';
            if (msg.includes('message channel closed') || msg.includes('asynchronous response') || msg.includes('listener indicated')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }, true);
})();
