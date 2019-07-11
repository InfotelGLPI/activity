<?php

/*
 -------------------------------------------------------------------------
 Activity plugin for GLPI
 Copyright (C) 2013 by the Activity Development Team.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginActivityActivity_Item extends CommonDBTM {

   static $rightname = "plugin_activity";

   /**
    * Display activity-item's tab for each computer
    *
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == 'User' && $this->canView()) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $dbu = new DbUtils();
               return self::createTabEntry(PluginActivityActivity::getTypeName(2),
                                           $dbu->countElementsInTable("glpi_plugin_activity_activities",
                                                                      ["users_id" => $item->getID()]));
            }
            return PluginActivityActivity::getTypeName(2);
         }
      }
      return '';
   }

   /**
    * Display tab's content for each computer
    *
    * @static
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool|true
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'User': self::showForUser($item);
            break;
      }

      return true;
   }


   static function showForUser($user) {
      global $DB;

      // validation des droits
      if (!Session::haveRight("plugin_activity", READ)) {
         return false;
      }

      $activitytype = new PluginActivityActivityType();
      $activity = new PluginActivityActivity();
      $query = "SELECT * FROM `glpi_plugin_activity_activities` 
                WHERE `users_id` = '".$user->fields['id']."' 
                ORDER BY `begin` DESC";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number != 0) {
         echo "<div class='spaced'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='5'>"._n('Associated activity', 'Associated activities', 2, 'activity')."</th>";
         echo "</tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<th>".__('Name')."</th>";
         echo "<th>".__('Description')."</th>";
         echo "<th>".__('Begin date')."</th>";
         echo "<th>".__('Duration')."</th>";
         echo "<th>".PluginActivityActivityType::getTypeName(1)."</th>";
         echo "</tr>";

         while ($data = $DB->fetch_array($result)) {

            echo "<tr class='tab_bg_1'>";
            if ($activity->getFromDB($data["id"])) {
               $link         = $activity->getLink();
            }
            echo "<td align='center'>".$link;
            echo "</a></td>";
            $content = Html::setSimpleTextContent($data["comment"]);
            echo "<td align='center'>".$content."</td>";
            echo "<td align='center'>".Html::convdate($data["begin"])."</td>";
            echo "<td>".CommonITILObject::getActionTime($data["actiontime"])."</td>";
            echo "<td>".Dropdown::getDropdownName("".$activitytype->getTable()."", $data["plugin_activity_activitytypes_id"])."</td>";

            echo "</tr>";
         }
         echo "</table>";
         echo "</div>";
      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>".__('No activity found', 'activity')."</td>";
         echo "</tr>";
         echo "</table>";
      }
   }
}