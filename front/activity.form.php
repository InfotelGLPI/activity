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

if (!isset($_GET["id"])) {
   $_GET["id"] = 0;
}
if (!isset($_GET["users_id"])) {
   $users_id = Session::getLoginUserID();
} else {
   $users_id = $_GET["users_id"];
}

$activity = new PluginActivityActivity();

if (isset($_POST["add"])) {
   $activity->check(-1, CREATE, $_POST);
   $activity->add($_POST);
   Html::back();

} else if (isset($_POST["update"])) {
   $activity->check($_POST['id'], UPDATE);
   $activity->update($_POST);
   Html::back();

} else if (isset($_POST["purge"])) {
   $activity->check($_POST['id'], PURGE);
   $activity->delete($_POST, 1);
   if (!isset($_POST["from_planning_edit_ajax"])) {
      $activity->redirectToList();
   } else {
      Html::back();
   }

} else {
   $activity->checkGlobal(READ);

   if (Session::getCurrentInterface() == 'central') {
      Html::header(PluginActivityActivity::getTypeName(2), '', "tools", "pluginactivitymenu");
   } else {
      Html::helpHeader(PluginActivityActivity::getTypeName(2));
   }
   //TODO And for simplified interface ?
   //$_POST['target']   = Toolbox::getItemTypeFormURL("PluginActivityActivity");

   //if (!isset($_POST["users_id"]) || empty($_POST["users_id"])) {
   //   $_POST["users_id"] = Session::getLoginUserID();
   //}

   //PluginActivityActivity::showGenericSearch(array_merge($_POST, $_GET));
   Html::requireJs('tinymce');
   $activity->display($_GET);

   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }
}