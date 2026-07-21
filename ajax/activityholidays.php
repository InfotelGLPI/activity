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

Html::header_nocache();
Session::checkLoginUser();
// checkLoginUser() is not authorization on GLPI 11: require the plugin READ
// right like every other endpoint, even when a user loads their own balance.
Session::checkRight('plugin_activity', READ);
header("Content-Type: text/html; charset=UTF-8");

$holiday = new Holiday();

if (isset($_POST['load_holiday_details'])) {
   $target_users_id = (int) $_POST['users_id'];
   // Only the session user may read their own leave balance, unless the profile
   // holds the "all users" right (the right gating every other cross-user view).
   if ($target_users_id !== Session::getLoginUserID()
       && !Session::haveRight('plugin_activity_all_users', 1)) {
      http_response_code(403);
      return;
   }
   $holiday->getDetails($target_users_id, $_POST['holiday_period_id']);
}
