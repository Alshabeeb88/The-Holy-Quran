(function () {
  'use strict';

  var root = document.querySelector('[data-tasbeeh]');
  if (!root) return;

  var goal = parseInt(root.getAttribute('data-goal'), 10) || 100;
  var tap = root.querySelector('[data-tasbeeh-tap]');
  var countNode = root.querySelector('[data-tasbeeh-count]');
  var remainingNode = root.querySelector('[data-tasbeeh-remaining]');
  var messageNode = root.querySelector('[data-tasbeeh-message]');
  var progressNode = root.querySelector('[data-tasbeeh-progress]');
  var progressBar = root.querySelector('[role="progressbar"]');
  var reset = root.querySelector('[data-tasbeeh-reset]');
  var storageKey = 'qfa_tasbeeh_count_v1';
  var count = 0;

  try {
    count = Math.min(goal, Math.max(0, parseInt(localStorage.getItem(storageKey), 10) || 0));
  } catch (error) {
    count = 0;
  }

  function save() {
    try { localStorage.setItem(storageKey, String(count)); } catch (error) {}
  }

  function render(animate) {
    var remaining = Math.max(0, goal - count);
    var percent = Math.min(100, Math.round((count / goal) * 100));
    countNode.textContent = count;
    remainingNode.textContent = remaining ? 'باقي ' + remaining : 'اكتمل الهدف';
    progressNode.style.width = percent + '%';
    progressBar.setAttribute('aria-valuenow', String(count));
    tap.classList.toggle('is-complete', count >= goal);
    root.classList.toggle('is-complete', count >= goal);
    messageNode.textContent = count >= goal ? 'تمت مئة تسبيحة بحمد الله' : (count ? 'استمر، بارك الله في ذكرك' : 'ابدأ بذكر الله');
    if (animate) {
      tap.classList.remove('is-counting');
      void tap.offsetWidth;
      tap.classList.add('is-counting');
    }
  }

  tap.addEventListener('click', function () {
    if (count < goal) {
      count += 1;
      save();
      render(true);
      if (navigator.vibrate) navigator.vibrate(count === goal ? [30, 40, 60] : 16);
    }
  });

  reset.addEventListener('click', function () {
    count = 0;
    save();
    render(false);
    tap.focus();
  });

  render(false);
}());
