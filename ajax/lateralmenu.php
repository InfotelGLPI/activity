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
//header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

header("Content-Type: text/html; charset=UTF-8");

Html::popHeader(_n('Private holiday', 'Private holidays', 2, 'activity'), $_SERVER['PHP_SELF']);

echo "<div class='center' id='tabsbody' >";
echo "<table class='tab_cadre' width='100%'>";
PluginActivityLateralmenu::showMenu();
echo "</table>";
echo "</div>";
