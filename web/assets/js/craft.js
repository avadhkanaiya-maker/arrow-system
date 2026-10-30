/* Craft-specific behaviour (the design's own scripts, site.js etc., stay unchanged).
   Products mega menu: each category can have its own featured box (_partials/navigation/mega-feature.twig,
   data-feature-for="<category panel id>"). When a category opens (site.js sets aria-expanded on its button: by click,
   keyboard or its default first category), show that category's box, else the default box with only the extra links (data-feature-default). */
(function () {
  'use strict';
  document.querySelectorAll('[data-feature-group]').forEach(function (group) {
    var boxes = group.querySelectorAll('[data-feature-for], [data-feature-default]');
    if (!group.querySelector('[data-feature-for]')) return; // no category has its own product: nothing to switch
    var show = function (catId) {
      var target = (catId && group.querySelector('[data-feature-for="' + catId + '"]')) || group.querySelector('[data-feature-default]');
      boxes.forEach(function (box) { box.hidden = box !== target; });
    };
    var sync = function () {
      var open = group.querySelector('[data-cat-trigger][aria-expanded="true"]');
      show(open && open.getAttribute('aria-controls'));
    };
    new MutationObserver(sync).observe(group, { subtree: true, attributes: true, attributeFilter: ['aria-expanded'] });
    sync();
  });
})();

/* Media Carousel (industries/blocks/mediaCarousel.twig): arrows and dots scroll the scroll-snap track one slide; the dot
   of the slide in view follows swipes; leaving a slide pauses its video. Without JS: the track still scrolls / swipes. */
(function () {
  'use strict';
  document.querySelectorAll('[data-media-carousel]').forEach(function (box) {
    var track = box.querySelector('[data-mcar-track]');
    var slides = Array.prototype.slice.call(track.children);
    var dots = Array.prototype.slice.call(box.querySelectorAll('[data-mcar-dot]'));
    var controls = box.querySelector('[data-mcar-controls]');
    if (!controls || slides.length < 2) return;
    var current = 0;
    var mark = function (i) { // the dot of slide i; leaving a slide pauses its video
      if (i === current) return;
      slides[current].querySelectorAll('video').forEach(function (v) { v.pause(); });
      current = i;
      dots.forEach(function (d, j) { if (j === i) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
    };
    var go = function (i) {
      i = (i + slides.length) % slides.length;
      track.scrollTo({ left: slides[i].offsetLeft - slides[0].offsetLeft });
      mark(i);
    };
    box.querySelector('[data-mcar-prev]').addEventListener('click', function () { go(current - 1); });
    box.querySelector('[data-mcar-next]').addEventListener('click', function () { go(current + 1); });
    dots.forEach(function (dot, i) { dot.addEventListener('click', function () { go(i); }); });
    var timer;
    track.addEventListener('scroll', function () {
      clearTimeout(timer);
      timer = setTimeout(function () { // a swipe: the slide now in view
        mark(Math.round(track.scrollLeft / (slides[1].offsetLeft - slides[0].offsetLeft)));
      }, 120);
    }, { passive: true });
    controls.hidden = false;
  });
})();

/* Card filter (industries/blocks/cardGrid.twig, "Filter Tabs and Search"; the arrow-material design's js/materials.js):
   a tab per card Subtitle with counts, a search box, pages of data-page-size cards with Previous / Next, a live
   "Showing X–Y of Z" status and an empty state. ?type=, ?q= and ?page= in the address pre-set the view (and follow it).
   Without JS the toolbar and page links stay hidden and every card shows. */
(function () {
  'use strict';
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-filter-grid]').forEach(function (grid) {
    var box = grid.parentNode;
    var toolbar = box.querySelector('[data-card-filter]');
    var cards = Array.prototype.slice.call(grid.children);
    var tabs = Array.prototype.slice.call(toolbar.querySelectorAll('[data-filter]'));
    var search = toolbar.querySelector('[data-filter-search]');
    var status = box.querySelector('[data-filter-status]');
    var empty = box.querySelector('[data-filter-empty]');
    var pager = box.querySelector('[data-pagination]');
    var summary = box.querySelector('[data-page-summary]');
    var pageSize = parseInt(grid.getAttribute('data-page-size'), 10) || 9;
    var current = 'all';
    var page = 1;

    function labelOf(tab) { return tab.firstChild.textContent.trim(); }
    function urlFor(p) {
      var params = [];
      if (current !== 'all') params.push('type=' + encodeURIComponent(current));
      if (search.value.trim()) params.push('q=' + encodeURIComponent(search.value.trim()));
      if (p > 1) params.push('page=' + p);
      return window.location.pathname + (params.length ? '?' + params.join('&') : '');
    }
    function saveToUrl() { try { window.history.replaceState(null, '', urlFor(page)); } catch (e) { /* ignore */ } }

    // Page numbers with gaps: 1 … 4 5 6 … 12 (all of them when there are 7 or fewer); a gap never hides just one page.
    function pageList(pages) {
      if (pages <= 7) return Array.apply(null, { length: pages }).map(function (_, i) { return i + 1; });
      var list = [1], from = Math.max(2, page - 1), to = Math.min(pages - 1, page + 1);
      if (page <= 3) { from = 2; to = 4; }
      if (page >= pages - 2) { from = pages - 3; to = pages - 1; }
      if (from > 2) list.push(from === 3 ? 2 : 'gap');
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
      pager.innerHTML = step('Previous', 'left', page - 1, page === 1, 'prev') + '<ol class="pagination__pages">' + items + '</ol>' +
        step('Next', 'right', page + 1, page === pages, 'next');
      summary.textContent = 'Page ' + page + ' of ' + pages;
    }

    function apply(animate) {
      var q = search.value.trim().toLowerCase();
      var matches = cards.filter(function (card) {
        return (current === 'all' || card.getAttribute('data-group') === current) && (!q || card.getAttribute('data-search').indexOf(q) !== -1);
      });
      var total = matches.length;
      var pages = Math.max(1, Math.ceil(total / pageSize));
      page = Math.min(Math.max(page, 1), pages);
      var start = (page - 1) * pageSize;
      var onPage = matches.slice(start, start + pageSize);
      cards.forEach(function (card) {
        var show = onPage.indexOf(card) !== -1;
        var wasHidden = card.hidden;
        card.hidden = !show;
        card.classList.remove('is-entering');
        if (show && animate && wasHidden && !reduceMotion.matches) { void card.offsetWidth; card.classList.add('is-entering'); }
      });
      tabs.forEach(function (tab) { tab.setAttribute('aria-pressed', String(tab.getAttribute('data-filter') === current)); });
      var active = tabs.filter(function (t) { return t.getAttribute('data-filter') === current; })[0];
      var where = current === 'all' ? '' : ' in ' + labelOf(active);
      var match = q ? ' matching “' + search.value.trim() + '”' : '';
      var range = pages > 1 ? (start + 1) + '–' + (start + onPage.length) + ' of ' : (current === 'all' && !q ? 'all ' : '');
      status.textContent = 'Showing ' + range + total + (total === 1 ? ' result' : ' results') + where + match + '.';
      empty.hidden = total > 0;
      renderPager(pages);
    }

    pager.addEventListener('click', function (e) {
      var link = e.target.closest('[data-page]');
      if (!link) return;
      e.preventDefault();
      page = parseInt(link.getAttribute('data-page'), 10);
      apply(true);
      saveToUrl();
      // back to the top of the list, focus on the first card of the new page
      toolbar.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' });
      var first = cards.filter(function (c) { return !c.hidden; })[0];
      var a = first && first.querySelector('a');
      if (a) a.focus({ preventScroll: true });
    });
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        if (tab.getAttribute('aria-pressed') === 'true') return;
        current = tab.getAttribute('data-filter');
        page = 1;
        apply(true);
        saveToUrl();
      });
    });
    var typing = null;
    search.addEventListener('input', function () {
      page = 1;
      apply(false);
      clearTimeout(typing);
      typing = setTimeout(saveToUrl, 400);
    });
    box.querySelector('[data-filter-reset]').addEventListener('click', function () {
      current = 'all';
      search.value = '';
      page = 1;
      apply(true);
      saveToUrl();
      search.focus();
    });

    var type = /[?&]type=([^&#]+)/.exec(window.location.search);
    var query = /[?&]q=([^&#]+)/.exec(window.location.search);
    var pageParam = /[?&]page=(\d+)/.exec(window.location.search);
    if (type && tabs.some(function (t) { return t.getAttribute('data-filter') === decodeURIComponent(type[1]); })) current = decodeURIComponent(type[1]);
    if (query) search.value = decodeURIComponent(query[1].replace(/\+/g, ' '));
    if (pageParam) page = parseInt(pageParam[1], 10);
    toolbar.hidden = false;
    apply(false);
  });
})();

/* Internal links: a full-page loader (craft.css .page-loader: the page covered, a red spinner in the middle) from the
   click until the next page shows (QA 2026-10-30: menus first, then every internal link). Any same-site page link opened
   in this tab: menus, buttons, cards, links in text …; not new tabs, # links on this page, files (PDF, images,
   documents: they download or open in a viewer, the page stays), phone / email, other sites, or links a script
   already handles (lightboxes, filters: their click is cancelled first). Screen readers hear "Loading page…". The
   browser's Back / Forward (page cache) hides it again; it also goes after 15 s if the navigation was cancelled. */
(function () {
  'use strict';
  var bar = document.createElement('div');
  bar.className = 'page-loader';
  bar.setAttribute('role', 'status');
  bar.innerHTML = '<span class="page-loader__spinner" aria-hidden="true"></span><span class="visually-hidden" data-loader-text></span>';
  document.body.appendChild(bar);
  var text = bar.querySelector('[data-loader-text]');
  var timer;
  var hide = function () { clearTimeout(timer); bar.classList.remove('is-loading'); text.textContent = ''; };

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if ((a.target && a.target !== '_self') || a.hasAttribute('download')) return;
    var url = new URL(a.href, window.location.href);
    if (url.origin !== window.location.origin) return;   // other sites, tel:, mailto:
    if (url.pathname === window.location.pathname && url.search === window.location.search) return;   // this page (#…)
    if (/\.(pdf|zip|docx?|xlsx?|pptx?|csv|jpe?g|png|gif|webp|svg|mp4|webm|mov)$/i.test(url.pathname) || /\/actions\//.test(url.pathname)) return;   // files
    setTimeout(function () {   // after every click listener has run: one may still cancel it (lightbox, filter …)
      if (e.defaultPrevented) return;
      bar.classList.add('is-loading');
      text.textContent = 'Loading page…';
      clearTimeout(timer);
      timer = setTimeout(hide, 15000);
    }, 0);
  });
  window.addEventListener('pageshow', hide);
})();
