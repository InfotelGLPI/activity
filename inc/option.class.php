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

class PluginActivityOption extends CommonDBTM {

   static $rightname = "plugin_activity";

   static function getTypeName($nb = 0) {

      return _n('Activity', 'Activity', $nb, 'activity');
   }


   function showForm ($ID, $options = []) {
      global $CFG_GLPI;

      $this->getFromDB(1);
      if (!$this->canView()) {
         return false;
      }
      if (!$this->canCreate()) {
         return false;
      }

      echo "<form name='form' method='post' action='".
         Toolbox::getItemTypeFormURL('PluginActivityOption')."'>";

      echo "<div align='center'><table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>".__('Plugin setup', 'activity')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Replace activity name by the activity type', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_type_as_name', $this->fields['use_type_as_name']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Use time repartition', 'activity')."</td>";
      echo "<td>";
      $rand = Dropdown::showYesNo('use_timerepartition', $this->fields['use_timerepartition'], -1,
                                  ['on_change' => 'changetimerepartition();']);
      echo "</td>";
      echo "</tr>";

      echo Html::scriptBlock("
         function changetimerepartition(){
            if($('#dropdown_use_timerepartition$rand').val() != 0){
               $('#timerepartitionlabel').show();
               $('#timerepartition').show();
            } else {
               $('#timerepartitionlabel').hide();
               $('#timerepartition').hide();
            }
         }
         changetimerepartition();
      ");

      $style_mandaydisplay = "style='display: none;'";
      if ($this->fields['use_timerepartition']) {
         $style_mandaydisplay = '';
      }

      echo "<tr class='tab_bg_1' id='timerepartition' $style_mandaydisplay>";
      echo "<td id='timerepartitionlabel'>".__('Use the view by day men in planning', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_mandaydisplay', $this->fields['use_mandaydisplay']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Use pairs schedules', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_pairs', $this->fields['use_pairs']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Authorize only the whole schedules', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_integerschedules', $this->fields['use_integerschedules']);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Authorize activities on week-ends', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_weekend', $this->fields['use_weekend']);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Principal client', 'activity')."</td>";
      echo "<td>";
      echo Html::input('principal_client', ['value' => $this->fields['principal_client'], 'size' => 70]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Used mail for holidays', 'activity')."</td>";
      echo "<td>";
      echo Html::input('used_mail_for_holidays', ['value' => $this->fields['used_mail_for_holidays'], 'size' => 70]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('CRA Footer', 'activity')."</td>";
      echo "<td>";
      Html::textarea(['name'            => 'cra_footer',
                      'value'           => $this->fields["cra_footer"],
                      'cols'       => 80,
                      'rows'       => 8,
                      'enable_richtext' => false]);
      echo "</td></tr>\n";

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Use group manager as holiday manager', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_groupmanager', $this->fields['use_groupmanager']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Default validation percent', 'activity')."</td>";
      echo "<td>";
      $holiday = new PluginActivityHoliday();
      echo $holiday->getValueToSelect('validation_percent', 'default_validation_percent',
                                      $this->fields["default_validation_percent"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('is on CRA by default', 'activity')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_cra_default', $this->fields['is_cra_default']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Use projects', 'activity')."</td>";
      echo "<td>";
      $rand_project = Dropdown::showYesNo('use_project', $this->fields['use_project'], -1,
                                          ['on_change' => 'change_useproject();']);
      echo "</td>";
      echo "</tr>";

      echo Html::scriptBlock("
         function change_useproject(){
            if($('#dropdown_use_project$rand_project').val() != 0){
               $('#display_use_project').show();
            } else {
               $('#display_use_project').hide();
            }
         }
         change_useproject();
      ");
      $style_use_project = "style='display: none;'";
      if ($this->fields['use_project']) {
         $style_use_project = '';
      }

      echo "<tr class='tab_bg_1' id='display_use_project' $style_use_project>";
      echo "<td>".__('is on CRA by default', 'activity'). " - ". Project::getTypeName(0)."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_cra_default_project', $this->fields['is_cra_default_project']);
      echo "</td>";
      echo "</tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Use hour on cra and not half day', 'activity')."</td>";
       echo "<td>";
       Dropdown::showYesNo('use_hour_on_cra', $this->fields['use_hour_on_cra']);
       echo "</td>";
       echo "</tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Use event subcategories', 'activity')."</td>";
       echo "<td>";
       Dropdown::showYesNo('use_planningeventsubcategories', $this->fields['use_planningeventsubcategories']);
       echo "</td>";
       echo "</tr>";

      echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
      echo Html::hidden('id', ['value' => 1]);
      echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();

   }

   /**
    * getConfigFromDB : get all configs in the database
    *
    * @global type $DB
    * @param type $ID : configs_id
    * @return boolean
    */
   static function getConfigFromDB() {

      $dbu = new DbUtils();
      $dataConfig = $dbu->getAllDataFromTable("glpi_plugin_activity_options");

      return $dataConfig;
   }

   /**
    * get use project
    *
    * @return mixed
    */
   public function getUseProject() {
      return $this->fields['use_project'];
   }

   /**
    * get is cra default project
    *
    * @return mixed
    */
   public function getIsCraDefaultProject() {
      return $this->fields['is_cra_default_project'];
   }

    public function getUseSubcategory() {
        return $this->fields['use_planningeventsubcategories'];
    }
}
