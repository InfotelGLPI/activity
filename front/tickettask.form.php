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
 the Free Software Foundation; either version 2 of the License, or
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
use GlpiPlugin\Activity\TicketTask;

Session::checkLoginUser();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["users_id"])) {
   $users_id = Session::getLoginUserID();
} else {
   $users_id = $_GET["users_id"];
}

$tic = new TicketTask();

$tic->checkGlobal(READ);
if (!isset($_GET['_in_modal'])) {
   if (Session::getCurrentInterface() == 'central') {
      Html::header(TicketTask::getTypeName(2), '', "tools", Menu::class);
   } else {
      Html::helpHeader(TicketTask::getTypeName(2));
   }
} else {
   Html::popHeader(TicketTask::getTypeName(2));
}

$tic->display($_GET);
if (!isset($_GET['_in_modal'])) {
   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }
} else {
   Html::popFooter();
}
