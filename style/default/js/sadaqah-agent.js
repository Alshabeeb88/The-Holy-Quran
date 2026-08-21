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

  var SEED_URL = 'x-studio-seed.php';

  /*
   * Where the plan on screen came from. Once the server holds a plan it is the
   * only source: the local draft store is neither read nor written, so the two
   * can never disagree about what the week contains. Older drafts are left on
   * the device untouched rather than deleted.
   */
  var planState = root.getAttribute('data-plan-state') || 'legacy';
  var serverBacked = planState === 'ready';

  var tabs = [].slice.call(root.querySelectorAll('[data-day-tab]'));
  var panels = [].slice.call(root.querySelectorAll('[data-day-panel]'));
  var posts = [].slice.call(root.querySelectorAll('[data-post]'));

  var state = {};
  if (!serverBacked) {
    try {
      state = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
    } catch (e) {
      state = {};
    }
  }

  function save() {
    if (serverBacked) return;
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
    var counter = post.querySelector('[data-char-count]');
    if (!area || !counter) return;

    var length = area.value.length;

    counter.textContent = length;
    post.classList.toggle('is-over-limit', length > LIMIT);
  }

  // Approval badge only; likewise never writes to the textarea.
  function status(post) {
    var badge = post.querySelector('[data-post-status]');
    if (!badge) return;

    var entry = entryFor(post);
    var approved = !!(entry && entry.approved);

    post.classList.toggle('is-approved', approved);
    badge.textContent = approved ? 'معتمدة' : 'بانتظار المراجعة';
  }

  /*
   * The only function that writes to the textarea, so it runs on load and on an
   * explicit reset, never while the administrator is typing.
   */
  function restore(post) {
    var entry = entryFor(post);
    var area = post.querySelector('textarea');
    if (!area) return;

    if (entry && typeof entry.text === 'string') area.value = entry.text;
    area.readOnly = true;
  }

  function lock(post) {
    var area = post.querySelector('textarea');
    if (area) area.readOnly = true;
  }

  function unlock(post) {
    var area = post.querySelector('textarea');
    if (!area) return;

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
    var area = post.querySelector('textarea');
    var text = area ? area.value : '';
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
    if (!area) return;

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

  // ---------------------------------------------------------------------
  // Talking to the server about one post
  // ---------------------------------------------------------------------

  var SAVE_URL = 'x-studio-save.php';

  /*
   * Set once a conflict is reported. The plan on screen is then known to be out
   * of date, so every further write is refused locally until the page is
   * reloaded: retrying with a fresh revision would silently overwrite whatever
   * the other tab just did.
   */
  var writesBlocked = false;

  /** The revision the server last confirmed. Read at send time, never cached. */
  function currentRevision() {
    return root.getAttribute('data-plan-revision') || '';
  }

  /** Messages are shown as text. A server string must never become markup. */
  function tell(post, text) {
    var box = post.querySelector('[data-post-message]');
    if (box) box.textContent = text || '';
  }

  /** Offer a reload after a conflict, without ever building HTML from a string. */
  function offerReload(post) {
    var box = post.querySelector('[data-post-message]');
    if (!box || box.querySelector('[data-reload]')) return;

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'agent-reload';
    button.setAttribute('data-reload', '');
    button.textContent = 'إعادة تحميل الصفحة';
    button.addEventListener('click', function () {
      window.location.reload();
    });

    box.appendChild(document.createTextNode(' '));
    box.appendChild(button);
  }

  /** Paint one post from what the server says it now holds. */
  function applyServerPost(post, serverPost) {
    if (!serverPost || typeof serverPost !== 'object') return;

    var area = post.querySelector('textarea');
    var badge = post.querySelector('[data-post-status]');
    var approveButton = post.querySelector('[data-approve]');
    var editButton = post.querySelector('[data-edit]');
    var publishedButton = post.querySelector('[data-mark-published]');
    var unpublishButton = post.querySelector('[data-unmark-published]');
    var unapproveButton = post.querySelector('[data-unapprove]');

    /*
     * Leave edit mode first, whatever put the post there. This function is the
     * one place that paints a post from server state, and edit mode is part of
     * that picture: without this, a post recorded as published while its text
     * was open stayed editable, with حفظ and إلغاء still on screen.
     *
     * Doing it here also makes the two ways of arriving at a state agree — a
     * page rendered as published and a post that became published over AJAX end
     * up with identical controls.
     */
    editing(post, false);

    // .value, never innerHTML: the text is content, not markup.
    if (area && typeof serverPost.text === 'string') area.value = serverPost.text;

    var approved = serverPost.approved === true;
    var published = serverPost.published === true;

    post.classList.toggle('is-approved', approved && !published);
    post.classList.toggle('is-published', published);
    if (badge) badge.textContent = published ? 'تم النشر' : (approved ? 'معتمدة' : 'بانتظار المراجعة');
    if (approveButton) approveButton.disabled = approved || published;

    // Editing a recorded post is refused by the server, so the control is shut
    // here too rather than left to fail on click.
    if (editButton) editButton.disabled = published;

    /*
     * Exactly one of the two record controls belongs on screen at a time, and
     * either can become the right one after the other is used, so both are kept
     * in the DOM and shown by state rather than removed.
     */
    if (publishedButton) {
      publishedButton.hidden = published || !approved;
      publishedButton.disabled = published || !approved;
    }
    if (unpublishButton) {
      unpublishButton.hidden = !published;
      unpublishButton.disabled = !published;
    }

    /*
     * Withdrawing an approval belongs only to an approved post that has not been
     * recorded as published: a recorded one must have that record withdrawn
     * first, matching the order the server enforces.
     */
    if (unapproveButton) {
      unapproveButton.hidden = !approved || published;
      unapproveButton.disabled = !approved || published;
    }

    count(post);
  }

  /** Turn one post's controls on or off while a request is in flight. */
  function busy(post, isBusy) {
    ['[data-edit]', '[data-save]', '[data-cancel]', '[data-approve]', '[data-mark-published]', '[data-unmark-published]', '[data-unapprove]'].forEach(function (selector) {
      var button = post.querySelector(selector);
      if (button) button.disabled = isBusy;
    });
  }

  function editing(post, on) {
    var area = post.querySelector('textarea');
    var editButton = post.querySelector('[data-edit]');
    var saveButton = post.querySelector('[data-save]');
    var cancelButton = post.querySelector('[data-cancel]');
    var approveButton = post.querySelector('[data-approve]');

    if (area) area.readOnly = !on;
    if (editButton) editButton.hidden = on;
    if (saveButton) saveButton.hidden = !on;
    if (cancelButton) cancelButton.hidden = !on;
    if (approveButton) approveButton.hidden = on;

    post.classList.toggle('is-editing', on);
  }

  /**
   * One request about one post. Only the named fields are ever sent, and the
   * page state is changed only from what comes back.
   */
  function sendPostAction(post, fields, onSuccess) {
    var token = root.getAttribute('data-csrf') || '';
    if (!token) {
      tell(post, 'تعذر التحقق من الصفحة. أعد تحميلها ثم حاول مرة أخرى.');
      return;
    }
    if (writesBlocked) {
      tell(post, 'تم تعديل الخطة من مكان آخر. أعد تحميل الصفحة.');
      offerReload(post);
      return;
    }

    var body = new URLSearchParams();
    body.append('action', fields.action);
    body.append('csrf', token);
    body.append('post_id', post.getAttribute('data-post') || '');
    body.append('expected_revision', currentRevision());
    if (typeof fields.text === 'string') body.append('text', fields.text);

    busy(post, true);
    tell(post, 'جارٍ الحفظ…');

    fetch(SAVE_URL, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: body.toString()
    }).then(function (response) {
      return response.json().then(function (data) {
        return { ok: response.ok, data: data };
      }).catch(function () {
        return { ok: false, data: {} };
      });
    }).then(function (result) {
      var data = result.data || {};
      var code = data.code || '';

      if (code === 'POST_UPDATED' || code === 'OK') {
        if (typeof data.revision === 'number') {
          root.setAttribute('data-plan-revision', String(data.revision));
        }
        /*
         * Release the in-flight lock first, then paint. busy() re-enables every
         * control indiscriminately, so running it afterwards undid the state it
         * had just been given: a post recorded as published came back with its
         * تعديل and اعتماد buttons live again. Painting last leaves the server's
         * answer as the final word.
         */
        busy(post, false);
        applyServerPost(post, data.post);
        tell(post, '');
        if (onSuccess) onSuccess();
        return;
      }

      if (code === 'REVISION_CONFLICT') {
        /*
         * No retry, no merge, and nothing on screen is changed from the request
         * that failed: the local copy is stale, and only a reload can settle it.
         */
        writesBlocked = true;
        tell(post, 'تم تعديل الخطة من مكان آخر. أعد تحميل الصفحة.');
        offerReload(post);
        return;   // controls stay disabled
      }

      if (code === 'AUTH_REQUIRED') {
        tell(post, 'انتهت جلسة الإدارة. سجّل الدخول من جديد.');
        return;
      }
      if (code === 'CSRF_FAILED' || code === 'ORIGIN_REJECTED') {
        tell(post, 'رُفض الطلب لأسباب أمنية. أعد تحميل الصفحة ثم حاول مرة أخرى.');
        return;
      }
      if (code === 'RULE_VIOLATION') {
        // Shown as plain text, straight from the server's own wording.
        tell(post, typeof data.message === 'string' ? data.message : 'لا يمكن تنفيذ هذه العملية.');
        busy(post, false);
        return;
      }
      if (code === 'PLAN_NOT_FOUND') {
        writesBlocked = true;
        tell(post, 'لم تعد الخطة موجودة على الخادم. أعد تحميل الصفحة.');
        offerReload(post);
        return;
      }
      if (code === 'STORE_CORRUPT') {
        tell(post, 'ملف الخطة تالف. راجعه على الخادم قبل المحاولة.');
        return;   // no retry: this needs a human
      }
      if (code === 'STORE_UNREADABLE') {
        tell(post, 'تعذر قراءة ملف الخطة. راجع صلاحيات الملف على الخادم.');
        return;
      }
      if (code === 'WRITE_FAILED') {
        // Nothing was written, so trying again is safe.
        tell(post, 'تعذر حفظ التغيير على الخادم. حاول مرة أخرى.');
        busy(post, false);
        return;
      }

      tell(post, typeof data.message === 'string' ? data.message : 'تعذر تنفيذ العملية.');
      busy(post, false);
    }).catch(function () {
      // A network failure changed nothing on the server, so nothing changes here
      // either; the controls simply come back.
      tell(post, 'تعذر الاتصال بالخادم. تحقق من الاتصال ثم حاول مرة أخرى.');
      busy(post, false);
    });
  }

  posts.forEach(function (post) {
    var id = post.getAttribute('data-post');
    var area = post.querySelector('textarea');

    /*
     * A server-backed plan is rendered complete, including its approval badges,
     * so there is nothing to restore and nothing to recompute: touching it here
     * would only risk overwriting what the server said with a stale draft.
     */
    if (!serverBacked) {
      restore(post);
      status(post);
    }
    count(post);

    if (area) {
      area.addEventListener('input', function () {
        // Counting is safe either way; only the local draft store is gated.
        count(post);
        if (serverBacked) return;

        var entry = state[id] || (state[id] = {});

        entry.text = area.value;
        entry.approved = false;
        save();

        // Neither call touches the value, so the caret stays where it is.
        status(post);
      });
    }

    var editButton = post.querySelector('[data-edit]');
    var saveButton = post.querySelector('[data-save]');
    var cancelButton = post.querySelector('[data-cancel]');
    var approveButton = post.querySelector('[data-approve]');

    // The text as the server last confirmed it, so cancelling can restore it
    // without asking again.
    var confirmedText = area ? area.value : '';

    if (editButton) {
      editButton.addEventListener('click', function () {
        if (!serverBacked) { unlock(post); return; }

        confirmedText = area ? area.value : '';
        editing(post, true);
        tell(post, '');
        if (area) {
          area.focus();
          area.setSelectionRange(area.value.length, area.value.length);
        }
      });
    }

    if (cancelButton) {
      cancelButton.addEventListener('click', function () {
        if (area) area.value = confirmedText;
        count(post);
        editing(post, false);
        tell(post, '');
      });
    }

    if (saveButton) {
      saveButton.addEventListener('click', function () {
        if (!area || saveButton.disabled) return;   // guards the double click

        sendPostAction(post, { action: 'save_post', text: area.value }, function () {
          confirmedText = area.value;
          editing(post, false);
        });
      });
    }

    if (approveButton) {
      approveButton.addEventListener('click', function () {
        if (approveButton.disabled) return;         // guards the double click

        if (!serverBacked) { approve(post); save(); return; }

        // No text is sent: approval is about the stored wording, not this page's.
        sendPostAction(post, { action: 'approve_post' }, function () {
          confirmedText = area ? area.value : confirmedText;
        });
      });
    }

    var shareButton = post.querySelector('[data-share-x]');
    if (shareButton) {
      shareButton.addEventListener('click', function () {
        // Sharing only opens the composer. It never records anything: whether
        // the post was actually sent is something only the administrator knows.
        shareOnX(post);
      });
    }

    var publishedButton = post.querySelector('[data-mark-published]');
    if (publishedButton) {
      publishedButton.addEventListener('click', function () {
        if (publishedButton.disabled) return;      // guards the double click

        // No text and no timestamp: the moment is the server's to decide.
        sendPostAction(post, { action: 'mark_published' });
      });
    }

    var unpublishButton = post.querySelector('[data-unmark-published]');
    if (unpublishButton) {
      unpublishButton.addEventListener('click', function () {
        if (unpublishButton.disabled) return;      // guards the double click

        /*
         * Asked first, and worded so the limit is unmistakable: this corrects
         * the studio's record and cannot touch anything already on X.
         */
        if (!window.confirm('سيتم إلغاء حالة «تم النشر» لهذا المنشور فقط. لن يتم حذف المنشور من منصة X. هل تريد المتابعة؟')) return;

        sendPostAction(post, { action: 'unmark_published' });
      });
    }

    var unapproveButton = post.querySelector('[data-unapprove]');
    if (unapproveButton) {
      unapproveButton.addEventListener('click', function () {
        if (unapproveButton.disabled) return;      // guards the double click

        // Asked first: this undoes a review decision, and the text is untouched.
        if (!window.confirm('سيتم سحب اعتماد هذا المنشور وإعادته إلى حالة «بانتظار المراجعة». هل تريد المتابعة؟')) return;

        sendPostAction(post, { action: 'unapprove_post' });
      });
    }
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
        if (!area) return;

        area.value = area.defaultValue;
        lock(post);
        status(post);
        count(post);
      });
    });
  }

  // ---------------------------------------------------------------------
  // Creating the week's plan
  // ---------------------------------------------------------------------

  var seedButton = root.querySelector('[data-seed-week]');
  if (seedButton) {
    var seedMessage = root.querySelector('[data-seed-message]');

    // textContent, never innerHTML: these strings are shown as text, and a
    // message must never be able to become markup.
    function say(text) {
      if (seedMessage) seedMessage.textContent = text;
    }

    seedButton.addEventListener('click', function () {
      var token = root.getAttribute('data-csrf') || '';
      if (!token) {
        say('تعذر التحقق من الصفحة. أعد تحميلها ثم حاول مرة أخرى.');
        return;
      }

      seedButton.disabled = true;
      say('جارٍ إنشاء الخطة…');

      var body = new URLSearchParams();
      body.append('action', 'seed_week');
      body.append('csrf', token);

      fetch(SEED_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: body.toString()
      }).then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        }).catch(function () {
          return { ok: false, data: {} };
        });
      }).then(function (result) {
        var code = (result.data && result.data.code) || '';

        /*
         * Both outcomes mean a plan is now there, so the page is reloaded to
         * show what the server holds rather than drawing it from the reply.
         */
        if (code === 'WEEK_CREATED' || code === 'WEEK_ALREADY_EXISTS') {
          window.location.reload();
          return;
        }

        if (code === 'AUTH_REQUIRED') {
          say('انتهت جلسة الإدارة. سجّل الدخول من جديد.');
        } else if (code === 'CSRF_FAILED' || code === 'ORIGIN_REJECTED') {
          say('رُفض الطلب لأسباب أمنية. أعد تحميل الصفحة ثم حاول مرة أخرى.');
        } else if (code === 'STORE_CORRUPT') {
          // Deliberately no retry: the file needs a human before anything else.
          say('ملف الخطة تالف. راجعه على الخادم قبل المحاولة.');
        } else if (code === 'STORE_UNREADABLE') {
          say('تعذر قراءة ملف الخطة. راجع صلاحيات الملف على الخادم.');
        } else if (code === 'WRITE_FAILED') {
          say('تعذر حفظ الخطة على الخادم. حاول مرة أخرى.');
          seedButton.disabled = false;
        } else {
          say('تعذر إنشاء الخطة.');
          seedButton.disabled = false;
        }
      }).catch(function () {
        // A network failure changes nothing on the server, so the button comes
        // back and no local plan is invented in its place.
        say('تعذر الاتصال بالخادم. تحقق من الاتصال ثم حاول مرة أخرى.');
        seedButton.disabled = false;
      });
    });
  }
})();
