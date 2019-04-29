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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginActivityActions {
   const ADD_ACTIVITY     = 'add_activity';
   const LIST_ACTIVITIES  = 'list_activities';
   const HOLIDAY_REQUEST  = 'holiday_request';
   const LIST_HOLIDAYS    = 'list_holidays';
   const APPROVE_HOLIDAYS = 'validate_holidays';
   const CRA              = 'cra';
   const HOLIDAY_COUNT    = 'holiday_count';
   const MANAGER          = 'manager';
}


class PluginActivityActivity extends CommonDBTM {

   // Event color
   static $HOLIDAY_COLOR        = "#7DAEDF";
   static $ACTIVITY_COLOR       = "#84BE6A";
   static $MANAGEENTITIES_COLOR = "#08A5AC";
   static $TICKET_COLOR         = "#E85F0C";

   // Calendar views
   /*static $DAY   = 'agendaDay';
   static $WEEK  = 'agendaWeek';
   static $MONTH = 'month';*/

   // Event tags
   static $TICKET_TAG         = 'ticket';
   static $MANAGEENTITIES_TAG = 'manageentities';
   static $ACTIVITY_TAG       = 'activity';
   static $HOLIDAY_TAG        = 'holiday';

   var $dohistory = false;

   static $rightname = "plugin_activity";

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      return _n('Activity', 'Activities', $nb, 'activity');
   }

   function post_getEmpty() {

      $this->fields['is_planned']   = 1;
      $this->fields['is_usedbycra'] = 1;
   }

   function isAllDayTask($input) {
      $interval = -1;
      if (isset($input["begin"]) && isset($input["end"])) {
         $begin    = strtotime($input["begin"]);
         $end      = strtotime($input["end"]);
         $interval = $end - $begin;
      }
      if (isset($input["plan"]["begin"]) && isset($input["plan"]["end"])) {
         $begin    = strtotime($input["plan"]["begin"]);
         $end      = strtotime($input["plan"]["end"]);
         $interval = $end - $begin;
      }
      //86400 is a day duration in seconds
      if ($interval % 86400 == 0) {
         $input["allDay"] = 1;
         $input["days"]   = $interval / 86400;
      }
      return $input;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    **/
   function prepareInputForAdd($input) {

      $input = self::isAllDayTask($input);

      $holiday = new PluginActivityHoliday();
      $holiday->setHolidays();

      if (!isset($input["plugin_activity_activitytypes_id"])
          || $input["plugin_activity_activitytypes_id"] == 0) {
         Session::addMessageAfterRedirect(__('Activity type is mandatory field', 'activity'), false, ERROR);
         return false;
      }

      $opt = new PluginActivityOption();
      $opt->getFromDB(1);
      if ($opt && $opt->fields['use_type_as_name'] == 1) {

         $input["name"] = Dropdown::getDropdownName('glpi_plugin_activity_activitytypes',
                                                    $input['plugin_activity_activitytypes_id']);
      }
      if (!isset($input["users_id"])
          || $input["users_id"] == 0) {
         Session::addMessageAfterRedirect(__('User is mandatory field', 'activity'), false, ERROR);
         return false;
      }

      if (!isset($input["begin"])
          || !isset($input["end"])) {
         Session::addMessageAfterRedirect(__('Invalid dates. Update cancelled.'), false, ERROR);
         return false;
      }

      $opt = new PluginActivityOption();
      $opt->getFromDB(1);
      $use_pairs            = $opt->fields['use_pairs'];
      $use_integerschedules = $opt->fields['use_integerschedules'];

      if ((isset($input['begin']) && ($input['begin'] != 'NULL'))
          && (isset($input['end']) && ($input['end'] != 'NULL'))) {

         $begin_hour = date('i', strtotime($input['begin']));
         $end_hour   = date('i', strtotime($input['end']));

         $delay = floor((strtotime($input['end']) - strtotime($input['begin'])) / 3600);

         if ($use_integerschedules &&
             ($begin_hour != '00' || $end_hour != '00')) {
            Session::addMessageAfterRedirect(__('Only whole hours are allowed (no split times)', 'activity'));
            return false;
         }

         if ($use_pairs == 1
             && ($delay % 2 > 0)) {

            Session::addMessageAfterRedirect(__('Only pairs schedules are allowed', 'activity'), false, ERROR);
            return false;
         }
      }

      if (!isset($input["actiontime"])
          && isset($input["begin"])
          && isset($input["end"])) {

         $input["actiontime"] = strtotime($input["end"]) - strtotime($input["begin"]);
      }

      if (isset($input["users_id"])) {
         Planning::checkAlreadyPlanned($input["users_id"], $input["begin"], $input["end"]);
      }

      if (!isset($input["is_usedbycra"])) {
         $input["is_usedbycra"] = 1;
      }

      if (PluginActivityHoliday::checkInHolidays($input, $holiday->getHolidays())) {
         Session::addMessageAfterRedirect(__('The chosen date is a public holiday', 'activity'), false, ERROR);
         return false;
      }

      $hol = new PluginActivityHoliday();
      if ($hol->isWeekend($input["begin"], true)) {
         Session::addMessageAfterRedirect(__('The chosen begin date is on weekend', 'activity'), false, ERROR);
         return false;
      }
      if ($hol->isWeekend($input["end"], false)) {
         Session::addMessageAfterRedirect(__('The chosen end date is on weekend', 'activity'), false, ERROR);
         return false;
      }

      if (isset($input["allDay"])) {
         if ($input["allDay"] == 1) {
            $AllDay              = PluginActivityReport::getAllDay();
            $input["actiontime"] = 0;
            for ($i = 0; $i < $input["days"]; $i++) {
               $input["actiontime"] += $AllDay;
            }
         }
      }

      return parent::prepareInputForAdd($input);
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
    **/
   function prepareInputForUpdate($input) {

      $input   = self::isAllDayTask($input);
      $holiday = new PluginActivityHoliday();
      $holiday->setHolidays();

      if ((!isset($input["users_id"])
           || $input["users_id"] == 0)
          && $this->fields["users_id"] == 0) {
         Session::addMessageAfterRedirect(__('User is mandatory field', 'activity'), false, ERROR);
         return false;
      }

      if ((!isset($input["plugin_activity_activitytypes_id"])
           || $input["plugin_activity_activitytypes_id"] == 0)
          && $this->fields["plugin_activity_activitytypes_id"] == 0) {
         Session::addMessageAfterRedirect(__('Activity type is mandatory field', 'activity'), false, ERROR);
         return false;
      }

      if (!isset($input["plugin_activity_activitytypes_id"])
          || $input["plugin_activity_activitytypes_id"] == 0) {
         $input["plugin_activity_activitytypes_id"] = $this->fields['plugin_activity_activitytypes_id'];
      }
      $opt = new PluginActivityOption();
      $opt->getFromDB(1);
      if ($opt && $opt->fields['use_type_as_name'] == 1) {
         $input["name"] = Dropdown::getDropdownName('glpi_plugin_activity_activitytypes', $input['plugin_activity_activitytypes_id']);
      }
      if (isset($input["users_id"])
          && isset($input["end"])) {
         Planning::checkAlreadyPlanned($input["users_id"], $this->fields["begin"], $input["end"],
                                       ['PluginActivityActivity' => [$input["id"]]]);
      }

      if (PluginActivityHoliday::checkInHolidays($input, $holiday->getHolidays())) {
         Session::addMessageAfterRedirect(__('The chosen date is a public holiday', 'activity'), false, ERROR);
         return false;
      }

      if (isset($input['plan'])) {

         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && ($input['plan']["begin"] < $input['plan']["end"])) {

            $input['_plan'] = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else {
            Session::addMessageAfterRedirect(
               __('Error in entering dates. The starting date is later than the ending date'),
               false, ERROR);
         }
      }

      $opt = new PluginActivityOption();
      $opt->getFromDB(1);
      $use_pairs            = $opt->fields['use_pairs'];
      $use_integerschedules = $opt->fields['use_integerschedules'];

      if ((isset($input['begin']) && ($input['begin'] != 'NULL'))
          && (isset($input['end']) && ($input['end'] != 'NULL'))) {

         $begin_hour = date('H', strtotime($input['begin']));
         $end_hour   = date('H', strtotime($input['end']));

         $delay = floor((strtotime($input['end']) - strtotime($input['begin'])) / 3600);

         if ($use_integerschedules &&
             ($begin_hour != '00' || $end_hour != '00')) {
            Session::addMessageAfterRedirect(__('Only whole hours are allowed (no split times)', 'activity'));
            return false;
         }

         if ($use_pairs == 1
             && ($delay % 2 > 0)) {

            Session::addMessageAfterRedirect(__('Only pairs schedules are allowed', 'activity'), false, ERROR);
            return false;
         }
      }

      if (!empty($input["begin"])
          && !empty($input["end"])
          && ($input["begin"] < $input["end"])) {
         $input["actiontime"] = strtotime($input["end"]) - strtotime($input["begin"]);
      } else {
         Session::addMessageAfterRedirect(
            __('Error in entering dates. The starting date is later than the ending date'),
            false, ERROR);
      }

      $hol = new PluginActivityHoliday();
      if ($hol->isWeekend($input["begin"], true)) {
         Session::addMessageAfterRedirect(__('The chosen begin date is on weekend', 'activity'), false, ERROR);
         return false;
      }
      if ($hol->isWeekend($input["end"], false)) {
         Session::addMessageAfterRedirect(__('The chosen end date is on weekend', 'activity'), false, ERROR);
         return false;
      }

      if (isset($input["allDay"])) {
         if ($input["allDay"] == 1) {
            $AllDay              = PluginActivityReport::getAllDay();
            $input["actiontime"] = 0;
            for ($i = 0; $i < $input["days"]; $i++) {
               $input["actiontime"] += $AllDay;
            }
         }
      }

      return parent::prepareInputForUpdate($input);
   }

   static function cleanForUser(User $user) {

      $temp = new self();
      $temp->deleteByCriteria(
         ['users_id' => $user->getField('id')]
      );

      $holiday = new PluginActivityHoliday();
      $holiday->deleteByCriteria(
         ['users_id' => $user->getField('id')]
      );

      $pref = new PluginActivityPreference();
      $pref->deleteByCriteria(
         ['users_id' => $user->getField('id')]
      );

      $pref = new PluginActivityPreference();
      $pref->deleteByCriteria(
         ['users_id_validate' => $user->getField('id')]
      );

      $val = new PluginActivityHolidayValidation();
      $val->deleteByCriteria(
         ['users_id_validate' => $user->getField('id')]
      );

   }

   static function getActionsOn() {
      global $CFG_GLPI;

      // Array of action user can do :
      //    link     -> url of link
      //    img      -> ulr of the img to show
      //    label    -> label to show
      //    onclick  -> if set, set the onclick value of the href
      //    rights   -> if true, action shown

      $listActions = [
         PluginActivityActions::ADD_ACTIVITY    => [
            'link'   => $CFG_GLPI["root_doc"] . "/front/planning.php",//plugins/activity/front/activity.form.php
            'img'    => "far fa-calendar-plus",
            'label'  => __('Add an activity', 'activity'),
            'rights' => Session::haveRight("plugin_activity", CREATE),
         ],
         PluginActivityActions::LIST_ACTIVITIES => [
            'link'   => $CFG_GLPI["root_doc"] . "/plugins/activity/front/activity.php",
            'img'    => "far fa-calendar-alt",
            'label'  => __('List of activities', 'activity'),
            'rights' => Session::haveRight("plugin_activity", READ),
         ],
         PluginActivityActions::CRA             => [
            'link'   => $CFG_GLPI["root_doc"] . "/plugins/activity/front/cra.php",
            'img'    => "far fa-calendar-check",
            'label'  => __('CRA', 'activity'),
            'rights' => Session::haveRight("plugin_activity_statistics", 1),
         ]
      ];

      return $listActions;
   }


   /**
    * Display menu
    */
   static function menu($class, $listActions, $widget = false) {
      global $CFG_GLPI;

      $i = 0;

      $allactions = [];
      foreach ($listActions as $actions) {
         if ($actions['rights']) {
            $allactions[] = $actions;
         }
      }

      $number = count($allactions);
      $return = "";
      if ($number > 0) {
         $return .= Ajax::createIframeModalWindow('holiday',
                                                  $CFG_GLPI["root_doc"] . "/plugins/activity/front/holiday.form.php",
                                                  ['title'         => __('Create a holiday request', 'activity'),
                                                   'reloadonclose' => false,
                                                   'width'         => 1180,
                                                   'height'        => 500,
                                                   'display'       => false,
                                                  ]);

         $return .= "<div align='center'>";

         if (!$widget) {
            $return .= "<table class='tab_cadre' cellpadding='5'>";
         }

         if (!$widget) {
            $return .= "<tr><th colspan='4'>" . $class::getTypeName(2) . "</th></tr>";
         } else {
            $return .= "<div class=\"tickets-stats\">";
         }

         foreach ($allactions as $action) {
            if (!$widget) {
               if ((($i % 2) == 0) && ($number > 1)) {
                  $return .= "<tr class='twhite'>";
               }
               if ($number == 1) {
                  $return .= "<tr class='twhite'>";
               }
            }
            if (!$widget) {
               $return .= "<td class='center'>";
            } else {
               $return .= "<div class='nb'>";
            }
            $return .= "<a href=\"" . $action['link'] . "\"";
            if (isset($action['onclick']) && !empty($action['onclick'])) {
               $return .= "onclick=\"" . $action['onclick'] . "\"";
            }
            $return .= ">";
            $return .= "<i class='" . $action['img'] . " fa-5x' style='color:#b5b5b5' title='" . $action['label'] . "'></i>";
            $return .= "<br><br>" . $action['label'] . "</a>";

            if (!$widget) {
               $return .= "</td>";
            } else {
               $return .= "</div>";
            }
            $i++;
            if (!$widget) {
               if (($i == $number) && (($number % 2) != 0) && ($number > 1)) {
                  $return .= "<td></td>";
                  $return .= "</tr>";
               }
            }
         }
         if ($widget) {
            $return .= "</tr>";
         } else {
            $return .= "</div>";
         }

         if (!$widget) {
            $return .= "</table>";
         }
         $return .= "</div>";
      }

      return $return;
   }

   function rawSearchOptions() {

      $activitytype = new PluginActivityActivityType();

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(1)
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'itemlink_type' => $this->getType()
      ];

      $tab[] = [
         'id'       => '3',
         'table'    => $activitytype->getTable(),
         'field'    => 'name',
         'name'     => PluginActivityActivityType::getTypeName(1),
         'datatype' => 'dropdown',
      ];


      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'begin',
         'massiveaction' => false,
         'name'          => __('Begin date'),
         'datatype'      => 'datetime'
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'end',
         'massiveaction' => false,
         'name'          => __('End date'),
         'datatype'      => 'datetime'
      ];

      $tab[] = [
         'id'       => '7',
         'table'    => $this->getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'text'
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => 'glpi_users',
         'field'         => 'name',
         'name'          => _n('User', 'Users', 1),
         'massiveaction' => false,
         'datatype'      => 'dropdown',
         'right'         => 'interface',
      ];

      $tab[] = [
         'id'            => '9',
         'table'         => $this->getTable(),
         'field'         => 'actiontime',
         'name'          => __('Total duration'),
         'nosearch'      => true,
         'massiveaction' => false,
         'datatype'      => 'timestamp',
         'withdays'      => false
      ];

      $tab[] = [
         'id'       => '10',
         'table'    => $this->getTable(),
         'field'    => 'is_usedbycra',
         'name'     => __('Use in CRA', 'activity'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'       => '30',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
      ];

      $tab[] = [
         'id'       => '80',
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'name'     => __('Entity'),
         'datatype' => 'dropdown'
      ];

      return $tab;
   }

   /**
    * Display the activity form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
    * */
   function showForm($ID, $options = []) {

      $dbu = new DbUtils();
      Html::requireJs('tinymce');
      $opt = new PluginActivityOption();
      $opt->getFromDB(1);
      $use_pairs            = $opt->fields['use_pairs'];
      $use_integerschedules = $opt->fields['use_integerschedules'];

      if ((isset($options['begin']) && ($options['begin'] != 'NULL'))
          && (isset($options['end']) && ($options['end'] != 'NULL'))) {

         $begin_hour = date('i', strtotime($options['begin']));
         $end_hour   = date('i', strtotime($options['end']));

         $delay = floor((strtotime($options['end']) - strtotime($options['begin'])) / 3600);

         if ($use_integerschedules &&
             ($begin_hour != '00' || $end_hour != '00')) {
            echo __('Only whole hours are allowed (no split times)', 'activity');
            return true;
         }
         if ($use_pairs == 1
             && ($delay % 2 > 0)) {

            echo __('Only pairs schedules are allowed', 'activity');
            return true;
         }

         $hol = new PluginActivityHoliday();
         if ($hol->isWeekend($options["begin"], true)) {
            echo __('The chosen begin date is on weekend', 'activity');
            return true;
         }
         if ($hol->isWeekend($options["end"], false)) {
            echo __('The chosen end date is on weekend', 'activity');
            return true;
         }
      }

      $canedit            = $this->can($ID, UPDATE);
      $options['colspan'] = 1;
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      if (!empty($ID)) {
         if ($this->fields["users_id"] != Session::getLoginUserID()
             && !Session::haveRight("plugin_activity_all_users", 1)) {
            return false;
         }
      }

      echo "<tr class='tab_bg_1'>";

      $opt = new PluginActivityOption();
      $opt->getFromDB(1);
      if ($opt && $opt->fields['use_type_as_name'] != 1) {
         echo "<td>" . __('Name') . "</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "name");
         echo "</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . PluginActivityActivityType::getTypeName(1) . "&nbsp;<span class='red'>*</span>";
      echo "</td>";
      echo "<td>";
      Dropdown::show('PluginActivityActivityType',
                     ['name'     => "plugin_activity_activitytypes_id",
                      'value'    => $this->fields["plugin_activity_activitytypes_id"],
                      'comments' => 1,
                      'entity'   => $this->fields["entities_id"]]);
      echo "</td>";
      echo "</tr>";
      if (isset($options['from_planning_edit_ajax'])
          && $options['from_planning_edit_ajax']) {
         echo Html::hidden('from_planning_edit_ajax');
      }

      echo "<tr class='tab_bg_2'><td>" . __('Calendar') . "</td>";
      echo "<td>";
      if (isset($options['from_planning_ajax'])
          && $options['from_planning_ajax']) {
         echo Html::hidden('begin', ['value' => $options['begin']]);
         echo Html::hidden('end', ['value' => $options['end']]);
         printf(__('From %1$s to %2$s'), Html::convDateTime($options["begin"]),
                Html::convDateTime($options["end"]));
         echo "</td>";
      } else {
         if ($canedit) {
            $begin = date("Y-m-d H:i:s");
            if (isset($this->fields["begin"])
                && !empty($this->fields["begin"])) {
               $begin = $this->fields["begin"];
            } else {
               if (isset($options["begin"])
                   && !empty($options["begin"])
               ) {
                  $begin = $options["begin"];
               }
            }

            $end = date("Y-m-d H:i:s");
            if (isset($this->fields["end"])
                && !empty($this->fields["end"])) {
               $end = $this->fields["end"];
            } else {
               if (isset($options["end"])
                   && !empty($options["end"])) {
                  $end = $options["end"];
               }
            }

            Html::showDateTimeField("begin", ['value' => $begin]);

            Html::showDateTimeField("end", ['value' => $end]);
         }

         echo "</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Use in CRA', 'activity') . "</td><td>";
      echo "<input type='hidden' name='is_planned' value='1'>";
      Dropdown::showYesNo("is_usedbycra", $this->fields["is_usedbycra"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";

      if ($canedit) {
         $rand = mt_rand();
         echo "<textarea rows='15' name='comment' id='comment_activity$rand'>" .
              $this->fields["comment"] . "</textarea>";
         Html::initEditorSystem('comment_activity' . $rand);
      } else {
         echo "<div id='kbanswer'>";
         echo Toolbox::unclean_html_cross_side_scripting_deep($this->fields["comment"]);
         echo "</div>";
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('User', 'Users', 1);
      echo "</td>";
      echo "<td>";

      if (empty($ID) && Session::haveRight("plugin_activity_all_users", 1)) {
         User::dropdown(['name'    => "users_id",
                         'value'   => Session::getLoginUserID(),
                         'right'   => "interface",
                         'comment' => 1,
                         'entity'  => $this->fields["entities_id"]]);
      } else if (empty($ID) && !Session::haveRight("plugin_activity_all_users", 1)) {
         echo $dbu->getUserName(Session::getLoginUserID());
         echo "<input type='hidden' name='users_id' value='" . Session::getLoginUserID() . "'>";
      }

      if (!empty($ID) && Session::haveRight("plugin_activity_all_users", 1)) {
         User::dropdown(['name'    => "users_id",
                         'value'   => $this->fields["users_id"],
                         'right'   => "interface",
                         'comment' => 1,
                         'entity'  => $this->fields["entities_id"]]);
      } else if (!empty($ID) && !Session::haveRight("plugin_activity_all_users", 1)) {
         echo $dbu->getUserName($this->fields["users_id"]);
         echo "<input type='hidden' name='users_id' value='" . $this->fields["users_id"] . "'>";
      }

      echo "</td>";
      echo "</tr>";

      if (!empty($ID)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo __('Total duration');
         echo "</td>";
         echo "<td>";
         echo CommonITILObject::getActionTime($this->fields['actiontime']);
         echo "</td>";
         echo "</tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   static function getAlreadyPlannedInformation($val) {
      global $CFG_GLPI;

      $out = "";

      $out .= self::getTypeName() . ' : ' . Html::convDateTime($val["begin"]) . ' -> ' .
              Html::convDateTime($val["end"]) . ' : ';
      $out .= "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/activity/front/activity.form.php?id=" .
              $val["id"] . "'>";
      $out .= Html::resume_text($val["name"], 80) . '</a>';

      return $out;
   }

   /**
    * Add items in the items fields of the parm array
    * Items need to have an unique index beginning by the begin date of the item to display
    * needed to be correcly displayed
    **/
   static function populatePlanning($options = []) {
      global $DB, $CFG_GLPI;

      $default_options = [
         'color'               => '',
         'event_type_color'    => '',
         'check_planned'       => false,
         'display_done_events' => true,
      ];
      $options         = array_merge($default_options, $options);

      $interv   = [];
      $activity = new self;

      if (!isset($options['begin']) || ($options['begin'] == 'NULL')
          || !isset($options['end']) || ($options['end'] == 'NULL')) {
         return $interv;
      }

      if (!$options['display_done_events']) {
         return $interv;
      }

      $who       = $options['who'];
      $who_group = $options['who_group'];
      $whogroup  = $options['whogroup'];
      $begin     = $options['begin'];
      $end       = $options['end'];

      // Get items to print
      $ASSIGN = "";

      if ($who_group === "mine") {
         if (count($_SESSION["glpigroups"])) {
            $groups = implode("','", $_SESSION['glpigroups']);
            $ASSIGN = "`users_id`
                           IN (SELECT DISTINCT `users_id`
                               FROM `glpi_groups_users`
                               WHERE `glpi_groups_users`.`groups_id` IN ('$groups'))
                                     AND ";
         } else { // Only personal ones
            $ASSIGN = "`users_id` = '$who'
                       AND ";
         }
      } else {
         if ($who > 0) {
            $ASSIGN = "`users_id` = '$who'
                       AND ";
         }
         if ($who_group > 0) {
            $ASSIGN = "`users_id` IN (SELECT `users_id`
                                     FROM `glpi_groups_users`
                                     WHERE `groups_id` = '$who_group')
                                           AND ";
         }
         if ($whogroup > 0) {
            $ASSIGN = "`groups_id` = '$whogroup'
                       AND ";
         }
      }

      $query = " SELECT `glpi_plugin_activity_activities`.`id`,
                        `glpi_plugin_activity_activities`.`name`,
                        `begin`,
                        `end`,
                        `users_id`,
                        `actiontime`,
                        `glpi_entities`.`name` AS entities_name,
                        `glpi_plugin_activity_activitytypes`.`name` AS type_name,
                        `glpi_plugin_activity_activities`.`comment`";
      $query .= " FROM `glpi_plugin_activity_activities` ";
      $query .= "LEFT JOIN `glpi_plugin_activity_activitytypes` 
                ON (`glpi_plugin_activity_activitytypes`.`id` = `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id`) ";
      $query .= "LEFT JOIN `glpi_entities` ON (`glpi_plugin_activity_activities`.`entities_id` = `glpi_entities`.`id`) ";
      $query .= " WHERE ";
      $query  .= " $ASSIGN ";
      $query .= " `is_planned`= 1 ";
      $dbu = new DbUtils();
      $query .= $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_activity_activities", '',
                                           $_SESSION["glpiactiveentities"], false);

      $query  .= " AND '$begin' < `end` AND '$end' > `begin`
            ORDER BY `begin` ";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {

         for ($i = 0; $data = $DB->fetch_array($result); $i++) {

            $key                              = $data["begin"] . "$$" . "PluginActivityActivity" . $data["id"];
            $interv[$key]['color']            = $options['color'];
            $interv[$key]['event_type_color'] = $options['event_type_color'];
            $interv[$key]["itemtype"]         = 'PluginActivityActivity';
            $interv[$key]["id"]               = $data["id"];
            $interv[$key]["users_id"]         = $data["users_id"];
            $interv[$key]["entities_name"]    = $data["entities_name"];

            if (strcmp($begin, $data["begin"]) > 0) {
               $interv[$key]["begin"] = $begin;
            } else {
               $interv[$key]["begin"] = $data["begin"];
            }
            if (strcmp($end, $data["end"]) < 0) {
               $interv[$key]["end"] = $end;
            } else {
               $interv[$key]["end"] = $data["end"];
            }
            $opt = new PluginActivityOption();
            $opt->getFromDB(1);
            if ($opt && $opt->fields['use_type_as_name'] != 1) {
               $interv[$key]["name"] = Html::resume_text($data["name"], $CFG_GLPI["cut"]);
            } else {
               $interv[$key]["name"] = $data["type_name"];
            }
            $interv[$key]["type"]       = $data["type_name"];
            $interv[$key]["actiontime"] = $data["actiontime"];
            $interv[$key]["content"]
                                        = Html::resume_text(Toolbox::unclean_cross_side_scripting_deep($data["comment"]),
                                                            $CFG_GLPI["cut"]);
            $interv[$key]["url"]        = $CFG_GLPI["root_doc"] . "/plugins/activity/front/activity.form.php?id=" .
                                          $data['id'];
            $interv[$key]["ajaxurl"]    = $CFG_GLPI["root_doc"] . "/ajax/planning.php" .
                                          "?action=edit_event_form" .
                                          "&itemtype=PluginActivityActivity" .
                                          "&id=" . $data['id'] .
                                          "&url=" . $interv[$key]["url"];

            $activity->getFromDB($data["id"]);
            $interv[$key]["editable"] = $activity->canUpdateItem();
         }
      }

      return $interv;

   }

   /**
    * Display a Planning Item
    *
    * @param $parm Array of the item to display
    *
    * @return Nothing (display function)
    **/
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {

      $dbu  = new DbUtils();
      $html = "";
      $rand = mt_rand();
      if ($complete) {

         if ($val["entities_name"]) {
            $html .= "<strong>" . __('Entity') . "</strong> : " . $val['entities_name'] . "<br>";
         }
         if ($val["end"]) {
            $html .= "<strong>" . __('End date') . "</strong> : " . Html::convdatetime($val["end"]) . "<br>";
         }
         if ($val["type"]) {
            $html .= "<strong>" . PluginActivityActivityType::getTypeName(1) . "</strong> : " .
                     $val["type"] . "<br>";
         }
         if ($val["users_id"] && $who != 0) {
            $html .= "<strong>" . __('User') . "</strong> : " . $dbu->getUserName($val["users_id"]) . "<br>";
         }
         if ($val["actiontime"]) {
            $html .= "<strong>" . __('Total duration') . "</strong> : " . Html::timestampToString($val['actiontime'], false) . "<br>";
         }

         $html .= "<div class='event-description'>" . $val["content"] . "</div>";
      } else {

         if ($val["entities_name"]) {
            $html .= "<strong>" . __('Entity') . "</strong> : " . $val['entities_name'] . "<br>";
         }
         if ($val["actiontime"]) {
            $html .= "<strong>" . __('Total duration') . "</strong> : " . Html::timestampToString($val['actiontime'], false) . "<br>";
         }
         $html .= Html::showToolTip($val["content"],
                                    ['applyto' => "activity_" . $val["id"] . $rand,
                                     'display' => false]);
      }

      return $html;
   }

   static function showMenu() {
      global $CFG_GLPI;

      $listActions = array_merge(self::getActionsOn(), PluginActivityHoliday::getActionsOn());

      $types = [
         PluginActivityActions::ADD_ACTIVITY,
         PluginActivityActions::HOLIDAY_REQUEST,
         PluginActivityActions::CRA,
         PluginActivityActions::APPROVE_HOLIDAYS
      ];

      foreach ($listActions as $key => $action) {
         if (in_array($key, $types) && $action['rights']) {
            echo "<tr class='tab_bg_1'>";
            echo "<td  colspan='2' class='small no-wrap'>";
            echo "<a href=\"" . $action['link'] . "\"  class='plugin_activity_button'";
            if (isset($action['onclick']) && !empty($action['onclick'])) {
               echo "onclick=\"" . $action['onclick'] . "\"";
            }
            echo ">";
            echo $action['label'];
            echo "</a>";
            echo "</td>";
            echo "</tr>";
         }
      }

      Ajax::createIframeModalWindow('holiday',
                                    $CFG_GLPI["root_doc"] . "/plugins/activity/front/holiday.form.php",
                                    ['title'         => __('Create a holiday request', 'activity'),
                                     'reloadonclose' => false,
                                     'width'         => 1180,
                                     'height'        => 500,
                                    ]);

      if (Session::haveRight("plugin_activity_can_requestholiday", 1)) {
         $holiday = new PluginActivityHoliday();
         $hcount  = new PluginActivityHolidayCount();

         $periods = $hcount->getCurrentPeriods();

         if (count($periods) > 0) {
            $nbHolidays = $holiday->countNbHoliday(Session::getLoginUserID(true), $periods, PluginActivityCommonValidation::ACCEPTED);

            if (!empty($nbHolidays[PluginActivityReport::$HOLIDAY]['total'])
                || !empty($nbHolidays[PluginActivityReport::$SICKNESS]['total'])
                || !empty($nbHolidays[PluginActivityReport::$PART_TIME]['total'])) {
               echo "<tr class='tab_bg_1'><td colspan='2'><b>" . __('Holidays detail', 'activity') . " : </b></td></tr>";
               $holiday->showHolidayDetailsByType($nbHolidays);
            }

            $nbHolidays = $holiday->countNbHoliday(Session::getLoginUserID(true), $periods, PluginActivityCommonValidation::WAITING);

            if (!empty($nbHolidays[PluginActivityReport::$HOLIDAY]['total'])
                || !empty($nbHolidays[PluginActivityReport::$SICKNESS]['total'])
                || !empty($nbHolidays[PluginActivityReport::$PART_TIME]['total'])) {
               echo "<tr class='tab_bg_1'><td colspan='2'><b>" . __('Holidays detail in progress', 'activity') . " : </b></td></tr>";
               $holiday->showHolidayDetailsByType($nbHolidays);
            }
            $nbHolidays = $holiday->countNbHolidayByPeriod(Session::getLoginUserID(true), $periods);

            $hcount->showHolidayDetailsByPeriod($nbHolidays);
         }
      }
   }

   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem = null) {
      $actions = parent::getSpecificMassiveActions($checkitem);

      if (Session::getCurrentInterface() == 'central') {
         if (Session::haveRight(self::$rightname, UPDATE)) {
            $actions['PluginActivityActivity' . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer']
               = __('Transfer');
         }
      }
      return $actions;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
    **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      switch ($ma->getAction()) {
         case 'transfer' :
            echo __('Entity') . " ";
            Dropdown::show('Entity');
            break;
      }
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'transfer' :
            $input = $ma->getInput();
            foreach ($ids as $id) {
               if ($item->can($id, UPDATE)) {
                  if ($item->update(['id' => $id, 'entities_id' => $input['entities_id']])) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;
      }
   }

   static function dateAdd($v, $d = null, $f = "Y-m-d") {
      $d = ($d ? $d : date("Y-m-d"));
      return date($f, strtotime($v . " days", strtotime($d)));
   }

   static function getNbDays($debut, $fin) {
      $diff = strtotime(date('Y-m-d', strtotime($fin))) - strtotime(date('Y-m-d', strtotime($debut)));

      return (round($diff / (3600 * 24)) + 1);
   }


   static function queryAllActivities($criteria) {

      $dbu = new DbUtils();
      $query = "SELECT `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id` AS type,
                        SUM(`glpi_plugin_activity_activities`.`actiontime`) AS total_actiontime, 
                        `glpi_plugin_activity_activitytypes`.`completename` AS name 
                     FROM `glpi_plugin_activity_activities` 
                     INNER JOIN `glpi_plugin_activity_activitytypes` 
                        ON (`glpi_plugin_activity_activitytypes`.`id` = `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id`)";
      $query .= "WHERE (`glpi_plugin_activity_activities`.`begin` >= '" . $criteria["begin"] . "' 
                  AND `glpi_plugin_activity_activities`.`begin` <= '" . $criteria["end"] . "') ";
      $query .= "  AND `glpi_plugin_activity_activities`.`users_id` = '" . $criteria["users_id"] . "' "
                . $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_activity_activities");
      $query .= " GROUP BY `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id` 
                 ORDER BY name";

      return $query;
   }


   static function queryUserActivities($criteria) {

      $dbu = new DbUtils();
      $query = "SELECT `glpi_plugin_activity_activities`.`name` AS name,
                       `glpi_plugin_activity_activities`.`id` AS id,
                       `glpi_plugin_activity_activities`.`actiontime` AS actiontime,
                       `glpi_plugin_activity_activities`.`comment` AS comment,
                       `glpi_plugin_activity_activitytypes`.`completename` AS type,
                       `glpi_plugin_activity_activities`.`allDay`,
                       `glpi_plugin_activity_activities`.`begin` AS begin,
                       `glpi_plugin_activity_activities`.`end` AS end,
                       `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id` AS type_id,
                       `glpi_entities`.`name` AS entity
               FROM `glpi_plugin_activity_activities` ";
      $query .= " LEFT JOIN `glpi_users` 
                     ON (`glpi_users`.`id` = `glpi_plugin_activity_activities`.`users_id`)";
      $query .= " LEFT JOIN `glpi_plugin_activity_activitytypes` 
                     ON (`glpi_plugin_activity_activitytypes`.`id` = `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id`)";
      $query .= " LEFT JOIN `glpi_entities` 
                     ON (`glpi_plugin_activity_activities`.`entities_id` = `glpi_entities`.`id`)";
      $query .= " WHERE ";
      $query .= "  `glpi_plugin_activity_activities`.`users_id` = '" . $criteria["users_id"] . "' "
                . $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_activity_activities") . "
                  AND (`glpi_plugin_activity_activities`.`begin` >= '" . $criteria["begin"] . "' 
                  AND `glpi_plugin_activity_activities`.`begin` <= '" . $criteria["end"] . "') ";

      if ($criteria["is_usedbycra"]) {
         $query .= " AND `glpi_plugin_activity_activities`.`is_usedbycra` ";
      }
      $query .= " AND `glpi_plugin_activity_activities`.`actiontime` != 0";
      $query .= " ORDER BY `glpi_plugin_activity_activities`.`name`";

      return $query;
   }


   static function queryManageentities($criteria) {

      $dbu = new DbUtils();
      $query = "SELECT `glpi_tickets_users`.`users_id`,
                       `glpi_entities`.`name` AS entity, 
                       `glpi_plugin_manageentities_cridetails`.`date`, 
                       `glpi_plugin_manageentities_cridetails`.`technicians`, 
                       `glpi_plugin_manageentities_cridetails`.`plugin_manageentities_critypes_id`, 
                       `glpi_plugin_manageentities_cridetails`.`withcontract`, 
                       `glpi_plugin_manageentities_cridetails`.`contracts_id`, 
                       `glpi_tickets`.`id`AS tickets_id "
               . " FROM `glpi_plugin_manageentities_cridetails` "
               . " LEFT JOIN `glpi_tickets` ON (`glpi_plugin_manageentities_cridetails`.`tickets_id` = `glpi_tickets`.`id`)"
               . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
               . " LEFT JOIN `glpi_tickets_users` ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id`)"
               . " LEFT JOIN `glpi_tickettasks` ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`)"
               . " LEFT JOIN `glpi_plugin_manageentities_critechnicians` ON (`glpi_plugin_manageentities_cridetails`.`tickets_id` = `glpi_plugin_manageentities_critechnicians`.`tickets_id`) "
               . " WHERE `glpi_tickets_users`.`type` = " . Ticket::ASSIGNED . " 
                  AND (`glpi_tickettasks`.`begin` >= '" . $criteria["begin"] . "' 
                  AND `glpi_tickettasks`.`end` <= '" . $criteria["end"] . "') "
               . " AND `glpi_tickets`.`is_deleted` = 0"
               . " AND (`glpi_tickets_users`.`users_id` ='" . $criteria["users_id"] . "' OR `glpi_plugin_manageentities_critechnicians`.`users_id` ='" . $criteria["users_id"] . "') ";
      $query .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets", '',
                                           $_SESSION["glpiactiveentities"], false);
      $query .= " AND `glpi_tickettasks`.`actiontime` != 0";
      $query .= " GROUP BY `glpi_plugin_manageentities_cridetails`.`tickets_id` ";
      $query .= " ORDER BY `glpi_plugin_manageentities_cridetails`.`date` ASC";

      return $query;
   }


   static function queryTickets($criteria) {

      $plugin = new Plugin();

      $query = "SELECT    `glpi_tickettasks`.*,
                          `glpi_plugin_activity_tickettasks`.`is_oncra`,
                          `glpi_entities`.`name` AS entity,
                          `glpi_entities`.`id` AS entities_id
                     FROM `glpi_tickettasks`
                     INNER JOIN `glpi_tickets`
                        ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id` AND `glpi_tickets`.`is_deleted` = 0)
                     LEFT JOIN `glpi_entities` 
                        ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) 
                     LEFT JOIN `glpi_plugin_activity_tickettasks` 
                        ON (`glpi_tickettasks`.`id` = `glpi_plugin_activity_tickettasks`.`tickettasks_id`) ";
      $query .= "WHERE ";
      if ($plugin->isActivated('manageentities')) {
         $query .= "`glpi_tickettasks`.`tickets_id` 
                     NOT IN (SELECT `tickets_id` 
                              FROM `glpi_plugin_manageentities_cridetails`) AND ";
      }
      $dbu = new DbUtils();
      $query .= "((`glpi_tickettasks`.`begin` >= '" . $criteria["begin"] . "' 
                           AND `glpi_tickettasks`.`end` <= '" . $criteria["end"] . "'
                           AND `glpi_tickettasks`.`users_id_tech` = '" . $criteria["users_id"] . "' " .
                $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets", '',
                                           $_SESSION["glpiactiveentities"], false);
      $query .= "                     ) 
                           OR (`glpi_tickettasks`.`date` >= '" . $criteria["begin"] . "' 
                           AND `glpi_tickettasks`.`date` <= '" . $criteria["end"] . "'
                           AND `glpi_tickettasks`.`users_id_tech` = '" . $criteria["users_id"] . "'
                           AND `glpi_tickettasks`.`begin` IS NULL " . $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets", '',
                                                                                                 $_SESSION["glpiactiveentities"], false);
      $query .= " )) AND `glpi_tickettasks`.`actiontime` != 0 AND `glpi_plugin_activity_tickettasks`.`is_oncra` = 1";
      $query .= " ORDER BY `glpi_tickettasks`.`begin` ASC";

      return $query;
   }


   /**
    * Set ticket activities for script
    *
    * @param type $tag
    * @param type $color
    * @param type $ticket
    * @param type $activities
    * @param type $is_private
    */
   function setTicketActivity($ticket = [], &$activities = [], $is_private = false) {

      $AllDay = PluginActivityReport::getAllDay();

      $opt = new PluginActivityOption();
      $opt->getFromDB(1);

      $y = 1;

      foreach ($ticket as $k => $v) {
         foreach ($v as $title => $data) {
            foreach ($data as $date => $duration) {
               if ($opt->fields['use_timerepartition'] > 0 && $opt->fields['use_mandaydisplay']) {
                  $action = PluginActivityReport::TotalTpsPassesArrondis($duration) * $AllDay;
               } else {
                  $action = $duration * $AllDay;
               }
               $content = Html::timestampToString($action, false);
               $end     = date("Y-m-d H:i:s", strtotime($date) + $action);

               $y++;
               if ($action > 0) {
                  $activities[] = [
                     'id'          => $y,
                     'title'       => $title,
                     'description' => $content . ($is_private ? " [" . __('Private') . "]" : ""),
                     'start'       => $date,
                     'end'         => $end];
               }
            }
         }
      }
   }
}