/**
 * -------------------------------------------------------------------------
 * activity plugin for GLPI
 * Copyright (C) 2019-2026 by the activity Development Team.
 *
 * https://github.com/InfotelGLPI/activity
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of activity.
 *
 * activity is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * activity is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with activity. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

/*
 * Subcategory selector of the planning external event form
 * (planningexternalevent_post_item_form.html.twig). The plugin renders it after the core
 * fields, hidden in [data-activity-subcategory]; it is moved into a row of its own right
 * after the category row, with the label carried by the container.
 *
 * The form can be loaded after page load (planning modal), hence the MutationObserver.
 */

const SELECTOR = '[data-activity-subcategory]';

const move = (container) => {
    const form = container.closest('form') ?? document;
    const category = form.querySelector('[name="planningeventcategories_id"]');
    const field = container.firstElementChild;
    container.removeAttribute('data-activity-subcategory');
    if (!category || !field) {
        return;
    }
    const category_row = category.closest('.form-field');
    if (!category_row) {
        return;
    }

    const row = document.createElement('div');
    row.classList.add('form-field', 'row', 'align-items-center', 'col-12', 'mb-2');

    const label = document.createElement('label');
    label.classList.add('col-form-label', 'col-2', 'text-xxl-end');
    label.textContent = container.dataset.activitySubcategoryLabel ?? '';
    label.htmlFor = container.dataset.activitySubcategoryFieldId ?? '';

    const cell = document.createElement('div');
    cell.classList.add('col-10', 'field-container');
    cell.append(field);

    row.append(label, cell);
    category_row.after(row);
    container.remove();
};

const collect = (root) => {
    if (root.matches?.(SELECTOR)) {
        move(root);
    }
    root.querySelectorAll?.(SELECTOR).forEach(move);
};

collect(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                collect(node);
            }
        });
    });
}).observe(document.documentElement, {childList: true, subtree: true});
