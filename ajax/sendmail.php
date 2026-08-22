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
use GlpiPlugin\Activity\Notification;

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

// Holiday::canViewItem() only checks the global plugin_activity_can_validate
// right, not that the caller is the registered validator for THIS holiday.
// HolidayValidation::canValidate() ties the action to the specific record,
// preventing a validator from acting on a holiday outside their scope.
if (isset($holiday->fields['id']) && HolidayValidation::canValidate($hId)) {

    $user = new User();
    $user->getFromDB($holiday->fields['users_id']);

    $userName         = (isset($user->fields['realname']) ? strtoupper($user->fields['realname']) : '') . " " . (isset($user->fields['firstname']) ? $user->fields['firstname'] : '');
    $approverFullname = (isset($_SESSION['glpifirstname']) ? ucfirst($_SESSION['glpifirstname']) : '') . " " . (isset($_SESSION['glpirealname']) ? strtoupper($_SESSION['glpirealname']) : '');
    $periods          = $holiday->getPeriodForTemplate($holiday->fields['actiontime']);

    $dateBegin = date('d-m-y', strtotime($holiday->fields['begin'])) . $periods['txt'];

    $strTxtFile = $holiday->createTxtFile($hId);

    $filename = "DC " . $userName . " " . date('Y') . " " . $dateBegin . ".txt";
    // $userName comes from the target user's display name: strip any path
    // component so a "../" in the name cannot escape GLPI_TMP_DIR on write.
    $filename = basename($filename);

    $f = fopen(GLPI_TMP_DIR . "/" . $filename, 'w');
    fwrite($f, $strTxtFile);
    fclose($f);

    $input                 = [];
    $input['id']           = $hId;
    $dateBegin             = date('d/m/Y', strtotime($holiday->fields['begin'])) . " " . $periods['txt'];
    $input['mail_subject'] = __("Holiday request from", "activity") . " " . $userName . " " . __("of", "activity") . " " . $dateBegin;
    $input['mail_body']    = $holiday->getBodyMail($dateBegin, date("d/m/Y", strtotime($holiday->fields['begin'])), $userName, $approverFullname);
    $input['validate_id']  = Session::getLoginUserID();
    $input['users_id']     = $holiday->fields['users_id'];
    $input['filename']     = $filename;
    $input['filepath']     = GLPI_TMP_DIR . "/" . $filename;
    $notification          = new Notification();
    $notification->sendComm($input);

} else {
    throw new NotFoundHttpException();
}

exit;
