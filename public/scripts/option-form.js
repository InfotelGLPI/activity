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
 * Plugin options form (option_form.html.twig): a block carrying
 * data-activity-toggle="<name>" is shown only while the yes/no selector <name> is on "yes".
 *
 * The selectors are select2 widgets, which trigger their change event through jQuery
 * only, hence the jQuery delegated listener.
 */

const sync = (name) => {
    const select = document.querySelector(`select[name="${CSS.escape(name)}"]`);
    if (!select) {
        return;
    }
    document.querySelectorAll(`[data-activity-toggle="${CSS.escape(name)}"]`).forEach((block) => {
        block.style.display = select.value !== '0' ? '' : 'none';
    });
};

document.querySelectorAll('[data-activity-toggle]').forEach((block) => {
    sync(block.dataset.activityToggle);
});

$(document).on('change', 'select', (event) => {
    if (document.querySelector(`[data-activity-toggle="${CSS.escape(event.target.name)}"]`)) {
        sync(event.target.name);
    }
});
