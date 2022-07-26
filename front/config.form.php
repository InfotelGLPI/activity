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

include ('../../../inc/includes.php');

Session::checkLoginUser();

if (Plugin::isPluginActive("activity")) {
   Session::checkRight("config", UPDATE);
   $config = new PluginActivityConfig();
   $opt = new PluginActivityOption();
   if (isset($_POST["update"])) {
      if ($config->canCreate() && isset($_POST['entities_id'])) {
         $config->add($_POST);
      }
      Html::back();

   } else if (isset($_POST["delete_item"])) {
      if ($config->canCreate()) {
         foreach ($_POST["item"] as $key => $val) {
            if ($val != 0) {
               $config->getFromDB($key);
               $config->delete(['id' => $key]);
            }
         }
      }
      Html::back();

   } else {
      Html::header(PluginActivityPlanningExternalEvent::getTypeName(2), '', "tools", "pluginactivitymenu");
      $config->showForm(1);
      $opt->showForm(1);
      Html::footer();
   }

} else {
   Html::header(__('Setup'), '', "config", "plugins");
   echo "<div class='alert alert-important alert-warning d-flex'>";
   echo "<b>".__('Please activate the plugin', 'activity')."</b></div>";
}
