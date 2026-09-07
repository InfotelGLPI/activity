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
use GlpiPlugin\Activity\Menu;
use GlpiPlugin\Activity\Holiday;

$holiday = new Holiday();

// The right must be settled before anything is written to the response: once
// Html::header() has run, output has started and the AccessDenied exception can
// no longer produce a clean 403 -- the caller got a full GLPI chrome (menu bar,
// breadcrumb, entity navigation) with an error spliced into the middle of it.
// Same ordering as front/planning.php and front/menu.php.
if (!$holiday->canView()) {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() == 'central') {
    Html::header(Holiday::getTypeName(2), '', "tools", Menu::class);
} else {
    Html::helpHeader(Holiday::getTypeName(2));
}

Search::show(Holiday::class);

if (Session::getCurrentInterface() == 'central') {
    Html::footer();
} else {
    Html::helpFooter();
}
