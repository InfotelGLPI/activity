<?php

/*
  -------------------------------------------------------------------------
  Activity plugin for GLPI
  Copyright (C) 2019-2022 by the Activity Development Team.
  -------------------------------------------------------------------------

  LICENSE

  This file is part of Activity.

  Activity is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  Activity is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Activity. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

if (!isset($_POST['holidays_id']) && !isset($_GET['holidays_id'])) {
   exit();
}

if (isset($_POST['holidays_id'])) {
   $hId = $_POST['holidays_id'];
}
if (isset($_GET['holidays_id'])) {
   $hId = $_GET['holidays_id'];
}

$holiday = new PluginActivityHoliday();
$holiday->getFromDB($hId);

if (isset($holiday->fields['id'])) {

   $user = new User();
   $user->getFromDB($holiday->fields['users_id']);

   $userName         = (isset($user->fields['realname']) ? strtoupper($user->fields['realname']) : '') . " " . (isset($user->fields['firstname']) ? $user->fields['firstname'] : '');
   $approverFullname = (isset($_SESSION['glpifirstname']) ? ucfirst($_SESSION['glpifirstname']) : '') . " " . (isset($_SESSION['glpirealname']) ? strtoupper($_SESSION['glpirealname']) : '');
   $periods          = $holiday->getPeriodForTemplate($holiday->fields['actiontime']);

   $dateBegin = date('d-m-y', strtotime($holiday->fields['begin'])) . $periods['txt'];

   $strTxtFile = $holiday->createTxtFile($hId);

   $filename = "DC " . $userName . " " . date('Y') . " " . $dateBegin . ".txt";

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
   $notification          = new PluginActivityNotification();
   $notification->sendComm($input);

} else {
   exit();
}

exit;
