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
 * Lateral activity menu: a calendar icon in the page header opens an offcanvas. The whole
 * offcanvas (title, actions, remaining holidays) is rendered by ajax/lateralmenu.php from
 * templates/lateral_menu.html.twig, and fetched on the first opening only: it carries the
 * holiday request modal, which must stay unique in the page.
 */

const OFFCANVAS_ID = 'plugin_activity_lateral_menu';
const TRIGGER_ID = 'plugin_activity_lateral_menu_link';

const url = `${window.CFG_GLPI?.root_doc ?? ''}${window.GLPI_PLUGINS_PATH?.activity ?? '/plugins/activity'}/ajax/lateralmenu.php`;

const open = async () => {
    if (document.getElementById(OFFCANVAS_ID) === null) {
        const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        if (!response.ok) {
            return;
        }
        // A contextual fragment keeps the script of the holiday request modal executable
        document.body.appendChild(document.createRange().createContextualFragment(await response.text()));
    }

    const offcanvas = document.getElementById(OFFCANVAS_ID);
    if (offcanvas !== null) {
        window.bootstrap.Offcanvas.getOrCreateInstance(offcanvas).show();
    }
};

const anchor = document.querySelector('header .ms-md-4');
if (anchor !== null && document.getElementById(TRIGGER_ID) === null) {
    const trigger = document.createElement('a');
    trigger.id = TRIGGER_ID;
    trigger.href = '#';
    trigger.className = 'ti ti-calendar-event';
    trigger.title = window._n?.('Activity', 'Activities', 1, 'activity') ?? '';
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        open();
    });
    anchor.before(trigger);
}
