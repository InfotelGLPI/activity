<?php

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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Activity\HolidayCount;
use GlpiPlugin\Activity\Menu;

$count = new HolidayCount();

// Same ordering as front/holiday.php: settle the right before the response body
// starts, otherwise the 403 is emitted after a full page of GLPI chrome.
if (!$count->canView()) {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() == 'central') {
    Html::header(HolidayCount::getTypeName(2), '', "tools", Menu::class, "holidaycount");
} else {
    Html::helpHeader(HolidayCount::getTypeName(2));
}

Search::show(HolidayCount::class);

if (Session::getCurrentInterface() == 'central') {
    Html::footer();
} else {
    Html::helpFooter();
}
