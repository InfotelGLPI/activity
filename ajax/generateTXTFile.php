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

use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\HolidayValidation;

Session::checkRight("plugin_activity_can_validate", READ);

if (!isset($_POST['holidays_id']) && !isset($_GET['holidays_id'])) {
    throw new NotFoundHttpException();
}

if (isset($_POST['holidays_id'])) {
    $hId = (int) $_POST['holidays_id'];
}
if (isset($_GET['holidays_id'])) {
    $hId = (int) $_GET['holidays_id'];
}

$holiday = new Holiday();
$holiday->getFromDB($hId);

// Same scoping as front/generateTXTFile.php: the global can_validate right is
// not enough, the caller must be the registered validator for THIS holiday.
if (isset($holiday->fields['id']) && HolidayValidation::canValidate($hId)) {

    $user = new User();
    $user->getFromDB($holiday->fields['users_id']);

    $userName = (isset($user->fields['realname']) ? strtoupper($user->fields['realname']) : '') . " " . (isset($user->fields['firstname']) ? $user->fields['firstname'] : '');

    $periods = $holiday->getPeriodForTemplate($holiday->fields['actiontime']);

    $dateBegin = date('d-m-y', strtotime($holiday->fields['begin'])) . $periods['txt'];

    $strTxtFile = $holiday->createTxtFile($hId);

    $filename = "DC " . $userName . " " . date('Y') . " " . $dateBegin . ".txt";

    // Emit the HTTP headers BEFORE writing the body, otherwise the download
    // headers land after output has started ("headers already sent").
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false); // required for certain browsers
    header("Content-Type: application/octet-stream");

    header('Content-Disposition: attachment; filename="' . basename($filename) . '";');
    header('Content-Transfer-Encoding: binary');

    $f = fopen("php://output", 'w');
    fwrite($f, $strTxtFile);
    fclose($f);
} else {
    throw new NotFoundHttpException();
}
