/*
 * Runs in <head> before the page renders. It used to be an inline script, which
 * the site's Content-Security-Policy (script-src 'self') blocks, so a saved dark
 * theme flashed light on every load and old hash links were never redirected.
 */
(function(){
  try {
    var savedTheme = localStorage.getItem('qfa_theme');
    var initialTheme = savedTheme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', initialTheme);
    document.documentElement.style.setProperty('--breadcrumb-inline-color', initialTheme === 'dark' ? '#f1f5f3' : '#0b6555');
    document.documentElement.style.setProperty('--breadcrumb-active-inline-color', initialTheme === 'dark' ? '#b7c7c1' : '#66756f');
  } catch(e) {
    document.documentElement.setAttribute('data-theme', 'light');
    document.documentElement.style.setProperty('--breadcrumb-inline-color', '#0b6555');
    document.documentElement.style.setProperty('--breadcrumb-active-inline-color', '#66756f');
  }
})();

// V22: legacy hash routes are no longer valid. Redirect before the page renders.
(function(){
  var h = window.location.hash;
  if (h !== '#contact-us' && h !== '#date-converter') return;
  var script = document.currentScript;
  var base = script ? script.src.replace(/style\/[^\/]+\/js\/theme-init\.js.*$/, '') : '/';
  if (h === '#contact-us') { window.location.replace(base + 'contact.php'); }
  if (h === '#date-converter') { window.location.replace(base + 'date-converter.php'); }
})();
