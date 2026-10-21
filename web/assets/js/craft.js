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
