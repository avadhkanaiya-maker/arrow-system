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

// Relation fields with a maximum (e.g. the homepage Exhibitions section's chosen exhibitions, max 3): in the "Choose" window,
// once as many items are ticked as the field has places left, the other items are disabled; unticking one enables them again.
// Craft itself lets more be ticked and keeps only some of them on Select (bug report 2026-10-27). Uses Craft's own disable
// (as for the items already chosen), so clicks, the keyboard and "select all" are all held. Max-1 fields keep Craft's
// "a new pick replaces the old one".
(function () {
    if (!window.Craft || !Craft.BaseElementSelectInput || !Craft.BaseElementSelectorModal) return;

    var createModal = Craft.BaseElementSelectInput.prototype.createModal;
    Craft.BaseElementSelectInput.prototype.createModal = function () {
        var input = this, modal = createModal.apply(this, arguments);
        if (input.settings.limit > 1) {
            modal.buiPlacesLeft = function () {   // Replace swaps one of the chosen items
                return input.settings.limit - input.$elements.length + (input._$replaceElement ? 1 : 0);
            };
        }
        return modal;
    };

    function hold(modal) {
        var index = modal && modal.buiPlacesLeft && modal.elementIndex;
        var select = index && index.view && index.view.elementSelect;
        if (!select) return;
        var left = modal.buiPlacesLeft(), ids = modal.buiHeldIds || (modal.buiHeldIds = []), $rows = index.view.getAllElements();
        if (select.$selectedItems.length > left) select.deselectItem(select.$selectedItems.slice(left));   // a shift-click range or "select all"
        if (select.$selectedItems.length >= left) {
            var $rest = $rows.not('.sel, .disabled');
            $rest.each(function () { ids.push($(this).data('id')); });
            if ($rest.length) index.disableElements($rest);
        } else if (ids.length) {
            index.enableElementsById(ids.splice(0));
        }
        // cp.css: no tick in a held row's box (Craft ticks disabled rows as "already chosen")
        $rows.each(function () { $(this).toggleClass('bui-held', ids.indexOf($(this).data('id')) !== -1); });
    }

    Garnish.on(Craft.BaseElementIndex, 'selectionChange updateElements', function (ev) {
        var modal = Garnish.Modal.visibleModal;
        if (modal && modal.elementIndex === ev.target) hold(modal);
    });
    Garnish.on(Craft.BaseElementSelectorModal, 'show', function (ev) { hold(ev.target); });   // reopened after a chosen item was removed
})();
