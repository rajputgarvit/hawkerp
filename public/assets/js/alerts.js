class AlertSystem {
    constructor() {
        if (document.querySelector('.alert-toast-container')) {
            this.container = document.querySelector('.alert-toast-container');
        } else {
            this.container = document.createElement('div');
            this.container.className = 'alert-toast-container';
            document.body.appendChild(this.container);
        }
        
        // Check for URL parameters on load
        this.checkUrlParams();
    }

    show(message, type = 'info', duration = 5000) {
        const alert = document.createElement('div');
        alert.className = `alert-toast alert-toast-${type}`;
        
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        if (type === 'warning') icon = 'exclamation-triangle';

        alert.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span class="alert-toast-message">${message}</span>
            <button class="alert-toast-close">&times;</button>
        `;

        // Close button
        alert.querySelector('.alert-toast-close').addEventListener('click', () => {
            this.dismiss(alert);
        });

        this.container.appendChild(alert);

        // Animate in
        requestAnimationFrame(() => {
            alert.classList.add('show');
        });

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(alert);
            }, duration);
        }
    }

    dismiss(alert) {
        alert.classList.remove('show');
        alert.addEventListener('transitionend', () => {
            if (alert.parentElement) {
                alert.remove();
            }
        });
    }

    checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        let paramsChanged = false;

        if (urlParams.has('success')) {
            this.show(urlParams.get('success'), 'success');
            urlParams.delete('success');
            paramsChanged = true;
        }
        if (urlParams.has('error')) {
            this.show(urlParams.get('error'), 'error');
            urlParams.delete('error');
            paramsChanged = true;
        }
        if (urlParams.has('warning')) {
            this.show(urlParams.get('warning'), 'warning');
            urlParams.delete('warning');
            paramsChanged = true;
        }

        if (paramsChanged) {
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, document.title, newUrl);
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.alerts = new AlertSystem();
});
