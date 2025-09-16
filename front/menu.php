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

use GlpiPlugin\Activity\Menu;
use GlpiPlugin\Activity\PlanningExternalEvent;
use GlpiPlugin\Activity\Holiday;

Session::checkLoginUser();

use Glpi\Exception\Http\AccessDeniedHttpException;

if (Session::getCurrentInterface() == 'central') {
   Html::header(PlanningExternalEvent::getTypeName(2), '', "tools", Menu::class);
} else {
   Html::helpHeader(PlanningExternalEvent::getTypeName(2));
}

$activity = new \PlanningExternalEvent();

$can = Session::haveRight("plugin_activity", READ);
$canholiday = Session::haveRight("plugin_activity_can_requestholiday", 1);
$canvalidateholiday = Session::haveRight("plugin_activity_can_validate", 1);

echo "<h3><div class='alert alert-secondary' role='alert'>";
echo "<i class='ti ti-calendar'></i>&nbsp;";
echo _n('Activity', 'Activities', 2, 'activity');
echo "</div></h3>";

echo "<table class='center' cellspacing='5'  style=\"margin-left: auto;margin-right:auto;\"><tr>";

if ($can) {
   echo "<td>";
   $listActions = PlanningExternalEvent::getActionsOn();
   echo PlanningExternalEvent::menu(PlanningExternalEvent::class, $listActions);
   echo "</td>";
}
if ($canholiday
         || $canvalidateholiday) {
   echo "<td>";
   $listActions = Holiday::getActionsOn();
   echo PlanningExternalEvent::menu(Holiday::class, $listActions);
   echo "</td>";
}
echo "</tr></table>";
if (!$can && !$canholiday  && !$canvalidateholiday) {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}
