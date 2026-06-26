/**
 * Admin Tokens Management
 */
document.addEventListener('DOMContentLoaded', function() {
    // Client-side generation (for Create/Edit forms)
    document.querySelectorAll('.js-generate-token').forEach(button => {
        button.addEventListener('click', async function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            this.disabled = true;
            try {
                const response = await fetch('/admin/users/generate-token-api');
                const data = await response.json();
                if (data.token) {
                    input.value = data.token;
                }
            } catch (e) {
                console.error('Failed to generate token', e);
                alert('Error generating token');
            } finally {
                this.disabled = false;
            }
        });
    });

    // Client-side clear (for Create/Edit forms)
    document.querySelectorAll('.js-clear-token').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                input.value = '';
            }
        });
    });

    // Server-side action via form submit (for Show view)
    document.querySelectorAll('.js-api-token').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const csrfToken = this.getAttribute('data-csrf');
            
            if (!confirm('Are you sure you want to perform this action?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_csrf_token';
            csrf.value = csrfToken;
            
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        });
    });
});
