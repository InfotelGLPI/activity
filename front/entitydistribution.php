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

use GlpiPlugin\Activity\EntityDistribution;
use GlpiPlugin\Activity\Menu;

// Same right as the CRA this page is computed from
Session::checkRight(EntityDistribution::$rightname, READ);

Html::header(EntityDistribution::getTypeName(), '', "tools", Menu::class);

if (isset($_POST['set_techleads'])) {
    EntityDistribution::setTechLeads($_POST);
    // Rendered again below with the same year and technician filter
    Html::displayMessageAfterRedirect();
}

EntityDistribution::showDistribution($_POST);

Html::footer();
