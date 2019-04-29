<?php

/*
 -------------------------------------------------------------------------
 Activity plugin for GLPI
 Copyright (C) 2019 by the Activity Development Team.
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

include ('../../../inc/includes.php');

Session::checkLoginUser();

if (Session::getCurrentInterface() == 'central') {
   Html::header(PluginActivityActivity::getTypeName(2), '', "tools", "pluginactivitymenu");
} else {
   Html::helpHeader(PluginActivityActivity::getTypeName(2));
}

$activity = new PluginActivityActivity();

$can = $activity->canView();
$canholiday = Session::haveRight("plugin_activity_can_requestholiday", 1);
$canvalidateholiday = Session::haveRight("plugin_activity_can_validate", 1);

echo "<table align='center' cellspacing='5'><tr>";

if ($can) {
   echo "<td>";
   $listActions = PluginActivityActivity::getActionsOn();
   echo PluginActivityActivity::menu("PluginActivityActivity", $listActions);
   echo "</td>";
}
if ($canholiday
         || $canvalidateholiday) {
   echo "<td>";
   $listActions = PluginActivityHoliday::getActionsOn();
   echo PluginActivityActivity::menu("PluginActivityHoliday", $listActions);
   echo "</td>";

}
echo "</tr></table>";
if (!$can && !$canholiday  && !$canvalidateholiday) {
   Html::displayRightError();
}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}