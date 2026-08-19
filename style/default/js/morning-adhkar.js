(function(){
  'use strict';
  var root = document.querySelector('.adhkar-page');
  if (!root) return;

  var cards = Array.prototype.slice.call(root.querySelectorAll('[data-adhkar-card]'));
  var completedEl = root.querySelector('[data-completed]');
  var bar = root.querySelector('[data-progress-bar]');
  var progress = root.querySelector('[role="progressbar"]');
  var message = root.querySelector('[data-progress-message]');
  var reset = root.querySelector('[data-reset]');

  function renderCard(card){
    var target = Number(card.getAttribute('data-target')) || 1;
    var count = Number(card.getAttribute('data-count')) || 0;
    var remaining = Math.max(target - count, 0);
    var value = card.querySelector('[data-remaining]');
    var label = card.querySelector('.counter-label');
    card.classList.toggle('is-complete', remaining === 0);
    value.textContent = remaining === 0 ? '✓' : String(remaining);
    label.textContent = remaining === 0 ? 'تم بحمد الله' : 'اضغط بعد القراءة';
    card.querySelector('[data-counter]').setAttribute('aria-label', remaining === 0 ? 'اكتمل هذا الذكر' : 'تبقى ' + remaining + ' من التكرارات');
  }

  function updateProgress(){
    var done = cards.filter(function(card){ return card.classList.contains('is-complete'); }).length;
    var percent = cards.length ? (done / cards.length) * 100 : 0;
    completedEl.textContent = String(done);
    bar.style.width = percent + '%';
    progress.setAttribute('aria-valuenow', String(done));
    message.textContent = done === cards.length ? 'أتممت أذكار الصباح، تقبّل الله منك.' : done ? 'واصل وِردك، بقي ' + (cards.length - done) + ' من الأذكار.' : 'ابدأ بالذكر الأول، وتقبّل الله طاعتك.';
  }

  cards.forEach(function(card){
    renderCard(card);
    card.querySelector('[data-counter]').addEventListener('click', function(){
      var target = Number(card.getAttribute('data-target')) || 1;
      var count = Number(card.getAttribute('data-count')) || 0;
      if (count >= target) return;
      card.setAttribute('data-count', String(count + 1));
      renderCard(card);
      updateProgress();
    });
  });

  reset.addEventListener('click', function(){
    cards.forEach(function(card){ card.setAttribute('data-count', '0'); renderCard(card); });
    updateProgress();
    cards[0].scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center'});
  });

  updateProgress();
})();
