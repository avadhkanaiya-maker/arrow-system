/* ==========================================================================
   Arrow Systems — Answers listing behavior (vanilla JS, no dependencies)
   - Search by answer title or summary, with a live "Showing X–Y of Z answers" status and an empty state
   - Pagination of the (filtered) list (page size: data-page-size on the grid); a new search returns to page 1
   - ?q=flexo and ?page=2 in the URL pre-set the view (so it can be linked to)
   Works for any number of answers. Header, menus, search and newsletter: ../js/home.js.
   Without JS the search box and pagination stay hidden and every answer is listed.
   ========================================================================== */
(function () {
  'use strict';

  var toolbar = document.querySelector('[data-toolbar]');
  var grid = document.querySelector('[data-grid]');
  if (!toolbar || !grid) return;

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-search]'));
  var search = toolbar.querySelector('[data-search]');
  var status = document.querySelector('[data-status]');
  var empty = document.querySelector('[data-empty]');
  var pager = document.querySelector('[data-pagination]');
  var summary = document.querySelector('[data-page-summary]');
  var pageSize = parseInt(grid.getAttribute('data-page-size'), 10) || 9;
  var page = 1;

  // The count in the page header follows the number of answer cards.
  var count = document.querySelector('[data-answer-count]');
  if (count) count.textContent = cards.length;

  /* ---------- URL ---------- */
  function urlFor(p) {
    var params = [];
    if (search.value.trim()) params.push('q=' + encodeURIComponent(search.value.trim()));
    if (p > 1) params.push('page=' + p);
    return window.location.pathname + (params.length ? '?' + params.join('&') : '') + window.location.hash;
  }
  function saveToUrl() {
    if (!window.history || !window.history.replaceState) return;
    try { window.history.replaceState(null, '', urlFor(page)); } catch (e) { /* file:// */ }
  }

  /* ---------- Pagination ---------- */
  // Page numbers with gaps: 1 … 4 5 6 … 12 (all of them when there are 7 or fewer).
  function pageList(pages) {
    if (pages <= 7) return Array.apply(null, { length: pages }).map(function (_, i) { return i + 1; });
    var list = [1], from = Math.max(2, page - 1), to = Math.min(pages - 1, page + 1);
    if (page <= 3) { from = 2; to = 4; }
    if (page >= pages - 2) { from = pages - 3; to = pages - 1; }
    if (from > 2) list.push(from === 3 ? 2 : 'gap'); // a gap never hides just one page
    for (var i = from; i <= to; i++) list.push(i);
    if (to < pages - 1) list.push(to === pages - 2 ? pages - 1 : 'gap');
    list.push(pages);
    return list;
  }
  function step(label, icon, target, disabled, rel) {
    var svg = '<svg class="icon" aria-hidden="true"><use href="#i-chevron-' + icon + '"/></svg>';
    var text = icon === 'left' ? svg + label : label + svg;
    return disabled
      ? '<span class="pagination__step is-disabled" aria-disabled="true">' + text + '</span>'
      : '<a class="pagination__step" href="' + urlFor(target) + '" rel="' + rel + '" data-page="' + target + '">' + text + '</a>';
  }
  function renderPager(pages) {
    pager.hidden = summary.hidden = pages <= 1;
    if (pages <= 1) { pager.innerHTML = ''; return; }
    var items = pageList(pages).map(function (p) {
      if (p === 'gap') return '<li class="pagination__gap" aria-hidden="true">&hellip;</li>';
      var cur = p === page ? ' aria-current="page"' : '';
      return '<li><a class="pagination__page" href="' + urlFor(p) + '" data-page="' + p + '"' + cur + '><span class="visually-hidden">Page </span>' + p + '</a></li>';
    }).join('');
    pager.innerHTML = step('Previous', 'left', page - 1, page === 1, 'prev') +
      '<ol class="pagination__pages">' + items + '</ol>' +
      step('Next', 'right', page + 1, page === pages, 'next');
    summary.textContent = 'Page ' + page + ' of ' + pages;
  }

  /* ---------- Filter + paginate ---------- */
  function apply(animate) {
    var q = search.value.trim().toLowerCase();
    var matches = cards.filter(function (card) { return !q || card.getAttribute('data-search').indexOf(q) !== -1; });
    var total = matches.length;
    var pages = Math.max(1, Math.ceil(total / pageSize));
    if (page > pages) page = pages;
    if (page < 1) page = 1;
    var start = (page - 1) * pageSize;
    var onPage = matches.slice(start, start + pageSize);

    cards.forEach(function (card) {
      var show = onPage.indexOf(card) !== -1;
      var wasHidden = card.hidden;
      card.hidden = !show;
      card.classList.remove('is-entering');
      if (show && animate && wasHidden && !reduceMotion.matches) { void card.offsetWidth; card.classList.add('is-entering'); }
    });

    var noun = total === 1 ? 'answer' : 'answers';
    var match = q ? ' matching “' + search.value.trim() + '”' : '';
    var range = pages > 1 ? (start + 1) + '–' + (start + onPage.length) + ' of ' : '';
    status.textContent = q || pages > 1 ? 'Showing ' + range + total + ' ' + noun + match + '.' : '';
    empty.hidden = total > 0;
    renderPager(pages);
  }

  function goToPage(p) {
    page = p;
    apply(true);
    saveToUrl();
    // Back to the top of the list, and move focus to the first answer on the new page.
    document.getElementById('answers').scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' });
    var first = cards.filter(function (c) { return !c.hidden; })[0];
    var link = first && first.querySelector('a');
    if (link) link.focus({ preventScroll: true });
  }

  pager.addEventListener('click', function (e) {
    var link = e.target.closest('[data-page]');
    if (!link) return;
    e.preventDefault();
    goToPage(parseInt(link.getAttribute('data-page'), 10));
  });

  var typingTimer = null;
  search.addEventListener('input', function () {
    page = 1;
    apply(true);
    clearTimeout(typingTimer);
    typingTimer = setTimeout(saveToUrl, 400);
  });
  document.querySelector('[data-reset]').addEventListener('click', function () {
    search.value = '';
    page = 1;
    apply(true);
    saveToUrl();
    search.focus();
  });

  var query = /[?&]q=([^&#]+)/.exec(window.location.search);
  var pageParam = /[?&]page=(\d+)/.exec(window.location.search);
  if (query) search.value = decodeURIComponent(query[1].replace(/\+/g, ' '));
  if (pageParam) page = parseInt(pageParam[1], 10);
  toolbar.hidden = false;
  apply(false);
})();
