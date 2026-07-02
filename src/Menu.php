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

namespace GlpiPlugin\Activity;

use CommonGLPI;
use Session;

class Menu extends CommonGLPI {
   static $rightname = 'plugin_activity';

   static function getMenuName() {
      return _n('Activity', 'Activities', 2, 'activity');
   }

   static function getMenuContent() {

      $plugin_page              = PLUGIN_ACTIVITY_WEBDIR. "/front/menu.php";
      $menu                     = [];
      //Menu entry in tools
      $menu['title']            = self::getMenuName();
      $menu['page']             = $plugin_page;
      $menu['links']['search']  = $plugin_page;

      if (Session::haveRight(static::$rightname, UPDATE)
            || Session::haveRight("config", UPDATE)) {
         //Entry icon in breadcrumb
         $menu['links']['config']                      = PLUGIN_ACTIVITY_WEBDIR . '/front/config.form.php';
         //Link to config page in admin plugins list
         $menu['config_page']                          = PLUGIN_ACTIVITY_WEBDIR . '/front/config.form.php';

         $menu['options']['holidaycount']['title']           = _n('Holiday counter', 'Holiday counters', 2, 'activity');
         $menu['options']['holidaycount']['page']            = PLUGIN_ACTIVITY_WEBDIR.'/front/holidaycount.php';
         $menu['options']['holidaycount']['links']['add']    = PLUGIN_ACTIVITY_WEBDIR.'/front/holidaycount.form.php';
         $menu['options']['holidaycount']['links']['search'] = PLUGIN_ACTIVITY_WEBDIR.'/front/holidaycount.php';
         $menu['icon']                                       = Holiday::getIcon();
      }

      return $menu;
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['tools']['types'][Menu::class])) {
         unset($_SESSION['glpimenu']['tools']['types'][Menu::class]);
      }
      if (isset($_SESSION['glpimenu']['tools']['content'][Menu::class])) {
         unset($_SESSION['glpimenu']['tools']['content'][Menu::class]);
      }
   }
}
