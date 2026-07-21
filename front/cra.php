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

use GlpiPlugin\Activity\Menu;
use GlpiPlugin\Activity\Report;

Session::checkLoginUser();
// checkLoginUser() is not authorization on GLPI 11: gate this activity report on
// the plugin READ right.
Session::checkRight('plugin_activity', READ);

if (isset($_GET['itemtype'])) {
   unset($_GET['root_doc']);

   // Guard against a missing/non-array 'criteria': count(null) is a fatal
   // TypeError under PHP 8, turning a crafted GET into a self-inflicted 500.
   $criteria = (isset($_GET['criteria']) && is_array($_GET['criteria'])) ? $_GET['criteria'] : [];

   $_SESSION['glpisearch'][] = $_GET;
   $_SESSION['glpisearch'][$_GET['itemtype']] = $_GET;
   $_SESSION['glpisearchcount'] = [$_GET['itemtype'] => count($criteria)];

   $target = Toolbox::getItemTypeSearchURL($_GET['itemtype']);

   header('Location: '.$target."?".Toolbox::append_params($_GET, '&'));

} else {
   Html::header(__('Report of Activities', 'activity'), '', "tools", Menu::class);

   // After taking a snapshot we redirect here with the report criteria in the
   // query string (PRG pattern). Rehydrate $_POST from those GET values so the
   // existing POST-based rendering keeps the displayed period/user instead of
   // falling back to the current month.
   foreach (['month', 'year', 'users_id'] as $activity_crit) {
      if (empty($_POST[$activity_crit]) && isset($_GET[$activity_crit])) {
         $_POST[$activity_crit] = $_GET[$activity_crit];
      }
   }

   if (empty($_POST["month"])) {
      $_POST["month"] = date('m', time());
   }

   if (empty($_POST["year"])) {
      $_POST["year"] = date('Y', time());
   }

   // Only profiles holding plugin_activity_all_users may read another user's CRA;
   // everyone else is forced onto their own report regardless of the posted
   // users_id, otherwise any user could read anyone's activity by id.
   if (!Session::haveRight("plugin_activity_all_users", 1)
           || !isset($_POST["users_id"])
           || empty($_POST["users_id"])) {
      $_POST["users_id"] = Session::getLoginUserID();
   }

   $report = new Report();

   $report->showGenericSearch($_POST);
}
Html::footer();
