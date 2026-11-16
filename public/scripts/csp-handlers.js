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

/* global checkAsCheckboxes */

/*
 * Behaviours formerly held by inline handlers, blocked by the Content Security Policy of GLPI.
 * Delegated listeners, bound once: no-op on the pages without the attributes below.
 *
 * - data-activity-confirm="<message>": the click goes on only once confirmed (was
 *   onclick="return confirm(...)"). Capture phase, so that a cancelled click reaches no other
 *   listener and submits no form.
 * - data-activity-check-all="<form id>": "check all" box of the plugin setup
 *   (config_form.html.twig), checks or clears every checkbox of the form.
 */

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }
    const element = event.target.closest('[data-activity-confirm]');
    if (element !== null && !window.confirm(element.dataset.activityConfirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }
    const checkbox = event.target.closest('[data-activity-check-all]');
    if (checkbox !== null) {
        checkAsCheckboxes(checkbox, checkbox.dataset.activityCheckAll);
    }
});
