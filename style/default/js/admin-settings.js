(function () {
  var modal = document.getElementById('settingsSavedModal');
  if (!modal) return;

  var closeButton = document.getElementById('settingsModalClose');

  function closeModal() {
    modal.classList.add('is-closing');

    window.setTimeout(function () {
      if (modal && modal.parentNode) {
        modal.parentNode.removeChild(modal);
      }
    }, 220);
  }

  if (closeButton) {
    closeButton.addEventListener('click', closeModal);
  }

  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeModal();
  });

  window.setTimeout(closeModal, 6000);
})();
