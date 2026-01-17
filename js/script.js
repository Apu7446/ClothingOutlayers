/**
 * ========================================
 * MAIN JAVASCRIPT FILE
 * ========================================
 * This file handles client-side functionality:
 * - Theme toggle (Light/Dark mode)
 * 
 * Note: This project does NOT use AJAX.
 * All form submissions use traditional page reloads.
 */

// IIFE (Immediately Invoked Function Expression)
// Wraps code to avoid polluting global namespace
(function () {
  
  // Get the root HTML element for setting data-theme attribute
  const root = document.documentElement;
  
  // Get the theme toggle button (if it exists on the page)
  const btn = document.getElementById('themeToggle');

  /**
   * Set the theme and save to localStorage
   * @param {string} theme - 'light' or 'dark'
   */
  function setTheme(theme) {
    // Set data-theme attribute on <html> element
    // CSS uses this to apply different color variables
    root.setAttribute('data-theme', theme);
    
    // Save preference to localStorage (persists after browser close)
    localStorage.setItem('theme', theme);
  }

  /**
   * Initialize theme on page load
   * 1. Check localStorage for saved preference
   * 2. If none, default to 'light'
   * 3. Add click listener to toggle button
   */
  function init() {
    // Check for saved theme preference
    const saved = localStorage.getItem('theme');
    
    if (saved === 'dark' || saved === 'light') {
      // Use saved preference
      setTheme(saved);
    } else {
      // No preference saved - default to light theme
      setTheme('light');
    }

    // Add click event listener to theme toggle button
    if (btn) {
      btn.addEventListener('click', function () {
        // Get current theme
        const current = root.getAttribute('data-theme') || 'light';
        // Toggle to opposite theme
        setTheme(current === 'light' ? 'dark' : 'light');
      });
    }
  }

  // Run init() when DOM is fully loaded
  document.addEventListener('DOMContentLoaded', init);
  
})();