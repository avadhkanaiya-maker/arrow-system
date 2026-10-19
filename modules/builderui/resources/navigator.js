// Page-builder navigator (like Elementor's Navigator) for the blog's Article Body and the product / industry Page Builders:
// - every top-level block starts collapsed on page load (new blocks stay open for typing)
// - a sticky "Outline" panel in the right sidebar: one row per block (icon, type, Craft's own preview text), H2 Heading
//   blocks as section markers with the blocks under them indented, a search box, Collapse all / Expand all;
//   clicking a row opens that block, scrolls to it and highlights it. The list follows adds, deletes, moves and typing.
// Uses Craft's Matrix API: each .matrixblock has its Craft.MatrixInput.Entry in jQuery data('entry').
(function () {
    var FIELD_IDS = ['fields-articleBody-field', 'fields-productBuilder-field', 'fields-industryBuilder-field', 'fields-pageBuilder-field'];   // one per entry page

    function topBlocks(field) {
        var blocks = field.querySelector('.blocks');   // the first .blocks is the field's own (nested Matrix fields are inside blocks)
        return blocks ? Array.prototype.filter.call(blocks.children, function (el) { return el.classList.contains('matrixblock'); }) : [];
    }
    function entryOf(el) { return window.jQuery ? jQuery(el).data('entry') : null; }
    function textOf(html) { var d = document.createElement('div'); d.innerHTML = (html || '').replace(/</g, ' <'); return d.textContent.replace(/\s+/g, ' ').trim(); }   // the space keeps "Heading" and "Paragraph" apart
    // preview: the block's first filled text field (its own, not a nested block's), else Craft's preview (e.g. image titles)
    function previewOf(el) {
        var inputs = el.querySelectorAll('input[type="text"], textarea');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].closest('.matrixblock') !== el || !inputs[i].value.trim()) continue;
            return textOf(inputs[i].value);
        }
        var e = entryOf(el);
        return e ? textOf(e.previewHtml()) : '';
    }
    function headingLevel(el) {   // Craft encodes dropdown values (BASE64:…), so read the chosen option's label ("H2: section")
        if (el.dataset.type !== 'heading') return null;
        var select = el.querySelector('select[name$="[headingLevel]"]');
        var m = select && select.selectedIndex >= 0 ? /^\s*H([1-6])/i.exec(select.options[select.selectedIndex].text) : null;
        return m ? 'h' + m[1] : 'h2';
    }

    function init(field) {
        var blocks = topBlocks(field);
        if (!blocks.length || !blocks.every(entryOf)) return false;   // Craft hasn't set up the blocks yet
        var sidebar = document.querySelector('#details .details');
        if (!sidebar) return true;

        // 1. collapsed by default (saved blocks only; Craft's own collapse, no animation)
        blocks.forEach(function (el) { var e = entryOf(el); if (e && !e.isNew && !e.collapsed) e.collapse(false); });

        // 2. the outline panel
        var panel = document.createElement('section');
        panel.className = 'bui-nav';
        panel.setAttribute('aria-label', 'Page outline');
        panel.innerHTML =
            '<div class="bui-nav__head"><h2 class="bui-nav__title">Outline</h2><span class="bui-nav__count"></span></div>' +
            '<input class="text fullwidth bui-nav__search" type="search" placeholder="Find a block…" aria-label="Find a block">' +
            '<div class="bui-nav__tools"><button type="button" class="btn small" data-bui="collapse">Collapse all</button>' +
            '<button type="button" class="btn small" data-bui="expand">Expand all</button></div>' +
            '<ol class="bui-nav__list"></ol>';
        sidebar.insertBefore(panel, sidebar.firstChild);   // first in the sidebar: fully on screen without scrolling
        var list = panel.querySelector('.bui-nav__list');
        var search = panel.querySelector('.bui-nav__search');

        function render() {
            var q = search.value.trim().toLowerCase();
            var all = topBlocks(field);
            var inSection = false;
            list.innerHTML = '';
            all.forEach(function (el, i) {
                var e = entryOf(el);
                var level = headingLevel(el);
                if (level === 'h2') inSection = true;
                var type = (el.querySelector('.titlebar .blocktype') || {}).textContent || el.dataset.type || '';
                var preview = previewOf(el);
                var label = (type.trim() + ' ' + preview).toLowerCase();
                if (q && label.indexOf(q) === -1) return;
                var li = document.createElement('li');
                li.className = 'bui-nav__item' + (level === 'h2' ? ' bui-nav__item--h2' : (inSection ? ' bui-nav__item--nested' : '')) +
                    (el.classList.contains('collapsed') ? '' : ' is-open') + (el.classList.contains('bui-nav-current') ? ' is-current' : '');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bui-nav__link';
                var icon = el.querySelector('.titlebar .cp-icon');
                btn.innerHTML = (icon ? icon.outerHTML : '') + '<span class="bui-nav__type"></span><span class="bui-nav__text"></span>';
                btn.querySelector('.bui-nav__type').textContent = level ? level.toUpperCase() : type.trim();
                btn.querySelector('.bui-nav__text').textContent = preview;
                btn.title = (i + 1) + '. ' + type.trim() + (preview ? ': ' + preview : '');
                btn.addEventListener('click', function () { go(el); });
                li.appendChild(btn);
                list.appendChild(li);
            });
            panel.querySelector('.bui-nav__count').textContent = all.length + ' blocks';
        }

        function showTab() {   // product / industry builders sit on a "Page Builder" tab: open it first
            var pane = field.closest('[id^="tab"]');
            var tab = pane && pane.classList.contains('hidden') ? document.querySelector('[role="tab"][aria-controls="' + pane.id + '"]') : null;
            if (tab) tab.click();
        }

        function go(el) {
            showTab();
            var e = entryOf(el);
            if (e && e.collapsed) e.expand();
            topBlocks(field).forEach(function (b) { b.classList.remove('bui-nav-current'); });
            el.classList.add('bui-nav-current');
            var header = document.getElementById('header');   // Craft's sticky top bar
            window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - (header ? header.offsetHeight : 0) - 16, behavior: 'smooth' });
            render();
            setTimeout(function () { el.classList.remove('bui-nav-current'); render(); }, 2000);
        }

        panel.addEventListener('click', function (ev) {
            var tool = ev.target.closest('[data-bui]');
            if (!tool) return;
            if (tool.dataset.bui === 'expand') showTab();
            topBlocks(field).forEach(function (el) {
                var e = entryOf(el);
                if (!e) return;
                if (tool.dataset.bui === 'collapse' && !e.collapsed) e.collapse(true);
                if (tool.dataset.bui === 'expand' && e.collapsed) e.expand();
            });
            setTimeout(render, 250);
        });
        search.addEventListener('input', render);

        // keep the outline in step with the editor: blocks added / removed / moved / collapsed, text typed
        var timer;
        function later() { clearTimeout(timer); timer = setTimeout(render, 300); }
        new MutationObserver(later).observe(field, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
        field.addEventListener('input', later);
        field.addEventListener('change', later);

        // the outline (first in Craft's sticky sidebar) reaches the window bottom; only its list scrolls, the sidebar has no
        // scrollbar of its own (the details below the outline come into view by scrolling the page)
        var box = document.getElementById('details-container');
        function fit() {
            if (!box) return;
            var stickyTop = parseFloat(getComputedStyle(box).top) || 0;
            panel.style.height = Math.max(320, window.innerHeight - Math.max(stickyTop, panel.getBoundingClientRect().top) - 16) + 'px';
        }
        window.addEventListener('resize', fit);
        window.addEventListener('scroll', fit, { passive: true });

        render();
        fit();
        return true;
    }

    // Craft sets up the blocks one by one (a 121-block post takes ~10 s): until then the blocks are shown as their title
    // bars only (.bui-precollapse), then Craft's real collapse takes over. Gives up after 2 minutes and shows everything.
    var field = FIELD_IDS.map(function (id) { return document.getElementById(id); }).filter(Boolean)[0];
    if (!field) return;
    field.classList.add('bui-precollapse');
    var started = Date.now();
    (function wait() {
        if (init(field) || Date.now() - started > 120000) { field.classList.remove('bui-precollapse'); return; }
        setTimeout(wait, 200);
    })();
})();
