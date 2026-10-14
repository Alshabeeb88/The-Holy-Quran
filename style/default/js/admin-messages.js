// Contact messages inbox: "select all" and a confirmation before deleting.
(function () {
  var form = document.getElementById('messages-form');
  if (!form) return;
  var all = document.getElementById('messages-select-all');
  var bulk = form.querySelector('[data-needs-selection]');

  function boxes() {
    return document.querySelectorAll('input[name="ids[]"][form="messages-form"]');
  }

  function sync() {
    var list = boxes(), picked = 0;
    for (var i = 0; i < list.length; i++) if (list[i].checked) picked++;
    if (all) {
      all.checked = list.length > 0 && picked === list.length;
      all.indeterminate = picked > 0 && picked < list.length;
    }
    if (bulk) bulk.disabled = picked === 0;
  }

  if (all) {
    all.addEventListener('change', function () {
      var list = boxes();
      for (var i = 0; i < list.length; i++) list[i].checked = all.checked;
      sync();
    });
  }

  document.addEventListener('change', function (event) {
    if (event.target && event.target.name === 'ids[]') sync();
  });

  form.addEventListener('submit', function (event) {
    var button = event.submitter;
    var single = button && button.name === 'id';
    // A row's own delete button must not also carry the ticked boxes.
    if (single) {
      var list = boxes();
      for (var i = 0; i < list.length; i++) list[i].disabled = true;
    }
    var question = button && button.getAttribute('data-confirm');
    if (question && !window.confirm(question)) {
      event.preventDefault();
      if (single) {
        var again = boxes();
        for (var j = 0; j < again.length; j++) again[j].disabled = false;
      }
    }
  });

  sync();
})();
