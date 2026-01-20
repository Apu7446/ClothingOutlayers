/**
 * OutLayers - Main JavaScript
 * AJAX Add to Cart functionality
 */
(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    // AJAX Add to Cart
    document.querySelectorAll('.ajax-cart-form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = this.querySelector('button[type="submit"]');
        var originalText = btn ? btn.innerHTML : '';
        
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳'; }
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?page=cart_add_ajax', true);
        xhr.onload = function() {
          if (xhr.status === 200) {
            try {
              var data = JSON.parse(xhr.responseText);
              if (data.success) {
                var badge = document.querySelector('.cart-badge');
                if (badge) {
                  badge.textContent = data.count;
                  badge.style.transform = 'scale(1.3)';
                  setTimeout(function() { badge.style.transform = 'scale(1)'; }, 300);
                }
              } else {
                alert(data.message || 'Failed');
              }
            } catch (e) { alert('Error'); }
          }
          if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
        };
        xhr.onerror = function() {
          alert('Network error');
          if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
        };
        xhr.send(new FormData(this));
      });
    });
  });
})();
