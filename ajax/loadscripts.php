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

Html::header_nocache();
Session::checkLoginUser();
Session::checkRight("plugin_activity", READ);
header("Content-Type: application/json; charset=UTF-8");

// Security: return pure JSON data (never HTML with <script> tags). The client
// parses this with JSON.parse and calls the behaviours itself, instead of
// eval()-ing server output — so no reflected value can ever become executable JS.
$response = [];
if (isset($_POST['action']) && $_POST['action'] === "load") {
   //TODO comment For ?
   if (Session::getCurrentInterface() == "central"
         && (strpos($_SERVER['REQUEST_URI'], "cra.php") !== false)) {
      $response['lang_month'] = array_values(Toolbox::getMonthsOfYearArray());
   }

   $response['slidepanel'] = [
      'name'        => 'showLateralMenu',
      'title'       => _n('Activity', 'Activities', 1, 'activity'),
      'url'         => PLUGIN_ACTIVITY_WEBDIR . '/ajax/lateralmenu.php',
      'position'    => 'right',
      'close_label' => __('Close'),
   ];
}

echo json_encode($response);
