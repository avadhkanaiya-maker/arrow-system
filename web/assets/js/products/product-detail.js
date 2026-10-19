/* ==========================================================================
   Arrow Systems — Product detail behavior (vanilla JS, no dependencies)
   - Tabs (ARIA tabs pattern): Overview / Features / Brochure. Arrow keys, Home and End move between tabs.
     Links with data-open-tab="tab-id" (e.g. "Download Brochure") open that tab and scroll to it; #features etc. work too.
   - Gallery: thumbnails swap the large image; the video thumbnail shows and starts the video (stops it when you leave).
   - What You Can Print: prev/next arrows scroll the card row; hidden when every card fits.
   "Request a Sample" links to ../try-a-free-sample/ (the form lives there).
   Video (click-to-play), header, menus, search and newsletter are handled by the shared ../js/home.js.
   Without JS: the tab list and thumbnails stay hidden, every panel and gallery slide is shown in order, the card row scrolls.
   ========================================================================== */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  function toArray(list) { return Array.prototype.slice.call(list); }
  var selectByPanel = {}; // panel id → function that shows it (returns the tab set)
  var selectByTab = {};   // tab id → same

  /* ---------- Tabs ---------- */
  toArray(document.querySelectorAll('[data-tabs]')).forEach(function (box) {
    var list = box.querySelector('[data-tablist]');
    if (!list) return;
    var tabs = toArray(list.querySelectorAll('[role="tab"]'));
    var panels = tabs.map(function (t) { return document.getElementById(t.getAttribute('aria-controls')); });

    function select(tab, opts) {
      opts = opts || {};
      tabs.forEach(function (t, i) {
        var on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        var wasHidden = panels[i].hidden;
        panels[i].hidden = !on;
        panels[i].classList.remove('is-entering');
        if (on && wasHidden && opts.animate && !reduceMotion.matches) { void panels[i].offsetWidth; panels[i].classList.add('is-entering'); }
      });
      if (opts.focus) tab.focus();
      if (list.scrollWidth > list.clientWidth) { // keep the chosen tab visible in a scrolling tab row (phones)
        var l = tab.offsetLeft - list.offsetLeft, r = l + tab.offsetWidth;
        if (l < list.scrollLeft) list.scrollLeft = l - 8;
        else if (r > list.scrollLeft + list.clientWidth) list.scrollLeft = r - list.clientWidth + 8;
      }
    }

    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { select(tab, { animate: true }); });
      selectByPanel[panels[i].id] = selectByTab[tab.id] = function () { select(tab, { animate: true }); return box; };
    });
    list.addEventListener('keydown', function (e) {
      var i = tabs.indexOf(document.activeElement);
      if (i === -1) return;
      var next = null;
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = tabs[(i + 1) % tabs.length];
      else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = tabs[(i - 1 + tabs.length) % tabs.length];
      else if (e.key === 'Home') next = tabs[0];
      else if (e.key === 'End') next = tabs[tabs.length - 1];
      if (!next) return;
      e.preventDefault();
      select(next, { focus: true, animate: true });
    });

    list.hidden = false;
    select(tabs.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0] || tabs[0]);
  });

  function scrollToBox(box) { box.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' }); }

  // "Download Brochure" and similar links open their tab.
  document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-open-tab]');
    var show = link && selectByTab[link.getAttribute('data-open-tab')];
    if (!show) return;
    e.preventDefault();
    scrollToBox(show());
  });
  function openFromHash() {
    var show = selectByPanel[window.location.hash.slice(1)];
    if (show) scrollToBox(show());
  }
  window.addEventListener('hashchange', openFromHash);
  if (window.location.hash) openFromHash();

  /* ---------- Gallery: thumbnails swap the stage; the video thumbnail starts the video ---------- */
  var gallery = document.querySelector('[data-gallery]');
  if (gallery) {
    var thumbs = toArray(gallery.querySelectorAll('[data-gallery-thumb]'));
    var slides = thumbs.map(function (t) { return document.getElementById(t.getAttribute('aria-controls')); });
    var showSlide = function (i) {
      thumbs.forEach(function (t, j) {
        var iframe = slides[j].querySelector('iframe');
        if (j !== i && iframe && !slides[j].hidden) iframe.src = iframe.src.replace('autoplay=1', 'autoplay=0'); // stop a playing video
        t.setAttribute('aria-pressed', String(j === i));
        slides[j].hidden = j !== i;
      });
    };
    thumbs.forEach(function (t, i) {
      t.addEventListener('click', function () {
        showSlide(i);
        var play = slides[i].querySelector('[data-video-play]');
        if (play) play.click(); // home.js swaps the poster for the YouTube player
      });
    });
    gallery.querySelector('[data-gallery-thumbs]').hidden = false;
    showSlide(0);
  }

  /* ---------- What You Can Print: arrows scroll the card row by one card ---------- */
  var track = document.querySelector('[data-apps-track]');
  var nav = document.querySelector('[data-apps-nav]');
  if (track && nav) {
    var prev = nav.querySelector('[data-apps-prev]'), next = nav.querySelector('[data-apps-next]');
    var step = function () { return track.firstElementChild.offsetWidth + parseFloat(getComputedStyle(track).columnGap || 0); };
    var syncNav = function () {
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
      nav.hidden = prev.disabled && next.disabled; // every card fits: no arrows
    };
    prev.addEventListener('click', function () { track.scrollBy({ left: -step() }); });
    next.addEventListener('click', function () { track.scrollBy({ left: step() }); });
    track.addEventListener('scroll', syncNav, { passive: true });
    window.addEventListener('resize', syncNav);
    syncNav();
  }
})();
