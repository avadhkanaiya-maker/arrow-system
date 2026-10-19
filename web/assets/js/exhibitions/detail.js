/* ==========================================================================
   Arrow Systems — Exhibition detail page behavior (vanilla JS, no dependencies)
   - Gallery lightbox: native <dialog>, previous/next (wraps), arrow keys, swipe, Escape,
     click outside the photo to close, focus returns to the thumbnail, neighbours preloaded
   - "Meet us at the next show" band hides itself after the show's end date
   Header, menus, search and newsletter are handled by the shared ../js/home.js.
   Without JS each thumbnail opens its full-size photo and the band stays visible.
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- Next show band ---------- */
  var band = document.querySelector('[data-next-band]');
  if (band && band.getAttribute('data-end')) {
    var p = band.getAttribute('data-end').split('-');
    var end = new Date(+p[0], +p[1] - 1, +p[2]);
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    if (end < today) band.hidden = true; // TODO(client): show the following show instead
  }

  /* ---------- Gallery lightbox ---------- */
  var dialog = document.querySelector('[data-lightbox]');
  var links = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-index]'));
  if (!dialog || !links.length || typeof dialog.showModal !== 'function') return;

  var root = document.documentElement;
  var img = dialog.querySelector('[data-lightbox-img]');
  var altText = dialog.querySelector('[data-lightbox-alt]');
  var count = dialog.querySelector('[data-lightbox-count]');
  var items = links.map(function (a) {
    var thumb = a.querySelector('img');
    return { src: a.getAttribute('href'), alt: thumb ? thumb.alt : '' };
  });
  var current = 0;

  function preload(i) {
    var item = items[(i + items.length) % items.length];
    var pre = new Image();
    pre.src = item.src;
  }

  function show(i) {
    current = (i + items.length) % items.length;
    var item = items[current];
    img.classList.add('is-loading');
    img.onload = function () { img.classList.remove('is-loading'); };
    img.src = item.src;
    img.alt = item.alt;
    altText.textContent = item.alt;
    count.textContent = (current + 1) + ' / ' + items.length;
    preload(current + 1);
    preload(current - 1);
  }

  function open(i) {
    show(i);
    root.classList.add('lightbox-open');
    dialog.showModal();
  }

  links.forEach(function (a, i) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      open(i);
    });
  });

  dialog.querySelector('[data-lightbox-prev]').addEventListener('click', function () { show(current - 1); });
  dialog.querySelector('[data-lightbox-next]').addEventListener('click', function () { show(current + 1); });
  dialog.querySelector('[data-lightbox-close]').addEventListener('click', function () { dialog.close(); });

  dialog.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft') { show(current - 1); e.preventDefault(); }
    if (e.key === 'ArrowRight') { show(current + 1); e.preventDefault(); }
  });

  // Click on the dark area (not the photo, caption or buttons) closes the viewer.
  dialog.addEventListener('click', function (e) {
    if (e.target === dialog || e.target.classList.contains('lightbox__frame')) dialog.close();
  });

  // Swipe left/right on touch screens.
  var touchX = null;
  dialog.addEventListener('touchstart', function (e) { touchX = e.changedTouches[0].clientX; }, { passive: true });
  dialog.addEventListener('touchend', function (e) {
    if (touchX === null) return;
    var dx = e.changedTouches[0].clientX - touchX;
    if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1));
    touchX = null;
  });

  // Escape (native) or close: unlock the page and return focus to the photo being viewed.
  dialog.addEventListener('close', function () {
    root.classList.remove('lightbox-open');
    links[current].focus();
  });
})();
