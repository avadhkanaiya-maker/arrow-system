/* ==========================================================================
   Arrow Systems — Blog listing behavior (vanilla JS, no dependencies)
   - "New" chip on articles published in the last few days (from each card's data-date;
     the featured article already carries "Latest article")
   - Category filter: search box (filters as you type), focus on open, Escape / outside click to close
   Header, menus, search and newsletter are handled by the shared ../js/home.js.
   Without JS the page is complete: the category panel still opens (native <details>) with the full list;
   only the category search and the "New" chips are missing.
   ========================================================================== */
(function () {
  'use strict';

  var NEW_DAYS = 3; // published today or in the previous 2 days
  var DAY = 864e5;
  var now = new Date();
  var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  Array.prototype.slice.call(document.querySelectorAll('.post-tile:not(.post-tile--feature)[data-date]')).forEach(function (card) {
    var p = card.getAttribute('data-date').split('-');
    var published = new Date(+p[0], +p[1] - 1, +p[2]);
    var age = Math.round((today - published) / DAY);
    if (age < 0 || age >= NEW_DAYS) return;

    var meta = card.querySelector('.post-tile__meta');
    if (!meta) return;
    var chip = document.createElement('span');
    chip.className = 'post-tile__new';
    chip.textContent = 'New';
    meta.insertBefore(chip, meta.firstChild);
  });

  /* ---------- Category filter ---------- */
  var filter = document.querySelector('[data-cat-filter]');
  if (filter) {
    var toggle = filter.querySelector('summary');
    var searchBox = filter.querySelector('[data-cat-search]');
    var input = filter.querySelector('[data-cat-search-input]');
    var list = filter.querySelector('[data-cat-list]');
    var items = Array.prototype.slice.call(list.querySelectorAll('[data-cat-name]'));
    var popular = filter.querySelector('[data-cat-popular]');
    var listHeading = filter.querySelector('[data-cat-list-heading]');
    var empty = filter.querySelector('[data-cat-empty]');

    searchBox.hidden = false;

    var applySearch = function () {
      var q = input.value.trim().toLowerCase();
      var shown = 0;
      items.forEach(function (li) {
        var match = !q || li.getAttribute('data-cat-name').indexOf(q) !== -1;
        li.hidden = !match;
        if (match) shown++;
      });
      list.classList.toggle('is-searching', !!q);
      popular.hidden = !!q;
      listHeading.hidden = !!q;
      empty.hidden = shown > 0;
      if (!shown) empty.textContent = 'No categories match \u201c' + input.value.trim() + '\u201d.';
    };
    input.addEventListener('input', applySearch);

    var close = function (returnFocus) {
      filter.open = false;
      if (returnFocus) toggle.focus();
    };

    filter.addEventListener('toggle', function () {
      if (filter.open) {
        input.focus();
      } else if (input.value) {
        input.value = '';
        applySearch();
      }
    });
    filter.addEventListener('keydown', function (e) {
      if ((e.key === 'Escape' || e.key === 'Esc') && filter.open) { e.preventDefault(); close(true); }
    });
    document.addEventListener('click', function (e) {
      if (filter.open && !filter.contains(e.target)) close(false);
    });
  }
})();
