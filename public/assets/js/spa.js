/**
 * Single Page Application (SPA) Navigation Handler
 */
const SPA = {
    init: function() {
        this.contentSelector = '.content-area'; // The container to update
        this.bindEvents();
        
        // Handle back/forward buttons
        window.onpopstate = (event) => {
            if (event.state && event.state.path) {
                this.loadPage(event.state.path, false);
            } else {
                // If no state, reload to be safe or load current location
                this.loadPage(window.location.href, false);
            }
        };
    },

    bindEvents: function() {
        // Intercept clicks on links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            // Check if it's an internal link and not a download/target=_blank
            const href = link.getAttribute('href');
            const target = link.getAttribute('target');
            
            if (href && 
                !href.startsWith('#') && 
                !href.startsWith('javascript:') && 
                !href.startsWith('mailto:') && 
                !href.startsWith('tel:') && 
                target !== '_blank' &&
                this.isInternalLink(href)) {
                
                e.preventDefault();
                this.loadPage(href);
            }
        });

        // Intercept form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            // Only handle forms that don't explicitly opt-out
            if (!form.hasAttribute('data-no-spa')) {
                e.preventDefault();
                this.submitForm(form);
            }
        });
    },

    isInternalLink: function(url) {
        // Check if the URL belongs to the same origin
        const a = document.createElement('a');
        a.href = url;
        return a.origin === window.location.origin;
    },

    loadPage: function(url, pushState = true) {
        this.showLoading();

        fetch(url, {
            headers: {
                'X-SPA-Request': 'true'
            }
        })
        .then(response => {
            // Handle redirects
            if (response.redirected) {
                url = response.url;
            }
            return response.text().then(html => ({ html, url }));
        })
        .then(({ html, url }) => {
            this.updateContent(html);
            
            if (pushState) {
                window.history.pushState({ path: url }, '', url);
            }
            
            // Update active state in sidebar
            this.updateActiveLink(url);
            
            this.hideLoading();
        })
        .catch(error => {
            console.error('SPA Load Error:', error);
            // Fallback to full reload on error
            window.location.href = url;
        });
    },

    submitForm: function(form) {
        this.showLoading();
        
        const formData = new FormData(form);
        // Use getAttribute to avoid conflict with input named 'action'
        const url = form.getAttribute('action') || window.location.href;
        const method = form.getAttribute('method') || 'GET';
        
        const options = {
            method: method.toUpperCase(),
            headers: {
                'X-SPA-Request': 'true'
            }
        };

        if (method.toUpperCase() === 'POST') {
            options.body = formData;
        } else {
            // For GET, append query params
            const params = new URLSearchParams(formData);
            // If URL already has params, append correctly
            // Simplified for now
        }

        // console.log('SPA Submitting Form to:', url);

        fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            if (response.redirected) {
                // If redirected, load the new URL
                const newUrl = response.url;
                window.history.pushState({ path: newUrl }, '', newUrl);
                this.loadPage(newUrl, false); // false because we just pushed state
                return null;
            }
            return response.text();
        })
        .then(html => {
            if (html) {
                this.updateContent(html);
            }
            this.hideLoading();
        })
        .catch(error => {
            console.error('SPA Form Error:', error);
            // alert('SPA Error: ' + error.message); 
            form.submit(); // Fallback
        });
    },

    updateContent: function(html) {
        const container = document.querySelector(this.contentSelector);
        if (container) {
            // Create a temporary container to parse the HTML
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Extract and apply title
            const title = temp.querySelector('title');
            if (title) {
                document.title = title.innerText;
            }

            // Extract and apply styles from the entire response (head and body)
            const styles = temp.querySelectorAll('style');
            styles.forEach(style => {
                const newStyle = document.createElement('style');
                newStyle.textContent = style.textContent;
                document.head.appendChild(newStyle);
            });
            
            // If the response contains the full page (fallback), extract content
            const newContent = temp.querySelector(this.contentSelector);
            if (newContent) {
                container.innerHTML = newContent.innerHTML;
            } else {
                // Assume partial content
                container.innerHTML = html;
            }
            
            // Execute scripts found in the new content
            this.executeScripts(container);
            
            // Re-initialize any plugins if needed
            // e.g., re-bind datepickers, etc.
        }
    },

    executeScripts: function(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    },
    
    updateActiveLink: function(url) {
        // Remove active class from all links
        document.querySelectorAll('.sidebar-menu .menu-item').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to current link
        const currentUrl = new URL(url, window.location.origin);
        let currentPath = currentUrl.pathname;
        
        // Normalize: remove index.php and trailing slash for comparison
        const normalize = (path) => {
            return path.replace(/\/index\.php$/, '').replace(/\/$/, '');
        };
        
        const normalizedCurrent = normalize(currentPath);

        document.querySelectorAll('.sidebar-menu .menu-item').forEach(link => {
            const linkUrl = new URL(link.href, window.location.origin);
            const linkPath = linkUrl.pathname;
            const normalizedLink = normalize(linkPath);
            
            // Match if exact match OR if current path starts with link path (for sub-pages)
            // But ensure we don't match root '/' against everything unless it's exact
            if (normalizedCurrent === normalizedLink || 
               (normalizedCurrent.startsWith(normalizedLink) && normalizedLink !== '' && normalizedLink !== '/')) {
                link.classList.add('active');
            }
        });
    },

    showLoading: function() {
        // Simple loading indicator
        let loader = document.getElementById('spa-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'spa-loader';
            loader.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:var(--primary-color, #007bff);z-index:9999;transition:width 0.3s;width:0;';
            document.body.appendChild(loader);
        }
        setTimeout(() => loader.style.width = '30%', 10);
        setTimeout(() => loader.style.width = '70%', 200);
    },

    hideLoading: function() {
        const loader = document.getElementById('spa-loader');
        if (loader) {
            loader.style.width = '100%';
            setTimeout(() => {
                loader.style.width = '0';
            }, 300);
        }
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    SPA.init();
});
