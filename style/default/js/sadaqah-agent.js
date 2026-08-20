(function () {
  'use strict';

  var root = document.querySelector('.sadaqah-agent-page');
  if (!root) return;

  var LIMIT = 280;
  var KEY = 'qfa_sadaqah_agent_preview_v1';

  /*
   * Web Intent endpoint. It only opens a composer with the text filled in;
   * nothing is published until the administrator presses Post inside X. No API,
   * no credentials, and no request leaves this page.
   *
   * The intent/tweet form is used rather than the newer intent/post: testing in
   * Safari showed the composer opening empty with intent/post, while this one
   * carries the text through reliably.
   */
  var X_INTENT = 'https://twitter.com/intent/tweet';

  var tabs = [].slice.call(root.querySelectorAll('[data-day-tab]'));
  var panels = [].slice.call(root.querySelectorAll('[data-day-panel]'));
  var posts = [].slice.call(root.querySelectorAll('[data-post]'));

  var state = {};
  try {
    state = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
  } catch (e) {
    state = {};
  }

  function save() {
    try {
      localStorage.setItem(KEY, JSON.stringify(state));
    } catch (e) {}
  }

  function entryFor(post) {
    return state[post.getAttribute('data-post')];
  }

  /*
   * Counter and over-limit styling. This deliberately never assigns to
   * textarea.value: writing to the value moves the caret to the end of the
   * field, which made editing anywhere but the end of the text impossible.
   * Because it only reads, it is safe to call on every keystroke.
   */
  function count(post) {
    var area = post.querySelector('textarea');
    var length = area.value.length;

    post.querySelector('[data-char-count]').textContent = length;
    post.classList.toggle('is-over-limit', length > LIMIT);
  }

  // Approval badge only; likewise never writes to the textarea.
  function status(post) {
    var entry = entryFor(post);
    var approved = !!(entry && entry.approved);

    post.classList.toggle('is-approved', approved);
    post.querySelector('[data-post-status]').textContent = approved ? 'معتمدة' : 'بانتظار المراجعة';
  }

  /*
   * The only function that writes to the textarea, so it runs on load and on an
   * explicit reset, never while the administrator is typing.
   */
  function restore(post) {
    var entry = entryFor(post);
    var area = post.querySelector('textarea');

    if (entry && typeof entry.text === 'string') area.value = entry.text;
    area.readOnly = true;
  }

  function lock(post) {
    post.querySelector('textarea').readOnly = true;
  }

  function unlock(post) {
    var area = post.querySelector('textarea');

    area.readOnly = false;
    area.focus();
    area.setSelectionRange(area.value.length, area.value.length);
  }

  /*
   * The text to hand to X: whatever stands in the textarea at this moment, with
   * the readable placeholders swapped for the real links the server resolved.
   * Reads the field and never writes to it, so sharing cannot disturb the caret,
   * the stored draft, or the approval state.
   *
   * Replacement is done with split/join rather than a regular expression, so the
   * brackets in a placeholder are treated as literal text and can never be
   * interpreted as a pattern.
   */
  function shareText(post) {
    var text = post.querySelector('textarea').value;
    var raw = post.getAttribute('data-share-links');
    var links = null;

    if (!raw) return text;
    try {
      links = JSON.parse(raw);
    } catch (e) {
      return text;
    }
    if (!links || typeof links !== 'object') return text;

    Object.keys(links).forEach(function (placeholder) {
      if (typeof links[placeholder] === 'string') {
        text = text.split(placeholder).join(links[placeholder]);
      }
    });

    return text;
  }

  function shareOnX(post) {
    var url = X_INTENT + '?text=' + encodeURIComponent(shareText(post));
    var win = window.open(url, '_blank', 'noopener,noreferrer');

    // Belt and braces: if the browser honoured noopener it already returned null,
    // and if it did not, the new window must still not reach back into this page.
    if (win) win.opener = null;
  }

  function approve(post) {
    var area = post.querySelector('textarea');

    state[post.getAttribute('data-post')] = { text: area.value, approved: true };
    lock(post);
    status(post);
    count(post);
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var id = tab.getAttribute('data-day-tab');

      tabs.forEach(function (other) {
        var active = other === tab;
        other.classList.toggle('is-active', active);
        other.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      panels.forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-day-panel') === id);
      });
    });
  });

  posts.forEach(function (post) {
    var id = post.getAttribute('data-post');
    var area = post.querySelector('textarea');

    restore(post);
    status(post);
    count(post);

    area.addEventListener('input', function () {
      var entry = state[id] || (state[id] = {});

      entry.text = area.value;
      entry.approved = false;
      save();

      // Neither call touches the value, so the caret stays where it is.
      status(post);
      count(post);
    });

    post.querySelector('[data-edit]').addEventListener('click', function () {
      unlock(post);
    });

    post.querySelector('[data-approve]').addEventListener('click', function () {
      approve(post);
      save();
    });

    post.querySelector('[data-share-x]').addEventListener('click', function () {
      shareOnX(post);
    });
  });

  [].slice.call(root.querySelectorAll('[data-approve-day]')).forEach(function (button) {
    button.addEventListener('click', function () {
      var panel = button.closest('[data-day-panel]');
      if (!panel) return;

      if (!window.confirm('سيتم اعتماد جميع تغريدات هذا اليوم بنصّها الحالي.\n\nهل تريد المتابعة؟')) return;

      [].slice.call(panel.querySelectorAll('[data-post]')).forEach(approve);
      save();
    });
  });

  var resetButton = root.querySelector('[data-reset-plan]');
  if (resetButton) {
    resetButton.addEventListener('click', function () {
      if (!window.confirm('سيتم حذف جميع التعديلات والاعتمادات المحفوظة على هذا الجهاز، وإرجاع النصوص إلى أصلها.\n\nلا يمكن التراجع عن هذا الإجراء. هل تريد المتابعة؟')) return;

      state = {};
      try {
        localStorage.removeItem(KEY);
      } catch (e) {}

      posts.forEach(function (post) {
        var area = post.querySelector('textarea');

        area.value = area.defaultValue;
        lock(post);
        status(post);
        count(post);
      });
    });
  }
})();
