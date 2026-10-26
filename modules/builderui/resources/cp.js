// Marks the "Add block" menus ("All elements" and each group's) of the page builders (Matrix: the blog's Article Body, the products', industries' and pages' Page Builders) with
// .bui-grid, so cp.css shows them as an icon grid.
// The menus already have Craft's search box; when one opens, the cursor goes into it.
(function () {
    var FIELD = '#fields-articleBody-field, #fields-productBuilder-field, #fields-industryBuilder-field, #fields-pageBuilder-field';

    function mark(root) {
        if (root.nodeType !== 1) return;
        root.querySelectorAll('button[aria-controls]').forEach(function (btn) {
            if (!btn.closest(FIELD) || btn.closest('.matrixblock .matrixblock')) return;
            var menu = document.getElementById(btn.getAttribute('aria-controls'));
            if (menu && menu.classList.contains('menu')) menu.classList.add('bui-grid');
            // Craft's full menu (every group): labelled "All elements" next to the group buttons (cp.css shows both)
            var label = btn.parentNode.classList.contains('expandable-button--collapsed') && btn.querySelector('.label');
            if (label && label.textContent !== 'All elements') label.textContent = 'All elements';
        });
    }

    mark(document.body);
    new MutationObserver(function (records) {
        records.forEach(function (r) { r.addedNodes.forEach(mark); });
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('button[aria-controls]');
        if (!btn || !btn.closest(FIELD)) return;
        mark(btn.parentNode);
        setTimeout(function () {
            var menu = document.getElementById(btn.getAttribute('aria-controls'));
            var input = menu && menu.classList.contains('bui-grid') && menu.querySelector('.search-container input');
            if (input) input.focus();
        }, 60);
    });
})();

// Form fields (Formie "Forms" field, e.g. the page builder's Form block): an "Edit form fields" link under the chosen form,
// opening Formie's form builder (fields, emails, thank-you message) in a new tab. Follows adds / removes of the form.
(function () {
    function sync(field) {
        var chip = field.querySelector('.elementselect .element[data-id], .elementselect .chip[data-id]');
        var link = field.querySelector('.bui-form-edit');
        var id = chip && chip.getAttribute('data-id');
        if (!id) { if (link) link.remove(); return; }
        if (link && link.getAttribute('data-id') === id) return;
        if (!link) {
            link = document.createElement('a');
            link.className = 'btn small bui-form-edit';
            link.target = '_blank';
            link.rel = 'noopener';
            field.querySelector('.input').appendChild(link);
        }
        link.setAttribute('data-id', id);
        link.href = Craft.getCpUrl('formie/forms/edit/' + id);
        link.innerHTML = 'Edit form fields <span aria-hidden="true">↗</span><span class="visually-hidden"> (opens in a new tab)</span>';
    }
    function scan() {
        document.querySelectorAll('.field[data-type]').forEach(function (field) {
            if (/formie\\fields\\Forms$/.test(field.getAttribute('data-type'))) sync(field);
        });
    }
    var timer;
    new MutationObserver(function () { clearTimeout(timer); timer = setTimeout(scan, 200); }).observe(document.body, { childList: true, subtree: true });
    scan();
})();
