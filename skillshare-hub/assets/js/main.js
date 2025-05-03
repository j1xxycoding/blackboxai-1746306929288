// Main JavaScript for Skillshare Hub

document.addEventListener('DOMContentLoaded', function() {
    // Handle file input preview for profile photo upload
    const photoInput = document.querySelector('.profile-photo-input');
    const photoPreview = document.querySelector('.profile-photo-preview');
    
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Credit card form validation
    const cardForm = document.querySelector('.payment-form');
    if (cardForm) {
        cardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple card number validation (for demo)
            const cardNumber = document.querySelector('#card_number').value.replace(/\s/g, '');
            const cardExpiry = document.querySelector('#card_expiry').value;
            const cardCVV = document.querySelector('#card_cvv').value;
            
            if (cardNumber.length !== 16 || !/^\d+$/.test(cardNumber)) {
                showError('Please enter a valid 16-digit card number');
                return;
            }
            
            if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                showError('Please enter a valid expiry date (MM/YY)');
                return;
            }
            
            if (!/^\d{3,4}$/.test(cardCVV)) {
                showError('Please enter a valid CVV');
                return;
            }
            
            // If validation passes, submit the form
            simulatePayment();
        });
    }

    // Simulate payment processing
    function simulatePayment() {
        const submitBtn = document.querySelector('.payment-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
            
            // Simulate API call delay
            setTimeout(() => {
                // Random success/failure for demo
                const success = Math.random() > 0.2;
                if (success) {
                    window.location.href = 'booking-confirmation.php?status=success';
                } else {
                    showError('Payment failed. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Pay Now';
                }
            }, 2000);
        }
    }

    // Error message display
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        errorDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.querySelector('.payment-form');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
        }
    }

    // Handle skill search filtering
    const searchInput = document.querySelector('#skill-search');
    const categorySelect = document.querySelector('#category-filter');
    const skillCards = document.querySelectorAll('.skill-card');

    if (searchInput && categorySelect && skillCards.length > 0) {
        function filterSkills() {
            const searchTerm = searchInput.value.toLowerCase();
            const category = categorySelect.value;

            skillCards.forEach(card => {
                const title = card.querySelector('.skill-title').textContent.toLowerCase();
                const cardCategory = card.dataset.category;
                const matchesSearch = title.includes(searchTerm);
                const matchesCategory = category === 'all' || cardCategory === category;
                
                card.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterSkills);
        categorySelect.addEventListener('change', filterSkills);
    }

    // Format card number input with spaces
    const cardNumberInput = document.querySelector('#card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            if (value.length > 16) value = value.substr(0, 16);
            const parts = value.match(/.{1,4}/g) || [];
            e.target.value = parts.join(' ');
        });
    }

    // Auto-format expiry date input
    const expiryInput = document.querySelector('#card_expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.substr(0, 4);
            if (value.length > 2) {
                value = value.substr(0, 2) + '/' + value.substr(2);
            }
            e.target.value = value;
        });
    }
});
