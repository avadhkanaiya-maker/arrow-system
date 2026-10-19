/* ==========================================================================
   Arrow Systems — Blog post behavior (vanilla JS, no dependencies)
   - Reading progress bar across the top of the window
   - "On this page": highlights the section being read; collapsed on phones (closes after a jump),
     always open on desktop
   Header, menus, search and newsletter are handled by the shared ../js/home.js; "New" chips by js/blog.js.
   Without JS the article and contents list still work.
   ========================================================================== */
(function () {
  'use strict';

  var desktopMQ = window.matchMedia('(min-width: 64em)');
  var article = document.querySelector('[data-article]');
  var bar = document.querySelector('[data-read-progress]');
  var toc = document.querySelector('[data-toc]');
  var links = toc ? Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]')) : [];
  var targets = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
  // A section counts as "current" once its heading reaches the spot a contents-link jump scrolls it to:
  // the page's scroll-padding (sticky header) + the heading's scroll-margin, plus a small tolerance.
  function jumpOffset() {
    var pad = parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop) || 0;
    var margin = targets[0] ? parseFloat(getComputedStyle(targets[0]).scrollMarginTop) || 0 : 0;
    return pad + margin + 24;
  }
  var offset = jumpOffset();
  // After a click, keep the clicked link highlighted while the page scrolls to it (and if the section
  // sits too close to the end of the page to reach the top), until the visitor scrolls again.
  var clicked = null, settled = false, settleTimer = null;

  /* ---------- Reading progress + current section ---------- */
  var active = null;
  function update() {
    if (article && bar) {
      var rect = article.getBoundingClientRect();
      var total = rect.height - window.innerHeight;
      var p = total > 0 ? Math.min(1, Math.max(0, -rect.top / total)) : 1;
      bar.parentNode.style.setProperty('--progress', p.toFixed(4));
    }
    if (links.length) {
      var current = null;
      if (clicked) {
        current = clicked;
      } else {
        targets.forEach(function (el, i) {
          if (el && el.getBoundingClientRect().top - offset <= 0) current = links[i];
        });
      }
      if (current !== active) {
        if (active) { active.classList.remove('is-active'); active.removeAttribute('aria-current'); }
        if (current) { current.classList.add('is-active'); current.setAttribute('aria-current', 'location'); }
        active = current;
      }
    }
  }
  var ticking = false;
  window.addEventListener('scroll', function () {
    if (clicked) {
      if (settled) { clicked = null; settled = false; } // the visitor scrolled on their own: follow the page again
      else {
        clearTimeout(settleTimer);
        settleTimer = setTimeout(function () { settled = true; }, 180); // the jump has finished
      }
    }
    if (!ticking) { ticking = true; window.requestAnimationFrame(function () { update(); ticking = false; }); }
  }, { passive: true });
  window.addEventListener('resize', function () { offset = jumpOffset(); update(); });
  update();

  /* ---------- Contents list: open on desktop, collapsed on phones ---------- */
  if (toc) {
    var syncToc = function () { toc.open = desktopMQ.matches; };
    syncToc();
    if (desktopMQ.addEventListener) desktopMQ.addEventListener('change', syncToc);
    else if (desktopMQ.addListener) desktopMQ.addListener(syncToc);

    // On desktop the list is always shown, so the heading is not a toggle.
    toc.querySelector('summary').addEventListener('click', function (e) {
      if (desktopMQ.matches) e.preventDefault();
    });
    links.forEach(function (a) {
      a.addEventListener('click', function () {
        clicked = a;
        settled = false;
        clearTimeout(settleTimer);
        settleTimer = setTimeout(function () { settled = true; }, 600); // also covers a jump that needs no scrolling
        update();
        if (!desktopMQ.matches) toc.open = false;
      });
    });
  }
})();
