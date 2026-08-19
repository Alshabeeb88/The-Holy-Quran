(function () {
  try {
    document.documentElement.setAttribute(
      'data-theme',
      localStorage.getItem('qfa_theme') === 'dark' ? 'dark' : 'light'
    );
  } catch (e) {}
})();
