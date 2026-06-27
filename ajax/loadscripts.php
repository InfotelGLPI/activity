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

use GlpiPlugin\Activity\LateralMenu;

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['action'])) {
   switch ($_POST['action']) {
      case "load" :
         //TODO comment For ?
         if (Session::getCurrentInterface() == "central"
               && (strpos($_SERVER['REQUEST_URI'], "cra.php") !== false)) {
            $lang_month  = array_values(Toolbox::getMonthsOfYearArray());
            echo "<script type='text/javascript'>changeClickTodayActivity(".json_encode(['lang_month' => $lang_month]).");</script>";
         }

          LateralMenu::createSlidePanel(
             'showLateralMenu',
             [
                 'title'     => _n('Activity', 'Activities', 1, 'activity'),
                 'url'       => PLUGIN_ACTIVITY_WEBDIR . '/ajax/lateralmenu.php'
             ]
         );

         break;
   }
} else {
   exit;
}
