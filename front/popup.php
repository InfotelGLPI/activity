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
use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\PlanningExternalEvent;
use GlpiPlugin\Activity\Report;

Session::checkLoginUser();
// checkLoginUser() is not authorization on GLPI 11: gate this popup on the
// plugin READ right like front/cra.php does.
Session::checkRight('plugin_activity', READ);

if (isset($_GET["users_id"])) {
   $users_id = (int) $_GET["users_id"];

} else if (isset($_POST["users_id"])) {
   $users_id = (int) $_POST["users_id"];

} else {
   $users_id = Session::getLoginUserID();
}

// Only profiles holding plugin_activity_all_users may target another user;
// everyone else is forced onto their own data regardless of the requested id.
if (!Session::haveRight("plugin_activity_all_users", 1)) {
   $users_id = Session::getLoginUserID();
}

if (!isset($_GET["id"])) {
   $_GET["id"] = 0;
}

if (isset($_GET["popup"])) {
   $_SESSION["glpipopup"]["name"] = $_GET["popup"];
}

if (isset($_SESSION["glpipopup"]["name"])) {
   switch ($_SESSION["glpipopup"]["name"]) {
      case "planningexternalevents" :
         Html::popHeader(PlanningExternalEvent::getTypeName(2), $_SERVER['PHP_SELF']);
         $_POST['target'] = "popup.php";
          $report = new Report();
          $report->showGenericSearch(array_merge($_POST, ['users_id' => $users_id]));
         break;
      case "holiday" :
         $holiday = new Holiday();
         // showForm() does not enforce rights on its own: check the holiday can
         // be read (entity + ownership) before rendering it.
         if (!$holiday->can((int) $_GET["id"], READ)) {
             throw new AccessDeniedHttpException();
         }
         Html::popHeader(Holiday::getTypeName(2), $_SERVER['PHP_SELF']);
         $holiday->showForm($_GET["id"], ['users_id' => $users_id]);
         break;
   }

   echo "<div class='center'><br><a href='javascript:window.close()'>".__('Close')."</a>";
   echo "</div>";
   Html::popFooter();
}
