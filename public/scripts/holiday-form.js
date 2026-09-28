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
 * Holiday request form (templates/holiday_form.html.twig): the begin and end dates and
 * their half-day radios carry data-activity-duration, and changing any of them recomputes
 * the duration through updateDuration() of scripts-activityholidays.js.
 *
 * The form is also rendered in the planning modal after page load, hence the listener
 * delegated to the document. Flatpickr dispatches `change` on the named input.
 */

document.addEventListener('change', (event) => {
    const input = event.target.closest?.('[data-activity-duration]');
    const duration = document.getElementById('div_duration');
    if (!input || !duration || typeof window.updateDuration !== 'function') {
        return;
    }
    window.updateDuration(input, duration.dataset.activityWebdir);
});
