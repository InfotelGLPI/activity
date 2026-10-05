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
 * Activity report search form (cra_search.html.twig): the previous / next month buttons
 * carry data-activity-month-step (-1 or 1). They shift the month and year selectors,
 * wrapping around the year, then submit the form with them.
 */

document.addEventListener('click', (event) => {
    const button = event.target.closest?.('[data-activity-month-step]');
    if (!button) {
        return;
    }
    const form = button.form;
    const month_select = form?.querySelector('select[name="month"]');
    const year_select = form?.querySelector('select[name="year"]');
    if (!month_select || !year_select) {
        return;
    }
    event.preventDefault();
    // Already sent (data-submit-once, set by the core on submit): a second click would shift the
    // month again and start a second slow report computation
    if (form.dataset.submitted === 'true') {
        return;
    }

    const step = Number.parseInt(button.dataset.activityMonthStep, 10);
    let month = Number.parseInt(month_select.value, 10) + step;
    let year = Number.parseInt(year_select.value, 10);
    if (month > 12) {
        month = 1;
        year += 1;
    } else if (month < 1) {
        month = 12;
        year -= 1;
    }

    // The selectors keep the value format they were rendered with (e.g. "01")
    const option = [...month_select.options].find((opt) => Number.parseInt(opt.value, 10) === month);
    month_select.value = option ? option.value : String(month);
    year_select.value = String(year);
    form.requestSubmit(form.querySelector('button[type="submit"][name="submit"]'));
});
