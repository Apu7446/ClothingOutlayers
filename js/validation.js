/**
 * OutLayers - Form Validation
 */
(function() {
  'use strict';

  function showError(input, msg) {
    clearError(input);
    input.classList.add('input-error');
    var err = document.createElement('span');
    err.className = 'error-message';
    err.textContent = msg;
    input.parentNode.appendChild(err);
  }

  function clearError(input) {
    input.classList.remove('input-error');
    var existing = input.parentNode.querySelector('.error-message');
    if (existing) existing.remove();
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Login Form
    var loginForm = document.querySelector('form[action*="page=login"]');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        var email = this.querySelector('[name="email"]');
        var pass = this.querySelector('[name="password"]');
        var hasError = false;
        
        if (email && !email.value.trim()) { showError(email, 'Email required'); hasError = true; }
        else if (email && !isValidEmail(email.value)) { showError(email, 'Invalid email'); hasError = true; }
        if (pass && !pass.value) { showError(pass, 'Password required'); hasError = true; }
        
        if (hasError) e.preventDefault();
      });
    }

    // Register Form
    var regForm = document.querySelector('form[action*="page=register"]');
    if (regForm) {
      regForm.addEventListener('submit', function(e) {
        var name = this.querySelector('[name="name"]');
        var email = this.querySelector('[name="email"]');
        var pass = this.querySelector('[name="password"]');
        var hasError = false;
        
        if (name && !name.value.trim()) { showError(name, 'Name required'); hasError = true; }
        if (email && !email.value.trim()) { showError(email, 'Email required'); hasError = true; }
        else if (email && !isValidEmail(email.value)) { showError(email, 'Invalid email'); hasError = true; }
        if (pass && pass.value.length < 6) { showError(pass, 'Password min 6 chars'); hasError = true; }
        
        if (hasError) e.preventDefault();
      });
    }

    // Clear errors on input
    document.querySelectorAll('input').forEach(function(input) {
      input.addEventListener('input', function() { clearError(this); });
    });
  });
})();
