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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\HolidayValidation;
use GlpiPlugin\Activity\Menu;

Session::checkLoginUser();

if (!isset($_GET["id"]) && !(isset($_POST['id']))) {
    throw new BadRequestHttpException(__('Item not found'));
}
$ID = isset($_POST['id']) ? (int)$_POST['id'] : (int)$_GET['id'];

$holidayValidation = new HolidayValidation();

if (isset($_POST["update"])) {
   $holidayValidation->check($ID, UPDATE);
   // Per-record ownership: only the validator designated on THIS record may
   // accept/refuse it — the global plugin_activity right is not enough. This
   // mirrors the per-record guard already enforced on the other workflow
   // endpoints (ajax/sendmail.php, front+ajax/generateTXTFile.php,
   // ajax/viewsubvalidation.php) and the UI, which only exposes the buttons
   // when users_id_validate == current user.
   if ((int)$holidayValidation->fields['users_id_validate'] !== Session::getLoginUserID()) {
      throw new AccessDeniedHttpException();
   }
   $holidayValidation->update($_POST);
   Html::back();

} else if (isset($_POST["delete"])) {
   $holidayValidation->check($ID, PURGE);
   if ((int)$holidayValidation->fields['users_id_validate'] !== Session::getLoginUserID()) {
      throw new AccessDeniedHttpException();
   }
   $holidayValidation->delete($_POST);
   Html::back();

} else if (isset($_POST["add"])) {
   $holidayValidation->check(-1, CREATE, $_POST);
   // Validation rows are created by the submission workflow (src/Holiday.php),
   // never by end users through this form. Only an already-designated validator
   // of the target holiday may add to its validations — this blocks a caller
   // from self-assigning as validator on an arbitrary holiday and then
   // approving it.
   $holidays_id = (int)($_POST['plugin_activity_holidays_id'] ?? 0);
   if (!HolidayValidation::canValidate($holidays_id)) {
      throw new AccessDeniedHttpException();
   }
   $holidayValidation->add($_POST);
   Html::back();

} else {
   // View a specific record: restricted to a designated validator of the
   // holiday or the holiday requester — not to anyone holding the global READ
   // right on the plugin.
   if (!$holidayValidation->getFromDB($ID)) {
      throw new BadRequestHttpException(__('Item not found'));
   }
   $holidayValidation->checkGlobal(READ);

   $holiday = new Holiday();
   $holiday->getFromDB($holidayValidation->fields['plugin_activity_holidays_id']);
   if (!HolidayValidation::canValidate((int)$holidayValidation->fields['plugin_activity_holidays_id'])
       && (int)$holiday->fields['users_id'] !== Session::getLoginUserID()) {
      throw new AccessDeniedHttpException();
   }

   if (Session::getCurrentInterface() == 'central') {
      Html::header(HolidayValidation::getTypeName(2), '', "tools", Menu::class);
   } else {
      Html::helpHeader(HolidayValidation::getTypeName(2));
   }

   $holidayValidation->display(['id' => $ID]);
   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }

}
