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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginActivityConfig extends CommonDBTM {

   static $rightname = "plugin_activity";

   static function getTypeName($nb = 0) {

      return _n('Activity', 'Activity', $nb, 'activity');
   }

   function showForm ($ID, $options = []) {
      $this->getfromDB($ID);
      if (!$this->canView()) {
         return false;
      }
      if (!$this->canCreate()) {
         return false;
      }

      $used_entities = [];
      $dbu = new DbUtils();
      $dataConfig = $dbu->getAllDataFromTable($this->getTable());
      if ($dataConfig) {
         foreach ($dataConfig as $field) {
            $used_entities[] = $field['entities_id'];
         }
      }

      echo "<form name='form' method='post' action='".
         Toolbox::getItemTypeFormURL('PluginActivityConfig')."'>";

      echo "<div align='center'><table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='5'>".__('Define internal helpdesk', 'activity')."</th></tr>";

      echo "<tr class='tab_bg_1'>";

      // Dropdown entity
      echo "<td>".__('Entity')."</td>";
      echo "<td>";
      Dropdown::show('Entity', ['name' => 'entities_id',
                                     'used' => $used_entities]);
      echo "</td>";
    // is_recursive
      echo "<td>".__('Is recursive')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_recursive');
      echo "</td>";

      // Checkbox is_internal_helpdesk
      echo "<td>";
      echo Html::input('name', ['size' => 70]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td class='center' colspan='3'>";
      echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();

      if ($dataConfig) {
         $this->listItems($dataConfig);
      }

   }

   private function listItems($fields) {
      if (!$this->canView()) {
         return false;
      }

      $canedit = $this->canUpdate();

      $rand = mt_rand();
      $number = count($fields);

      echo "<div class='left'>";

      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams
            = ['container'
                        => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".__('Show internal helpdesk', 'activity')."</th></tr>";
      echo "<tr>";
      echo "<th width='10'>";
      if ($canedit && $number) {
         echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
      }
      echo "</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Is recursive')."</th>";
      echo "<th>".__('Type')."</th>";
      echo "</tr>";
      foreach ($fields as $field) {
         echo "<tr class='tab_bg_1'>";
         //CHECKBOX
         echo "<td width='10'>";
         if ($canedit) {
            Html::showMassiveActionCheckBox(__CLASS__, $field["id"]);
         }
         echo "</td>";
         //DATA LINE
         echo "<td>".Dropdown::getDropdownName('glpi_entities', $field['entities_id'])."</td>";
         echo "<td>";
         echo Dropdown::getYesNo($field['is_recursive']);
         echo "</td>";
         echo "<td>";
         echo $field['name'];
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * getConfigFromDB : get all configs in the database
    *
    * @global type $DB
    * @param type $ID : configs_id
    * @return boolean
    */
   static function getConfigFromDB($entities_id) {

      $ancestor_entities_id = getAncestorsOf(Entity::getTable(),$entities_id);
      if(!empty($ancestor_entities_id)) {
          $restrict = [
              "OR" => [
                  "entities_id" => $entities_id,
                  "AND" => [
                      "entities_id" => $ancestor_entities_id,
                      "is_recursive" => 1
                  ]
              ]

          ];
      } else {
          $restrict = [  "entities_id" => $entities_id];
      }

      $dbu = new DbUtils();
      $dataConfig = $dbu->getAllDataFromTable("glpi_plugin_activity_configs", $restrict);

      return $dataConfig;
   }
}
