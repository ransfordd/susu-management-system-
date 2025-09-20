// Password visibility toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add toggle buttons to all password fields
    const passwordFields = document.querySelectorAll('input[type="password"]');
    
    passwordFields.forEach(function(field) {
        // Create toggle button
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y';
        toggleButton.style.zIndex = '10';
        toggleButton.style.border = 'none';
        toggleButton.style.background = 'transparent';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        
        // Wrap field in relative container if not already
        if (!field.parentElement.classList.contains('position-relative')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
            field.parentNode.insertBefore(wrapper, field);
            wrapper.appendChild(field);
        }
        
        // Add toggle button
        field.parentElement.appendChild(toggleButton);
        
        // Add padding to field to make room for button
        field.style.paddingRight = '45px';
        
        // Toggle functionality
        toggleButton.addEventListener('click', function() {
            if (field.type === 'password') {
                field.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                field.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
});
