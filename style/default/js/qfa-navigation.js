(function () {
  'use strict';

  document.addEventListener('change', function (event) {
    var target = event.target;

    if (!target || !target.matches('[data-qfa-navigate]')) {
      return;
    }

    var value = target.value;

    if (value) {
      window.location.href = value;
    }
  });
})();
