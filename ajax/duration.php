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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\Report;

Session::checkRight("plugin_activity", READ);

$values  = [];
$report  = new Report();
$holiday = new Holiday();

// The caller owns the whole request body: none of these keys is guaranteed to be
// there, nor to be a string. Reading them blind turned an incomplete POST into
// undefined-key warnings and then an uncaught TypeError inside getActionTime(),
// on an endpoint whose contract is to answer JSON -- the client got a PHP trace
// or an empty body instead. Refuse the request explicitly instead.
if (
    !isset($_POST['begin'], $_POST['end'], $_POST['actiontime'])
    || !is_scalar($_POST['begin'])
    || !is_scalar($_POST['end'])
    || !is_scalar($_POST['actiontime'])
) {
    throw new BadRequestHttpException();
}

$actiontime  = $report->getActionTime(
    (string) $_POST["begin"],
    (string) $_POST["end"],
    $values,
    (string) $_POST["actiontime"],
    Report::$WORK,
);

header('Content-Type: application/json');
echo json_encode(['actiontime' => $actiontime]);
