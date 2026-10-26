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
