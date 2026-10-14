// Draws the authenticator QR code on admin-security.php from the otpauth link
// in data-uri. Kept in a file because the admin pages allow no inline script.
(function () {
  var box = document.getElementById('qfa-qr');
  if (!box || typeof qrcode !== 'function') return;
  var qr = qrcode(0, 'M');
  qr.addData(box.getAttribute('data-uri') || '');
  qr.make();
  box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
})();
