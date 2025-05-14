document.addEventListener('DOMContentLoaded', function() {
    console.log('Modal script loaded');
    
    // Elements
    const loginBtn = document.querySelector('.login-btn');
    const signupBtn = document.querySelector('.signup-btn');
    const getStartedBtn = document.querySelector('.cta-button');
    const loginModal = document.getElementById('loginModal');
    const signupModal = document.getElementById('signupModal');
    const closeButtons = document.querySelectorAll('.close-modal');

    // Debug log element existence
    console.log('Login button:', loginBtn ? 'found' : 'not found');
    console.log('Signup button:', signupBtn ? 'found' : 'not found');
    console.log('Login modal:', loginModal ? 'found' : 'not found');
    console.log('Signup modal:', signupModal ? 'found' : 'not found');
    console.log('Close buttons:', closeButtons.length + 'found');

    // Modal open/close
    function openModal(modal) { 
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModals() {
        if (loginModal) loginModal.style.display = 'none';
        if (signupModal) signupModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Event Listeners for modal controls
    if (loginBtn) loginBtn.addEventListener('click', () => openModal(loginModal));
    if (signupBtn) signupBtn.addEventListener('click', () => openModal(signupModal));
    if (getStartedBtn) getStartedBtn.addEventListener('click', () => openModal(signupModal));
    
    closeButtons.forEach(btn => btn.addEventListener('click', closeModals));
    
    window.addEventListener('click', (e) => {
        if (e.target === loginModal || e.target === signupModal) closeModals();
    });

    // Ensure modals are hidden by default
    if (loginModal) loginModal.style.display = 'none';
    if (signupModal) signupModal.style.display = 'none';

    // Login form handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        let isSubmitting = false;
        
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Prevent multiple submissions
            if (isSubmitting) return;
            
            isSubmitting = true;
            
            const formMessage = loginForm.querySelector('.form-message');
            formMessage.textContent = '';
            
            try {
                const formData = new FormData(loginForm);
                const is_admin = formData.get('is_admin') === 'on';
                const action = is_admin ? 'admin_login.php' : 'login.php';
                console.log('Using login endpoint:', action);

                // Disable form during submission
                loginForm.querySelectorAll('input, button').forEach(el => el.disabled = true);

                const response = await fetch(action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Network error');
                }

                const result = await response.json();
                
                if (result.success) {
                    formMessage.textContent = 'Success! Redirecting...';
                    formMessage.style.color = 'green';
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1200);
                } else {
                    formMessage.textContent = result.error || 'Invalid credentials';
                    formMessage.style.color = 'red';
                }
            } catch (error) {
                console.error('Login error:', error);
                formMessage.textContent = 'An error occurred. Please try again.';
                formMessage.style.color = 'red';
            } finally {
                // Re-enable form and reset submission flag
                loginForm.querySelectorAll('input, button').forEach(el => el.disabled = false);
                isSubmitting = false;
            }
        });
    }

    // Signup form handler
    const signupForm = document.querySelector('#signupModal form');
    if (signupForm) {
        const formMessage = signupForm.querySelector('.form-message') || 
            (function() {
                const msg = document.createElement('div');
                msg.className = 'form-message';
                signupForm.appendChild(msg);
                return msg;
            })();

        signupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            formMessage.textContent = 'Processing...';
            formMessage.style.color = 'black';

            try {
                const formData = new FormData(signupForm);
                const response = await fetch('signup.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    formMessage.textContent = 'Account created successfully! Redirecting...';
                    formMessage.style.color = 'green';
                    setTimeout(() => {
                        window.location.href = result.redirect || 'dashboard.php';
                    }, 1500);
                } else {
                    formMessage.textContent = result.error || 'Something went wrong. Please try again.';
                    formMessage.style.color = 'red';
                }
            } catch (error) {
                formMessage.textContent = 'Network error. Please try again.';
                formMessage.style.color = 'red';
            }
        });
    }
});
