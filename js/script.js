(function () {
  const root = document.documentElement;
  const btn = document.getElementById('themeToggle');

  function setTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
  }

  function init() {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark' || saved === 'light') {
      setTheme(saved);
    } else {
      // default: light
      setTheme('light');
    }

    if (btn) {
      btn.addEventListener('click', function () {
        const current = root.getAttribute('data-theme') || 'light';
        setTheme(current === 'light' ? 'dark' : 'light');
      });
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();