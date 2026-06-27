<?php

/*
 -------------------------------------------------------------------------
 activity plugin for GLPI
 Copyright (C) 2019-2026 by the activity Development Team.

 https://github.com/InfotelGLPI/activity
 -------------------------------------------------------------------------

 LICENSE

 This file is part of activity.

 activity is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 activity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with activity. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\Report;

Session::checkLoginUser();

$values  = [];
$report  = new Report();
$holiday = new Holiday();

$actiontime  = $report->getActionTime(
    $_POST["begin"],
    $_POST["end"],
    $values,
    $_POST["actiontime"],
    Report::$WORK
);

header('Content-Type: application/json');
echo json_encode(['actiontime' => $actiontime]);
