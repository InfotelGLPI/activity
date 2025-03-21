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

#[\AllowDynamicProperties]
class PluginActivityReport extends CommonDBTM {

   static $WORK             = 0;
   static $HOLIDAY          = 1;
   static $PART_TIME        = 2;
   static $SICKNESS         = 3;
   static $AM_END           = '13:00:00';
   static $PM_BEGIN         = '14:00:00';
   static $AM_LABEL         = 'am';
   static $PM_LABEL         = 'pm';
   static $ALL_DAY_LABEL    = 'allDay';
   static $DEFAULT_LABEL    = 'none';
   static $ONE_DAY_ACTIVITY = '86400';


   private $item_search = [];

   /*
    * Get glpi config AM begin
    */
   static function getAmBegin() {
      global $CFG_GLPI;

      return $CFG_GLPI["planning_begin"];
   }

   /*
    * Get glpi config PM end
    */
   static function getPmEnd() {
      global $CFG_GLPI;

      return $CFG_GLPI["planning_end"];
   }

   /*
    * Get glpi config day duration
    */
   static function getAllDay() {

      return (strtotime(self::getPmEnd()) - strtotime(self::getAmBegin())) - (strtotime(self::$PM_BEGIN) - strtotime(self::$AM_END));
   }

   /*
    *
    * @Create an HTML drop down menu
    *
    * @param string $name The element name and ID
    *
    * @param int $selected The month to be selected
    *
    * @return string
    *
    */
   static function monthDropdown($name = "month", $selected = null) {

      $dd = "<select class='form-select' name='$name'>";

      $monthsarray = Toolbox::getMonthsOfYearArray();

      foreach ($monthsarray as $k => $month) {

         $label = $monthsarray[$k];

         $dd .= '<option value="' . $k . '"';
         if ($k == $selected) {
            $dd .= ' selected';
         }
         /*** get the month ***/
         $dd .= '>' . $label . '</option>';
      }

      $dd .= "</select>";

      return $dd;
   }

   /*
    * @Create an HTML drop down menu
    *
    * @param string $name The element name and ID
    *
    * @param int $selected The month to be selected
    *
    * @return string
    *
    */
   static function YearDropdown($name = "year", $selected = null) {

      $dd   = "<select class='form-select' name='$name'>";
      $year = date("Y") - 3;
      for ($i = 0; $i <= 6; $i++) {

         $dd .= '<option value="' . $year . '"';
         if ($year == $selected) {
            $dd .= ' selected';
         }
         /*** get the year ***/
         $dd .= '>' . $year . '</option>';
         $year++;
      }

      $dd .= "</select>";

      return $dd;
   }

   static function getLastYear() {

      $year = date("Y") - 3;

      $years = [];
      for ($i = 0; $i <= 6; $i++) {
         $years[] = $year;
         $year++;
      }
      $last = array_pop($years);
      return $last;
   }

   static function showSnapshotTrigger($input) {
      //We copy the input
      echo "<form method='POST' action='cra.php'>\n";
      echo "<table class='tab_cadre'>";
      echo "<tr><td>";
      echo Html::hidden('month', ['value' => $input['month']]);
      echo Html::hidden('year', ['value' => $input['year']]);
      echo Html::hidden('users_id', ['value' => $input['users_id']]);
      echo Html::hidden('display_type', ['value' => Search::PDF_OUTPUT_LANDSCAPE]);
      echo Html::hidden('snapshot', ['value' => 'snapshot']);
      echo Html::submit(__("Take a snapshot of the CRA", "activity"), ['name' => 'submit', 'class' => 'btn btn-primary']);
      echo "</tr></td>";
      echo "</table>";

      Html::closeForm();
   }

   static function showSnapshots($input) {
      global $DB, $CFG_GLPI;

      if (!isset($input["users_id"]) || empty($input["users_id"])) {
         $users_id = Session::getLoginUserID();
      } else {
         $users_id = $input["users_id"];
      }
      $mois_courant = intval(date('m', time()));
      if (isset($input["month"])
          && $input["month"] > 0) {
         $mois_courant = $input["month"];
      }
      $annee_courante = date('Y', time());
      if (isset($input["year"])
          && $input["year"] > 0) {
         $annee_courante = $input["year"];
      }

      $doc = new Document();
      if (isset($input['delete_snapshot'])) {
         foreach ($input['delete_snapshot'] as $documents_id => $val) {
            $input['id'] = $documents_id;
            $doc->delete($input);
         }
         unset($input['delete_snapshot']);
      }

      $query = "SELECT glpi_documents.id AS documents_id
               FROM glpi_documents
               JOIN glpi_documents_items ON glpi_documents.id = glpi_documents_items.documents_id
               JOIN glpi_plugin_activity_snapshots ON glpi_documents.id = glpi_plugin_activity_snapshots.documents_id
               WHERE glpi_documents.is_deleted = '0'
               AND glpi_documents_items.itemtype='User' 
               AND glpi_documents_items.items_id=" . $users_id . " 
               AND glpi_plugin_activity_snapshots.month = " . $mois_courant . "
               AND glpi_plugin_activity_snapshots.year = " . $annee_courante . "
              ;";

      $result = $DB->query($query);
      if ($result && $DB->numrows($result) > 0) {
         echo "<div id='listSnapshots' style='margin-top:20px'>";
         echo "<form id='snapshot_form' method='POST' action='" . PLUGIN_ACTIVITY_WEBDIR . "/front/cra.php'>";
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th colspan='4'>" . __("Snapshots of the month", "activity") . "</th></tr>";
         echo "<tr><th>" . __("Name") . "</th>"
              . "<th>" . __("Document") . "</th>"
              . "<th>" . __("Date") . "</th>"
              . "<th></th></tr>";
         while ($data = $DB->fetchArray($result)) {
            $doc->getFromDB($data['documents_id']);
            echo "<tr>";
            echo "<td><a href='" . $CFG_GLPI['root_doc'] . "/front/document.form.php?id=" . $doc->fields['id'] . "' target='_blank'>" . $doc->fields['name'] . "</a></td>";
            echo "<td>" . $doc->getDownloadLink() . "</td>";
            echo "<td>" . Html::convDateTime($doc->fields['date_mod']) . "</td>";
            echo "<td>";
            $name = "delete_snapshot[" . $doc->fields['id'] . "]";
            echo Html::submit(__("Put in trashbin"), ['name' => $name, 'class' => 'btn btn-primary']);
            echo "</td>";
            echo "</tr>";
         }
         echo "</table>";
         //We copy the input
         echo Html::hidden('month', ['value' => $input['month']]);
         echo Html::hidden('year', ['value' => $input['year']]);
         echo Html::hidden('users_id', ['value' => $input['users_id']]);
         Html::closeForm();
         echo "</div>";
      }

   }


   static function takeASnapshot($crit, $input, $PDF) {

      $dbu = new DbUtils();

      $user = new User();
      $user->getFromDB($input["users_id"]);
      $name     = $user->fields['name'] . " - CRA - " . $PDF->GetNoCra(getdate());
      $filename = $name . ".pdf";
      $docpath  = GLPI_TMP_DIR;

      //Sauvegarde du PDF dans le fichier
      if (!is_dir($docpath)) {
         mkdir($docpath, 0777, true);
      }

      $PDF->Output(GLPI_TMP_DIR . "/" . $filename, 'F');

      $doc = new Document();

      $input['month'] = ltrim($input['month'], '0');
      $months    = Toolbox::getMonthsOfYearArray();
      $monthname = $months[$input['month']];

      $tab = [
         //"documentcategories_id" => 21,//PDF
         "mime"        => "application/pdf",
         "entities_id" => $_SESSION['glpiactive_entity'],
         "name"        => $user->fields['name'] . " - CRA - " . $monthname . " " . $input['year'],
         "upload_file" => $filename,
         /*       "filepath"  => $seepath.$filename,*/
         "comment"     => "**" . $input['year'] . "-" . $input['month'] . "** " . __("Snapshot taken in", "activity") . " " . Html::convDateTime(date("Y-m-d H:i:s")),
         "_no_message" => "true"
      ];

      $tab["_filename"][0] = $filename;

      $document_id = $doc->add($tab);
      if ($document_id) {
         $documentitem = new Document_Item();
         $tab          = [
            "documents_id" => $document_id,
            "entities_id"  => $_SESSION['glpiactive_entity'],
            "items_id"     => $crit["users_id"],
            "itemtype"     => $dbu->getItemTypeForTable("glpi_users"),
         ];
         $documentitem->add($tab);

         $snapshot = new PluginActivitySnapshot();
         $tab      = [
            "documents_id" => $document_id,
            "date"         => date("Y-m-d H:i:s"),
            "month"        => $input['month'],
            "year"         => $input['year']
         ];
         $snapshot->add($tab);
      }

   }

   /**
    * show dropdown for output format
    *
    * @since version 0.83
    **/
   static function showOutputFormat() {
      global $CFG_GLPI;

      echo "<select class='form-select' name='display_type'>";
      echo "<option value='" . Search::PDF_OUTPUT_LANDSCAPE . "'>" . __('Export CRA', 'activity') .
           "</option>";
      echo "</select>&nbsp;";
      echo "<button type='submit' name='export' class='submit btn btn-primary unstyled pointer' " .
           " title=\"" . _sx('button', 'Export') . "\">" .
           "<i class='ti ti-device-floppy'></i><span class='sr-only'>" . _sx('button', 'Export') . "<span>";
   }

   function showGenericSearch($input) {

      // Display type
      $output_type = Search::HTML_OUTPUT;
      if (isset ($input["display_type"])) {
         $output_type = $input["display_type"];
         if ($output_type < 0) {
            $output_type = -$output_type;
         }
      }

      $pdfMode = false;
      $PDF     = new PluginActivityCraPDF('L', 'mm', 'A4');
      if ($output_type == search::PDF_OUTPUT_LANDSCAPE) {
         $pdfMode     = true;
         $output_type = search::HTML_OUTPUT;
      }

      if (!isset($input["users_id"]) || empty($input["users_id"])) {
         $users_id = Session::getLoginUserID();
      } else {
         $users_id = $_POST["users_id"];
      }
      if ($output_type == Search::HTML_OUTPUT) {

         Html::header(PluginActivityPlanningExternalEvent::getTypeName(2));


         Html::requireJs('activity');

         echo "<div align='center'><b>" . __('Activity report', 'activity') . "</b></div><br>";
         echo "<div align='center'>";
         echo "<form method=\"post\" id=\"form_cra\" name=\"form\" action=\"cra.php\">";
         echo "<table class='tab_cadre' width='800'>";
         echo "<tr class='tab_bg_2'>";
         echo "<td><ul id='last_month_report'></ul></td>";
         echo "<td align='right'>";
         echo __('Date') . "</td>";
         echo "<td>";
         $mois_courant = intval(date('m', time()));
         if (isset($input["month"])
             && $input["month"] > 0) {
            $mois_courant = $input["month"];
         }
         echo self::monthDropdown("month", $mois_courant);
         echo "&nbsp;";
         $annee_courante = date('Y', time());
         if (isset($input["year"])
             && $input["year"] > 0) {
            $annee_courante = $input["year"];
         }
         echo self::YearDropdown("year", $annee_courante);
         echo "</td>";
         echo "<td><ul id='next_month_report'></ul></td>";

         $script = "monthActivityButton('#next_month_report', '" . __('Next month', 'activity') . "' );";
         $script .= "monthActivityButton('#last_month_report', '" . __('Previous month', 'activity') . "' );";

         echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');

         echo "<td class='tab_bg_2' align='center'>";

         if (Session::haveRight("plugin_activity_all_users", 1)) {

            User::dropdown(['name'     => "users_id",
                            'value'    => $users_id,
                            'right'    => "interface",
                            'comments' => 1,
                            'entity'   => $_SESSION["glpiactiveentities"]]);

         } else {
            echo Html::hidden('users_id', ['value' => $users_id]);
         }
         echo "</td>";
         echo "<td align='center'>";
         echo Html::submit(_sx('button', 'Post'), ['name' => 'submit', 'class' => 'btn btn-primary']);
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";

         echo "<form method='POST' action='cra.php'>\n";
         echo "<table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'><td class='center'>";

         $param = "";
         //We clear post datas before cloning
         if (isset($_POST['snapshot'])) {
            unset($_POST['snapshot']);
         }
         foreach ($_POST as $key => $val) {
            if (is_array($val)) {
               foreach ($val as $k => $v) {
                  $name = "' . $key . '[$k]";
                  echo Html::hidden($name, ['value' => $v]);
//                  echo "<input type='hidden' name='" . $key . "[$k]' value='$v' >";
                  if (!empty($param)) {
                     $param .= "&";
                  }
                  $param .= $key . "[" . $k . "]=" . urlencode($v);
               }
            } else {
               echo Html::hidden($key, ['value' => $val]);
               if (!empty($param)) {
                  $param .= "&";
               }
               $param .= "$key=" . urlencode($val);
            }
         }
         self::showOutputFormat();
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

         self::showSnapshotTrigger($input);
      }

      // Title
      if (!empty($input["users_id"])
          && !empty($input["month"])
          && !empty($input["year"])) {
         $this->showCRA($input, $output_type, $PDF, $pdfMode);
      }

      self::showSnapshots($input);

      if ($output_type == Search::HTML_OUTPUT) {

         if (Session::getCurrentInterface() == 'central') {
            Html::footer();
         } else {
            Html::helpFooter();
         }
      }
   }


   function showCRA($input, $output_type, PluginActivityCraPDF $PDF, $pdfMode = false) {
      global $CFG_GLPI, $DB;

      $AllDay = 3600*24;
       $opt = new PluginActivityOption();
       $opt->getFromDB(1);
       $use_hour_on_cra = $opt->fields['use_hour_on_cra'];
       $use_planning_activity_hours = isset($opt->fields['use_planning_activity_hours']) ? $opt->fields['use_planning_activity_hours'] : 1;
       $use_subcategory = $opt->fields['use_planningeventsubcategories'];
       if ($use_planning_activity_hours) {
           $AllDay = self::getAllDay();
       }

       $options = [
           'use_hours' => $use_hour_on_cra,
           'use_planning_activity_hours' => $use_planning_activity_hours
       ];

      $holiday = new PluginActivityHoliday();
      $holiday->setHolidays();

      // temp fix for month format
       if (!str_starts_with($input['month'], '0')) {
           if($input['month'] < 10) {
               $input['month'] = '0'.$input['month'];
           }
       }

      $crit["begin"]             = $input["year"] . "-" . $input["month"] . "-01 00:00:00";
      $lastday                   = cal_days_in_month(CAL_GREGORIAN, $input["month"], $input["year"]);
      $crit["end"]               = $input["year"] . "-" . $input["month"] . "-" . $lastday . " 23:59:59";
      $crit["users_id"]          = $input["users_id"];
      $crit["global_validation"] = PluginActivityCommonValidation::ACCEPTED;

      // 1.1 Plugin Activity
       if ($use_planning_activity_hours) {
           $query  = PluginActivityPlanningExternalEvent::queryAllExternalEvents($crit);
           $result = $DB->query($query);
           $number = $DB->numrows($result);
           // planningexternalevents total time
           $query1 = "SELECT SUM(`glpi_plugin_activity_planningexternalevents`.`actiontime`) AS total 
                  FROM `glpi_plugin_activity_planningexternalevents`
                   LEFT JOIN `glpi_planningexternalevents` 
                     ON (`glpi_plugin_activity_planningexternalevents`.`planningexternalevents_id` = `glpi_planningexternalevents`.`id`)";
           $query1 .= " WHERE (`begin` <= '" . $crit["end"] . "' 
                           AND `end` >= '" . $crit["begin"] . "')
                              AND `users_id` = '" . $crit["users_id"] . "'";
           if ($result1 = $DB->query($query1)) {
               $data1 = $DB->fetchArray($result1);
               $total = $data1["total"];
           }
       } else {
           $externalEvents = PluginActivityPlanningExternalEvent::getExternalEventWithActivityOnPeriod(
               $crit['begin'],
               $crit['end'],
               $crit['users_id'],
               true,
               $use_subcategory
           );
           $total = 0;
           $ids = []; // avoid repetition for external event with recurrences
           $actiontimeByCategories = [];
           foreach ($externalEvents as $event) {
               if (!in_array($event['id'], $ids)) {
                   $ids[] = $event['id'];
               }

               $actiontime = self::getActiontimeOnPeriod($crit['begin'], $crit['end'], $event);
               $total += $actiontime;

               // group events data by category
               $key = $event['category_name'];
               if ($use_subcategory) {
                   $key .= ' '.$event['subcategory_name'];
               }
               $clone = $event;
               $clone['actiontime'] = $actiontime;
               if (!array_key_exists($key, $actiontimeByCategories)) {
                   $actiontimeByCategories[$key] = [
                       'total_actiontime' => $actiontime,
                       'category_name' => $event['category_name'],
                       'planningeventcategories_id' => $event['planningeventcategories_id'],
                       'events' => [$clone]
                   ];
                   if ($use_subcategory) {
                       $actiontimeByCategories[$key]['subcategories_id'] = $event['subcategories_id'];
                       $actiontimeByCategories[$key]['subcategory_name'] = $event['subcategory_name'];
                   }
               } else {
                   $actiontimeByCategories[$key]['total_actiontime'] += $actiontime;
                   $actiontimeByCategories[$key]['events'][] = $clone;
               }
           }
           $number = count($ids);
       }


      if ($pdfMode) {
         $generalinformations = [];
         $monthsarray         = Toolbox::getMonthsOfYearArray();
         // format month to avoid error when getting the month name from $monthsarray
         $input['month'] = $input['month'] < 10 ? str_replace('0', '', $input['month']) : $input['month'];
         $user = new User();
         $user->getFromDB($crit["users_id"]);
         $generalinformations['name'] = self::strtoupper_auto($user->fields['realname']) . ' ' . $user->fields['firstname'];
         $generalinformations['name'] .= (!empty($user->fields['registration_number'])) ? ' (' . $user->fields['registration_number'] . ')' : '';
         $generalinformations['date'] = $monthsarray[$input["month"]] . '                      ' . $input["year"];
         $principal_client            = "";
         $opt                         = new PluginActivityOption();
         if ($opt->getFromDB(1)) {
            $principal_client = $opt->fields['principal_client'];
         }
         $generalinformations['client'] = self::strtoupper_auto($principal_client);
         $PDF->setGeneralInformations($generalinformations);
      }

      // 1.2 Plugin Manageentities
      $numberm = 0;
      if (Plugin::isPluginActive('manageentities')) {
         $config = new PluginManageentitiesConfig();
         $config->GetFromDB(1);

         $crit["documentcategories_id"] = $config->fields["documentcategories_id"];

         if ($use_planning_activity_hours) {
             $manage  = PluginActivityPlanningExternalEvent::queryManageentities($crit);
             $resultm = $DB->query($manage);
             $numberm = $DB->numrows($resultm);
         } else {
             $manageentitiesTasks = PluginActivityPlanningExternalEvent::getTicketTasksManageentities(
                 $crit['begin'],
                 $crit['end'],
                 $crit['users_id']
             );
             $numberm = count($manageentitiesTasks);
         }
      }

      // 1.3 Tickets
       if ($use_planning_activity_hours) {
           $tickets  = PluginActivityPlanningExternalEvent::queryTickets($crit);
           $resultt1 = $DB->query($tickets);
           $numbert  = $DB->numrows($resultt1);
       } else {
           $ticketTasks = PluginActivityPlanningExternalEvent::getTicketTaskWithActivityOnPeriod(
               $crit['begin'],
               $crit['end'],
               $crit['users_id'],
               true
           );
           $numbert = count($ticketTasks);
       }


      // 1.1 Plugin holiday
       // TODO implement holidays with !$use_planning_activity_hours
      $queryh  = "SELECT SUM(actiontime) AS total 
                  FROM `glpi_plugin_activity_holidays`";
      $queryh  .= " WHERE (`begin` >= '" . $crit["begin"] . "' 
                           AND `begin` <= '" . $crit["end"] . "')
                        AND `users_id` = '" . $crit["users_id"] . "'";
      $resulth = $DB->query($queryh);
      $numberh = $DB->numrows($resulth);

      // ProjectTask
      $tickets  = PluginActivityProjectTask::queryProjectTask($crit);
      $resultpt = $DB->query($tickets);
      $numberpt = $DB->numrows($resultpt);

      if ($number != "0" || $numberm != "0" || $numbert != "0" || $numberh != "0" || $numberpt != "0") {

         // 2.2 Details
         $title  = [];
         $values = [];

         // 2.3 Plugin Activity
          if ($use_planning_activity_hours) {
              $crit["is_usedbycra"] = true;
              $query2 = PluginActivityPlanningExternalEvent::queryUserExternalEvents($crit);

              $result2 = $DB->query($query2);
              if ($DB->numrows($result2)) {
                  while ($data2 = $DB->fetchArray($result2)) {
                      if (isset($data2['rrule']) && !empty($data2['rrule'])) {
                          $duration = strtotime($data2['end']) - strtotime($data2['begin']);
                          $rrule = json_decode($data2['rrule'], 1);
                          $rset = PlanningExternalEvent::getRsetFromRRuleField($rrule, $data2['begin']);
                          $begin_datetime = new DateTime($crit['begin'], new DateTimeZone('UTC'));
                          $begin_datetime->sub(new DateInterval("PT" . ($duration - 1) . "S"));
                          $end_datetime = new DateTime($crit['end'], new DateTimeZone('UTC'));
                          $occurences = $rset->getOccurrencesBetween($begin_datetime, $end_datetime);

                          // add the found occurences to the final tab after replacing their dates
                          foreach ($occurences as $currentDate) {
                              $occurence_begin = $currentDate;
                              $data2['begin'] = $occurence_begin->format('Y-m-d H:i:s');

                              $occurence_end = (clone $currentDate)->add(new DateInterval("PT" . $duration . "S"));
                              $data2['end'] = $occurence_end->format('Y-m-d H:i:s');
                              //                     $type = $data2["type"];
                              //                     $parents = $dbu->getAncestorsOf("glpi_planningeventcategories", $data2["type_id"]);
                              //                     $last = end($parents);

                              if ($holiday->isWeekend($data2['begin'], true)) {
                                  continue;
                              }
                              if ($opt->fields['show_planningevents_entity']) {
                                  $type = self::strtoupper_auto($data2["entity"]) . " > ";
                                  if (empty($data2["type"])) {
                                      $type .= __('No defined type', 'activity');
                                  } else {
                                      $type .= $data2["type"];
                                      if ($opt->getUseSubcategory() && $data2['subtype']) {
                                          $type .= ' - ' . $data2['subtype'];
                                      }
                                  }
                              } else {
                                  if (empty($data2["type"])) {
                                      $type = self::strtoupper_auto($data2["entity"]) . " > " . __(
                                              'No defined type',
                                              'activity'
                                          );
                                  } else {
                                      $type = $data2["type"];
                                      if ($opt->getUseSubcategory() && $data2['subtype']) {
                                          $type .= ' - ' . $data2['subtype'];
                                      }
                                  }
                              }

                              $title[$type][] = $data2["begin"];

                              $opt_act = self::$WORK;

                              //All Day planning Case
                              $begin = strtotime($data2["begin"]);
                              $end = strtotime($data2["end"]);
                              $datediff = $end - $begin;

                              $nbday = round($datediff / (60 * 60 * 24));

                              if ($pos = strpos($data2["begin"], '00:00:00') !== false) {
                                  $action = $data2['actiontime'] / 3600;
                                  $action = $action - (8 * $nbday);
                                  $data2['actiontime'] = $action * 3600;
                              }
                              if ($pos = strpos('00:00:00', '00:00:00') !== false) {
                                  $action = $data2['actiontime'] / 3600;
                                  $action = $action - (8 * $nbday);
                                  $data2['actiontime'] = $action * 3600;
                              }
                              //End All Day planning Case
                              // Repartition of the time
                              $values = $this->timeRepartition(
                                  $data2['actiontime'] / $AllDay,
                                  $data2["begin"],
                                  $values,
                                  $opt_act,
                                  $type,
                                  $holiday->getHolidays(),
                                  $options
                              );


                              $this->item_search[$type]['PlanningExternalEvent'][date(
                                  'Y-m-d',
                                  strtotime($data2["begin"])
                              )][] = $data2['id'];
                          }
                      } else {
                          if ($opt->fields['show_planningevents_entity']) {
                              $type = self::strtoupper_auto($data2["entity"]) . " > ";
                              if (empty($data2["type"])) {
                                  $type .= __('No defined type', 'activity');
                              } else {
                                  $type .= $data2["type"];
                                  if ($opt->getUseSubcategory() && $data2['subtype']) {
                                      $type .= ' - ' . $data2['subtype'];
                                  }
                              }
                          } else {
                              if (empty($data2["type"])) {
                                  $type = self::strtoupper_auto($data2["entity"]) . " > " . __(
                                          'No defined type',
                                          'activity'
                                      );
                              } else {
                                  $type = $data2["type"];
                                  if ($opt->getUseSubcategory() && $data2['subtype']) {
                                      $type .= ' - ' . $data2['subtype'];
                                  }
                              }
                          }

                          $title[$type][] = $data2["begin"];

                          $opt_act = self::$WORK;

                          //All Day planning Case
                          $begin = strtotime($data2["begin"]);
                          $end = strtotime($data2["end"]);
                          $datediff = $end - $begin;

                          $nbday = round($datediff / (60 * 60 * 24));

                          if ($pos = strpos($data2["begin"], '00:00:00') !== false) {
                              $action = $data2['actiontime'] / 3600;
                              $action = $action - (8 * $nbday);
                              $data2['actiontime'] = $action * 3600;
                          }
                          if ($pos = strpos('00:00:00', '00:00:00') !== false) {
                              $action = $data2['actiontime'] / 3600;
                              $action = $action - (8 * $nbday);
                              $data2['actiontime'] = $action * 3600;
                          }
                          //End All Day planning Case
                          // Repartition of the time
                          $values = $this->timeRepartition(
                              $data2['actiontime'] / $AllDay,
                              $data2["begin"],
                              $values,
                              $opt_act,
                              $type,
                              $holiday->getHolidays(),
                              $options
                          );

                          $this->item_search[$type]['PlanningExternalEvent'][date(
                              'Y-m-d',
                              strtotime($data2["begin"])
                          )][] = $data2['id'];
                      }
                  }
              }
          } else {
            foreach($externalEvents as $externalEvent) {
                if ($opt->fields['show_planningevents_entity']) {
                    $type = self::strtoupper_auto($externalEvent["entity_name"]) . " > ";
                    if (empty($externalEvent["category_name"])) {
                        $type .= __('No defined type', 'activity');
                    } else {
                        $type .= $externalEvent["category_name"];
                        if ($opt->getUseSubcategory() && $externalEvent['subcategory_name']) {
                            $type .= ' - ' . $externalEvent['subcategory_name'];
                        }
                    }
                } else {
                    if (empty($externalEvent["category_name"])) {
                        $type = self::strtoupper_auto($externalEvent["entity_name"]) . " > " . __(
                                'No defined type',
                                'activity'
                            );
                    } else {
                        $type = $externalEvent["category_name"];
                        if ($opt->getUseSubcategory() && $externalEvent['subcategory_name']) {
                            $type .= ' - ' . $externalEvent['subcategory_name'];
                        }
                    }
                }

                $title[$type][] = $externalEvent["begin"];

                $dateTimeBegin = new DateTime($externalEvent['begin']);
                $dateTimeEnd =  new DateTime($externalEvent['end']);
                $this->addEventToValues(
                    $values,
                    $dateTimeBegin,
                    $dateTimeEnd,
                    $crit,
                    $externalEvent,
                    self::$WORK,
                    $type,
                    'PlanningExternalEvent',
                    $externalEvent['id']
                );
            }
          }


         // 2.3 Plugin Activity holidays
         if (Session::haveRight("plugin_activity_can_requestholiday", 1)) {
            $crit["is_usedbycra"] = true;
            $queryh               = PluginActivityHoliday::queryUserHolidays(array_merge($crit,
                                                                                         ['begin'             => date('Y-m-d H:i:s', strtotime($crit["begin"] . " - 1 MONTH")),
                                                                                          'end'               => $crit["end"],
                                                                                          'global_validation' => PluginActivityCommonValidation::ACCEPTED]));

            $resulth = $DB->query($queryh);
            if ($DB->numrows($resulth)) {
               while ($datah = $DB->fetchArray($resulth)) {
                  if (empty($datah["type"])) {
                     $type = $datah["entity"] . " > " . __('No defined type', 'activity');
                  } else {
                     $type = $datah["type"];
                  }

                  $title[$type][] = $datah["begin"];

                  $opt_act = self::$HOLIDAY;

                  // Repartition of the time
                  $values = $this->timeRepartition($datah['actiontime'] / $AllDay, $datah["begin"], $values, $opt_act, $type, $holiday->getHolidays(), $options);
                  if (!isset($this->holiday_type[$type])) {
                     $this->holiday_type[$type]['is_holiday']   = $datah['is_holiday'];
                     $this->holiday_type[$type]['is_sickness']  = $datah['is_sickness'];
                     $this->holiday_type[$type]['is_part_time'] = $datah['is_part_time'];
                  }
                  $this->item_search[$type]['PluginActivityHoliday'][date('Y-m-d', strtotime($datah["begin"]))][] = $datah['id'];
               }
            }
         }

         // 1.3 Tickets
         if ($numbert != "0") {
             if ($use_planning_activity_hours) {
                 while ($datat = $DB->fetchArray($resultt1)) {
                     $mtitle = self::strtoupper_auto($datat['entity']) . " > " . __('Unbilled', 'activity');
                     $internal = PluginActivityConfig::getConfigFromDB($datat['entities_id']);
                     if ($internal) {
                         foreach ($internal as $field) {
                             $mtitle = self::strtoupper_auto($datat['entity']) . " > " . $field["name"];
                         }
                     }
                     if (!empty($datat["begin"]) && !empty($datat["end"])) {
                         $values = $this->timeRepartition($datat['actiontime'] / $AllDay, $datat["begin"], $values, self::$WORK, $mtitle, $holiday->getHolidays(), $options);
                         $this->item_search[$mtitle]['Ticket'][date('Y-m-d', strtotime($datat["begin"]))][] = $datat['tickets_id'];
                     } else {
                         $values = $this->timeRepartition($datat['actiontime'] / $AllDay, $datat["date"], $values, self::$WORK, $mtitle, $holiday->getHolidays(), $options);
                         $this->item_search[$mtitle]['Ticket'][date('Y-m-d', strtotime($datat["date"]))][] = $datat['tickets_id'];
                     }
                 }
             } else {
                 foreach($ticketTasks as $ticketTask) {
                     $mtitle = self::strtoupper_auto($ticketTask['entity_name']) . " > " . __('Unbilled', 'activity');
                     $internal = PluginActivityConfig::getConfigFromDB($ticketTask['entities_id']);
                     if ($internal) {
                         foreach ($internal as $field) {
                             $mtitle = self::strtoupper_auto($ticketTask['entity_name']) . " > " . $field["name"];
                         }
                     }
                     if (!empty($ticketTask["begin"]) && !empty($ticketTask["end"])) {
                         $dateTimeBegin = new DateTime($ticketTask['begin']);
                         $dateTimeEnd =  new DateTime($ticketTask['end']);
                     } else {
                         $dateTimeEnd =  new DateTime($ticketTask['date']);
                         $dateTimeBegin = clone $dateTimeEnd;
                         $dateTimeBegin->modify('-' . $ticketTask['actiontime'] . ' seconds');
                     }
                     $this->addEventToValues(
                         $values,
                         $dateTimeBegin,
                         $dateTimeEnd,
                         $crit,
                         $ticketTask,
                         self::$WORK,
                         $mtitle,
                         'Ticket',
                         $ticketTask['tickets_id']
                     );
                 }
             }
         }

         // 2.4 Plugin Manageentities
         if (Plugin::isPluginActive('manageentities')) {
            if ($numberm != "0") {
                // TODO implement $use_planning_activity_hours
               while ($datam = $DB->fetchArray($resultm)) {

                  $queryTask = "SELECT `glpi_tickettasks`.*
                                 FROM `glpi_tickettasks`
                                 LEFT JOIN `glpi_plugin_manageentities_cridetails`
                                    ON (`glpi_plugin_manageentities_cridetails`.`tickets_id` = `glpi_tickettasks`.`tickets_id`)
                                 LEFT JOIN `glpi_plugin_activity_tickettasks`
                                    ON (`glpi_plugin_activity_tickettasks`.`tickettasks_id` = `glpi_tickettasks`.`id`)
                                 WHERE `glpi_tickettasks`.`tickets_id` = '" . $datam['tickets_id'] . "'
                                       AND (`glpi_tickettasks`.`begin` >= '" . $crit["begin"] . "' 
                                       AND `glpi_tickettasks`.`end` <= '" . $crit["end"] . "')
                                       AND `glpi_plugin_activity_tickettasks`.`is_oncra` = 1
                                       AND `glpi_tickettasks`.`users_id_tech` = '" . $crit["users_id"] . "'";

                  $resultTask = $DB->query($queryTask);
                  $numberTask = $DB->numrows($resultTask);
                  if ($numberTask != "0") {
                     while ($dataTask = $DB->fetchArray($resultTask)) {
                         $mtitle = self::strtoupper_auto($datam["entity"]) . " > ";
                        if ($datam["withcontract"]) {
                           $contract = new Contract();
                           $contract->getFromDB($datam["contracts_id"]);
                           $mtitle .= $contract->fields["num"];
                        }
                        $title[$mtitle][] = $dataTask["begin"];

                        // Repartition of the time
                        $values = $this->timeRepartition($dataTask['actiontime'] / $AllDay, $dataTask["begin"], $values, self::$WORK, $mtitle, $holiday->getHolidays(), $options);

                        $this->item_search[$mtitle]['Ticket'][date('Y-m-d', strtotime($dataTask["begin"]))][] = $datam['tickets_id'];
                     }
                  }
               }
            }
         }

         //Project task
         $opt = new PluginActivityOption();
         if ($opt->getFromDB(1) && $opt->getUseProject()) {
            if ($numberpt != "0") {
               while ($datapt = $DB->fetchArray($resultpt)) {
                  $project = new Project();
                  $project->getFromDB($datapt["projects_id"]);
                  $mtitle = self::strtoupper_auto($project->getName()) . " > " . ProjectTask::getTypeName();
                  if ($datapt['planned_duration'] == 0) {
                     //$timestart        = strtotime($datapt["plan_start_date"]);

                     $count            = PluginActivityPlanningExternalEvent::getNbDays($datapt["plan_start_date"], $datapt["plan_end_date"]);
                     $planned_duration = 0;
                     $timestart        = new DateTime($datapt["plan_start_date"]);
                     $timeendDay       = new DateTime($datapt["plan_start_date"]);
                     $timeendFinal     = new DateTime($datapt["plan_end_date"]);
                     for ($i = 1; $i < $count; $i++) {
                        $hol = new PluginActivityHoliday();
                        if (!$hol->isWeekend($timestart->format('Y-m-d H:i:s'))) {
                           if (date('H:i:s', strtotime($timestart->format('Y-m-d H:i:s'))) <= self::getAmBegin() || $i > 1) {
                              $timestart->setTime(substr(self::getAmBegin(), 0, 2),
                                                  substr(self::getAmBegin(), 3, 2),
                                                  substr(self::getAmBegin(), 6, 2));
                              $timeendDay->setTime(substr(self::getPmEnd(), 0, 2),
                                                   substr(self::getPmEnd(), 3, 2),
                                                   substr(self::getPmEnd(), 6, 2));
                           } else {
                              $timeendDay->setTime(substr(self::getPmEnd(), 0, 2),
                                                   substr(self::getPmEnd(), 3, 2),
                                                   substr(self::getPmEnd(), 6, 2));
                           }
                           if (date('H:i:s', strtotime($timeendFinal->format('Y-m-d H:i:s'))) > self::getPmEnd()) {
                              $timeendFinal->setTime(substr(self::getPmEnd(), 0, 2),
                                                     substr(self::getPmEnd(), 3, 2),
                                                     substr(self::getPmEnd(), 6, 2));
                           }
                           $planned_duration = (strtotime($timeendDay->format('Y-m-d H:i:s')) -
                                                strtotime($timestart->format('Y-m-d H:i:s'))) -
                                               (strtotime(self::$PM_BEGIN) - strtotime(self::$AM_END));

                           $values = $this->timeRepartition($planned_duration / $AllDay, $timestart->format('Y-m-d H:i:s'),
                                                            $values, self::$WORK, $mtitle, $holiday->getHolidays(), $options);
                        }
                        $timestart->modify('+1 day');
                        $timeendDay->modify('+1 day');
                     }
                     $midi = 0;
                     if (strtotime(date('H:i:s', strtotime($timeendFinal->format('Y-m-d H:i:s')))) > strtotime(self::$AM_END)) {
                        $midi = strtotime(self::$PM_BEGIN) - strtotime(self::$AM_END);
                     }
                     $planned_duration = (strtotime($timeendFinal->format('Y-m-d H:i:s')) - strtotime($timestart->format('Y-m-d H:i:s'))
                                          - ($midi));
                     $values           = $this->timeRepartition($planned_duration / $AllDay, $timestart->format('Y-m-d H:i:s'),
                                                                $values, self::$WORK, $mtitle, $holiday->getHolidays(), $options);
                  } else {
                     $values = $this->timeRepartition($datapt['planned_duration'] / $AllDay, $datapt["plan_start_date"],
                                                      $values, self::$WORK, $mtitle, $holiday->getHolidays(), $options);
                  }
                  $this->item_search[$mtitle]['ProjectTask'][date('Y-m-d', strtotime($datapt["plan_start_date"]))][] = $datapt['projects_id'];
               }
            }
         }

         foreach ($title as $k => $v) {
            $v         = array_unique($v);
            $title[$k] = $v;
         }

         $count = PluginActivityPlanningExternalEvent::getNbDays($crit["begin"], $crit["end"]);

         $countopened  = 0;
         $countwe      = 0;
         $countweekend = 0;
         $days         = [];
         $num          = 1;
         echo Search::showHeader($output_type, $number + $numberm + $numbert, 3, false);
         echo Search::showNewLine($output_type);
         self::showTitle($output_type, $num, '', '', false);
         self::showTitle($output_type, $num, '', '', false);

         /*joursem*/
         $joursem = ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'];
         //         $We = array('Di', 'Sa');
         $timeHeaders = [];
         for ($i = 0; $i != $count; $i++) {
            $d = PluginActivityPlanningExternalEvent::dateAdd($i, $crit["begin"]);
            list($annee, $mois, $jour) = explode('-', $d);
            $timestamp = mktime(0, 0, 0, $mois, $jour, $annee);
            self::showTitle($output_type, $num, $joursem[date("w", $timestamp)], 'day', false);
            $timeHeaders[$jour]['header'] = $joursem[date("w", $timestamp)];
            $timeHeaders[$jour]['values'] = 0;
            $countopened++;

            $countweekend += $holiday->countWe($d, $d, $holiday->getHolidays());
            // In week end ?
            if ($holiday->countWe($d, $d, $holiday->getHolidays()) > 0) {
               $timeHeaders[$jour]['options']['weekend'] = true;
            }
         }

         if ($pdfMode) {
            $PDF->SetTimeHeader($timeHeaders);
         }

         self::showTitle($output_type, $num, '', '', false);
         echo Search::showEndLine($output_type);

         /*days*/
         echo Search::showNewLine($output_type);
         self::showTitle($output_type, $num, __('Project', 'activity'), '', false);
         self::showTitle($output_type, $num, __('Activity', 'activity'), '', false);
         for ($i = 0; $i != $count; $i++) {
            $d      = PluginActivityPlanningExternalEvent::dateAdd($i, $crit["begin"]);
            $days[] = $d;
            list($annee, $mois, $jour) = explode('-', $d);
            self::showTitle($output_type, $num, $jour, 'day', false);
         }
         self::showTitle($output_type, $num, __('Total'), '', false);
         echo Search::showEndLine($output_type);

         $titles = [];
         foreach ($days as $k => $v) {
            foreach ($title as $tab => $tabval) {
               if (in_array($v, $tabval)) {
                  $titles[] = [$v => $tab];
               }
            }
         }

         $activities = [];
         foreach ($values as $type => $data) {
            foreach ($data as $name => $day) {
               $count = 0;
               foreach ($days as $k => $v) {
                  foreach ($day as $date => $val1) {
                     //                     if (array_key_exists($morning, $day)) {
                     if ($v == date('Y-m-d', strtotime($date))) {
                        if (isset($activities[$type][$name][$count][$v])) {
                           $activities[$type][$name][$count][$v] += $val1;
                        } else {
                           $activities[$type][$name][$count][$v] = $val1;
                        }
                     } else if (!isset($activities[$type][$name][$count][$v])) {
                        $activities[$type][$name][$count][$v] = 0;
                     }
                  }
                  $count++;
               }
            }
         }

         /*List titles*/
         $row_num                 = 1;
         $time[self::$WORK]       = [];
         $time[self::$HOLIDAY]    = [];
         $time[self::$HOLIDAY][1] = [];
         $time[self::$HOLIDAY][2] = [];
         $time[self::$HOLIDAY][3] = [];
         $time_total              = [];
         foreach ($activities as $type => $data) {
            foreach ($data as $key => $valt) {

               //1 for holiday, 2 for sickness, 3 for part time by default its 1 for holiday
               if (isset($this->holiday_type[$key]['is_holiday']) && $this->holiday_type[$key]['is_holiday'] == 1) {
                  $holiday_type_type = 1;
               } else if (isset($this->holiday_type[$key]['is_sickness']) && $this->holiday_type[$key]['is_sickness'] == 1) {
                  $holiday_type_type = 2;
               } else if (isset($this->holiday_type[$key]['is_part_time']) && $this->holiday_type[$key]['is_part_time'] == 1) {
                  $holiday_type_type = 3;
               } else {
                  $holiday_type_type = 1;
               }

               foreach ($valt as $val) {
                  foreach ($val as $date_act => $val_act) {
                     $value = "";

                     // Value
                     if ($val_act > 0) {
                        //TODO Check why ?
                        //$value = Html::formatNumber($val_act, false, 3);
                        $value = $val_act;
                     }

                     // In week end ?
                     $countwe = $holiday->countWe($date_act, $date_act, $holiday->getHolidays());
                     if ($countwe > 0) {
                        if ($type == self::$WORK) {
                           $time[$type][$key][$date_act]['options']['weekend'] = true;
                        } else if ($type == self::$HOLIDAY) {
                           $time[$type][$holiday_type_type][$date_act]['options']['weekend'] = true;
                        }
                     }

                     // Value correct depass
                     if ($val_act > 1) {
                        if ($type == self::$WORK) {
                           $time[$type][$key][$date_act]['options']['depass'] = true;
                        } else if ($type == self::$HOLIDAY) {
                           $time[$type][$holiday_type_type][$date_act]['options']['depass'] = true;
                        }
                     }

                     if ($type == self::$WORK) {
                        if (isset($time[$type][$key][$date_act]['values'])) {
                           $time[$type][$key][$date_act]['values'] += $value;
                        } else {
                           $time[$type][$key][$date_act]['values'] = $value;
                        }
                     } else if ($type == self::$HOLIDAY) {
                        if (!isset($time[$type][$holiday_type_type][$date_act]['values'])) {
                           $time[$type][$holiday_type_type][$date_act]['values'] = (float)$value;
                        } else {
                           if ($value > 0) {
                              $time[$type][$holiday_type_type][$date_act]['values'] += (float)$value;
                           }
                        }
                     }
                  }
               }
            }
         }

         // Show work
         $tot_activity = $this->showActivity($time[self::$WORK], $output_type, $row_num, self::$WORK, $crit);
         // Show holiday
         $row_num++;
         $num = 1;
         echo Search::showNewLine($output_type);
         self::showTitle($output_type, $num, __('Absences', 'activity'), '', false);
         self::showTitle($output_type, $num, __('Comments'), '', false);

         for ($i = 0; $i != $count; $i++) {
            $d     = PluginActivityPlanningExternalEvent::dateAdd($i, $crit["begin"]);
            $class = "class='center'";
            $style = "";
            if ($holiday->countWe($d, $d, $holiday->getHolidays()) > 0) {
               $style = " style='background-color:#CCCCCC' ";
            }
            echo Search::showItem($output_type, '', $num, $row_num, $class . $style);
         }

         echo Search::showItem($output_type, '', $num, $row_num);
         echo Search::showEndLine($output_type);

         $tot_parttime = $this->showActivity($time[self::$HOLIDAY][3], $output_type, $row_num, self::$PART_TIME, $crit);
         $tot_holiday  = $this->showActivity($time[self::$HOLIDAY][1], $output_type, $row_num, self::$HOLIDAY, $crit);
         $tot_sickness = $this->showActivity($time[self::$HOLIDAY][2], $output_type, $row_num, self::$SICKNESS, $crit);

         // Show total of all
         $this->showTotal($tot_activity + $tot_holiday + $tot_parttime + $tot_sickness, $count, $countopened - $countweekend, $output_type, $row_num, $time);

         if ($pdfMode) {
            $showPopUp = false; //We don't always show the popup with the pdf CRA
            $PDF->SetTime($time[self::$WORK]);
            $PDF->SetHoliday($time[self::$HOLIDAY][1], $time[self::$HOLIDAY][2], $time[self::$HOLIDAY][3]);

            //On définit le pied de page du PDF
            $cra_footer = '';
            $opt        = new PluginActivityOption();
            $opt->getFromDB(1);
            if ($opt) {
               $cra_footer = explode("\n", $opt->fields['cra_footer']);
            }

            $PDF->setFooterInformations($cra_footer);

            // On dessine le document
            $PDF->DrawCra();

            // Si l'utilisateur souhaite réaliser un snapshot
            if (isset($input['snapshot'])) {
               self::takeASnapshot($crit, $input, $PDF);
            } else { // Si il ne souhaite pas -> on affiche le CRA pdf dans une Popup
               $user = new User();
               $user->getFromDB($input["users_id"]);
               $filename = $user->fields['name'] . " - CRA - " . $PDF->GetNoCra(getdate()) . ".pdf";
               $seepath  = GLPI_PLUGIN_DOC_DIR . "/activity/";

               //Sauvegarde du PDF dans le fichier
               if (!is_dir($seepath)) {
                  mkdir($seepath);
               }
               $PDF->Output($seepath . "/" . $filename, 'F');

               $showPopUp = true;
            }
         }

         echo Search::showFooter($output_type, PluginActivityPlanningExternalEvent::getTypeName(1));

         if ($number != "0") {
            $num = 1;
            echo "<br>";
            echo Search::showHeader($output_type, $number + $numberm + $numbert, 3, true);
            echo Search::showNewLine($output_type);
            self::showTitle($output_type, $num, __('Activities detail', 'activity'), '', false);
            echo Search::showEndLine($output_type);
            echo Search::showHeader($output_type, $number + $numberm + $numbert, 3, true);
            echo Search::showNewLine($output_type);
            self::showTitle($output_type, $num, PlanningEventCategory::getTypeName(2), 'activitytype', false);
             if ($opt->getUseSubcategory()) {
                self::showTitle($output_type, $num, PluginActivityPlanningeventsubcategory::getTypeName(2), 'activitysubtype', false);
             }
            self::showTitle($output_type, $num, __('Comments'), 'comment', false);
            self::showTitle($output_type, $num, __('Total'), 'hours', false);
            self::showTitle($output_type, $num, __('Percent', 'activity'), 'percent', false);
            echo Search::showEndLine($output_type);

            // 2.1 Spew out the data in a table
            $row_num = 1;
            if ($use_planning_activity_hours) {
                while ($data = $DB->fetchArray($result)) {
                    if ($data["total_actiontime"] > 0) {
                        $percent = $data["total_actiontime"] * 100 / $total;
                    } else {
                        $percent = 0;
                    }

                    $row_num++;
                    $num = 1;
                    echo Search::showNewLine($output_type);
                    echo Search::showItem($output_type, $data["name"], $num, $row_num);
                    if ($opt->getUseSubcategory()) {
                        echo Search::showItem($output_type, $data["subname"], $num, $row_num);
                    }
                    $comment = "";
                    $dbu     = new DbUtils();
                    $queryt  = "SELECT `glpi_planningexternalevents`.`text` AS text,
                                 `glpi_plugin_activity_planningexternalevents`.`actiontime`
                     FROM `glpi_planningexternalevents` 
                     INNER JOIN `glpi_planningeventcategories` 
                        ON (`glpi_planningeventcategories`.`id` = `glpi_planningexternalevents`.`planningeventcategories_id`)
                     INNER JOIN `glpi_plugin_activity_planningexternalevents` 
                              ON (`glpi_planningexternalevents`.`id` = `glpi_plugin_activity_planningexternalevents`.`planningexternalevents_id`)";
                    $queryt  .= "WHERE (`glpi_planningexternalevents`.`begin` >= '" . $crit["begin"] . "' 
                           AND `glpi_planningexternalevents`.`begin` <= '" . $crit["end"] . "') 
                           AND `glpi_planningexternalevents`.`planningeventcategories_id` = '" . $data["type"] . "'";

                    if ($opt->getUseSubcategory()) {
                        $queryt .= "AND `glpi_plugin_activity_planningexternalevents`.`planningeventsubcategories_id`";
                        $queryt .= $data['subtype'] ?  " = '".$data["subtype"]."' " : ' IS NULL ';
                    }

                    $queryt  .= "  AND `glpi_planningexternalevents`.`users_id` = '" . $crit["users_id"] . "' "
                        . $dbu->getEntitiesRestrictRequest("AND", "glpi_planningexternalevents");

                    $resultt = $DB->query($queryt);
                    $numbert = $DB->numrows($resultt);
                    if ($numbert != "0") {
                        while ($datat = $DB->fetchArray($resultt)) {
                            $comment .= $datat["text"] . " (" . (self::TotalTpsPassesArrondis($datat["actiontime"] / $AllDay, $options)) . ")<br>";
                        }
                    }
                    echo Search::showItem($output_type, nl2br(Glpi\RichText\RichText::getTextFromHtml($comment)), $num, $row_num);
                    $total_ouvres = self::TotalTpsPassesArrondis($data["total_actiontime"] / $AllDay, $options);
                    echo Search::showItem($output_type, Html::formatNumber($total_ouvres, false, 3), $num, $row_num);
                    echo Search::showItem($output_type, Html::formatNumber($percent) . "%", $num, $row_num);
                    echo Search::showEndLine($output_type);
                }
            } else {
                foreach($actiontimeByCategories as $category => $data) {
                    if ($data['total_actiontime'] > 0) {
                        $percent = $data['total_actiontime'] * 100 / $total;
                    } else {
                        $percent = 0;
                    }

                    $row_num++;
                    $num = 1;
                    echo Search::showNewLine($output_type);
                    echo Search::showItem($output_type, $data["category_name"], $num, $row_num);
                    if ($opt->getUseSubcategory()) {
                        echo Search::showItem($output_type, $data["subcategory_name"], $num, $row_num);
                    }
                    $comment = "";

                    $numbert = count($data['events']);
                    if ($numbert != "0") {
                        foreach ($data['events'] as $datat) {
                            $comment .= $datat["text"] . " (" . (self::TotalTpsPassesArrondis($datat["actiontime"] / $AllDay, $options)) . ")<br>";
                        }
                    }
                    echo Search::showItem($output_type, nl2br(Glpi\RichText\RichText::getTextFromHtml($comment)), $num, $row_num);
                    $total_ouvres = self::TotalTpsPassesArrondis($data["total_actiontime"] / $AllDay, $options);
                    echo Search::showItem($output_type, Html::formatNumber($total_ouvres, false, 3), $num, $row_num);
                    echo Search::showItem($output_type, Html::formatNumber($percent) . "%", $num, $row_num);
                    echo Search::showEndLine($output_type);
                }
            }

            echo Search::showFooter($output_type, PluginActivityPlanningExternalEvent::getTypeName(1));
         }

      } else {
         echo Search::showHeader($output_type, 1, 1, true);
         echo Search::showNewLine($output_type);
         self::showTitle($output_type, $num, __('No activity found', 'activity'), '', false);
         echo Search::showEndLine($output_type);
         echo Search::showFooter($output_type, PluginActivityPlanningExternalEvent::getTypeName(1));
      }

      if ($pdfMode && $showPopUp) {

         echo Ajax::createIframeModalWindow('activity_displayPdf',
                                            PLUGIN_ACTIVITY_WEBDIR . "/front/cra.send.php?file=_plugins/activity/$filename",
                                            ['title'   => 'cra',
                                             'display' => false,
                                             'autoopen' => true]);

      }
   }

   /**
    * Repartition of the time
    *
    * @param numeric  $time : time to add (in full day)
    * @param string   $begin : begin date to add (format Y-m-d h:i:s)
    * @param array    $values : array of times
    * @param int      $type : holiday, work, part time, sickness
    * @param string   $activity : name of the activity
    * @param array    $holidays : array of holidays
    * @param array    $options : use real hour or constant hour
    *
    * @return array
    */
   function timeRepartition($time, $begin, $values, $type, $activity, $holidays = [], $options = []) {
      $opt = [
          'real_hour' => false,
          'use_planning_activity_hours' => true,
          'use_hours' => false
      ];
      foreach ($options as $key => $value) {
          $opt[$key] = $value;
      }
       if ($time > 1) {
         if ($opt['use_hours']) {
             for ($i = 0; $i < $time; $i++) {
                 $date_add = date('Y-m-d', strtotime($begin . ' + ' . $i . ' DAY'));

                 $h = date("H:i:s", strtotime($begin));
                 if ($h < self::$AM_END) {
                     $hour = self::getAmBegin();
                 } else {
                     $hour = self::$PM_BEGIN;
                 }

                 if ($time >= ($i + 1)) {
                     // more than one day remaining => add a full day to $date_add
                     $toAdd = 1;
                 } else {
                     // add the remaining percentage to $date_add
                     $toAdd = $time - $i;
                 }

                 $values = self::setTimes(
                     $toAdd,
                     $opt['real_hour'] ? $h : $hour,
                     $date_add,
                     $values,
                     $type,
                     $activity,
                     $holidays
                 );
             }
         } else {
             $tot = $time * 100;
             $add = 0;
             for ($i = 0; $i < $tot; $i = $i + 25) {
                 $mod = $i % 4;

                 if ($i != 0 && $mod == 0) {
                     $add++;
                 }

                 $date_add = date('Y-m-d', strtotime($begin . ' + ' . $add . ' DAY'));

                 $h = date("H:i:s", strtotime($begin));
                 if ($h < self::$AM_END) {
                     $hour = self::getAmBegin();
                 } else {
                     $hour = self::$PM_BEGIN;
                 }

                 $values = self::setTimes(0.25, $opt['real_hour'] ? $h : $hour, $date_add, $values, $type, $activity, $holidays);
             }
         }
      } else if ($time == 1) {
         $date_add = date('Y-m-d', strtotime($begin));
         $h        = date("H:i:s", strtotime($begin));
         if ($h > self::$AM_END) {
            $hour   = self::$PM_BEGIN;
            $values = self::setTimes(0.5, $opt['real_hour'] ? $h : $hour, $date_add, $values, $type, $activity, $holidays);

            $date_add = date('Y-m-d', strtotime($begin . ' + 1 DAY'));
            $hour     = self::getAmBegin();
            $values   = self::setTimes(0.5, $opt['real_hour'] ? $h : $hour, $date_add, $values, $type, $activity, $holidays);

         } else {
            $hour   = self::getAmBegin();
            $values = self::setTimes(1, $opt['real_hour'] ? $h : $hour, $date_add, $values, $type, $activity, $holidays);
         }

      } else {
         $date_add = date('Y-m-d', strtotime($begin));
         $h        = date("H:i:s", strtotime($begin));
         if ($h <= self::$AM_END) {
            $hour = self::getAmBegin();
         } else {
            $hour = self::$PM_BEGIN;
         }

         $values = self::setTimes($time, $opt['real_hour'] ? $h : $hour, $date_add, $values, $type, $activity, $holidays);
      }

      return $values;
   }


   /**
    * Get the time between two dates
    *
    * @param int      $time : time to add
    * @param datetime $begin : begin date to add
    * @param array    $values : array of times
    * @param int      $type : holiday, work, part time, sickness
    * @param string   $activity : name of the activity
    *
    * @return float
    */
   function getActionTimeForExternalEvent($begin, $end, $values, $actiontime, $type) {

      $holidays = PluginActivityHoliday::getCalendarHolidaysArray($_SESSION["glpiactive_entity"]);
//      $AllDay   = self::getAllDay();

      $holidaysO = new PluginActivityHoliday();

      $duration = strtotime($end) - strtotime($begin);
      // sometimes duration == 86399 for an event whose length was set at 1 day
      if ($duration == (self::$ONE_DAY_ACTIVITY - 1)) {
          $duration = self::$ONE_DAY_ACTIVITY;
      }
//      if ($duration == self::$ONE_DAY_ACTIVITY) {
//         $duration = $AllDay;
//      }

      $finalDuration = $duration - $holidaysO->countWe($begin, $end, $holidays);
      return $finalDuration;
   }

   /**
    * Get the time between two dates
    *
    * @param int      $time : time to add
    * @param datetime $begin : begin date to add
    * @param array    $values : array of times
    * @param int      $type : holiday, work, part time, sickness
    * @param string   $activity : name of the activity
    *
    * @return float
    */
   function getActionTime($begin, $end, $values, $actiontime, $type) {

      $holidays = PluginActivityHoliday::getCalendarHolidaysArray($_SESSION["glpiactive_entity"]);

      $holidaysO = new PluginActivityHoliday();

      $date_diff  = strtotime($end . " 23:59") - strtotime($begin . " 00:01");
      $nbSecondes = 60 * 60 * 24;
      $duration   = round($date_diff / $nbSecondes);

      $finalDuration = $duration - $holidaysO->countWe($begin, $end, $holidays);

      return $finalDuration;
   }


   /**
    * Set times and check if day is not exceeding 1
    *
    * @param int      $time : time to add (1 = full day)
    * @param int      $hour : begin hour
    * @param string   $date_add : Y-m-d begin date
    * @param array    $values : array of times
    * @param int      $type : holiday, work, part time, sickness
    * @param string   $activity : name of the activity
    *
    * @return array
    */
   static function setTimes($time, $hour, $date_add, $values, $type, $activity, $holidays = []) {
      $holiday = new PluginActivityHoliday();
      $isWe    = $holiday->countWe($date_add, $date_add, $holidays);

      if (!$isWe) {
         if (!empty($values[$type][$activity])) {
            $search_dates = [];
            foreach ($values[$type][$activity] as $date => $value) {
               $search_dates[date('Y-m-d', strtotime($date))][date('H:i:s', strtotime($date))] = $value;
            }

            // Count times in the day
            $countAll = 0;
            if (isset($search_dates[$date_add])) {
               foreach ($search_dates[$date_add] as $value) {
                  $countAll += $value;
               }
            }

            // Count times in the day
            $countAM = 0;
            if (isset($search_dates[$date_add])) {
               foreach ($search_dates[$date_add] as $h => $value) {
                  if ($h <= self::$AM_END) {
                     $countAM += $value;
                  }
               }
            }

            // Count times in the day
            $countPM = 0;
            if (isset($search_dates[$date_add])) {
               foreach ($search_dates[$date_add] as $h => $value) {
                  if ($h >= self::$PM_BEGIN) {
                     $countPM += $value;
                  }
               }
            }

            $exeedingDay = 0;
            $exeedingAM  = 0;
            $exeedingPM  = 0;
            if ($countAll + $time > 1) {
               $exeedingDay = 1;
            }
            if ($hour <= self::$AM_END && $countAM + $time > 0.5 && $countAM > 0) {
               $exeedingAM = 1;
            }
            if ($hour >= self::$PM_BEGIN && $countPM + $time > 0.5) {
               $exeedingPM = 1;
            }

            // Is AM+PM exceeding ?
            if ($exeedingDay || $exeedingPM) {
               if ($countAll - $time < 0) {
                  $values[$type][$activity][$date_add . ' ' . self::getAmBegin()] = $time;
                  $values = self::setTimes($countAll, self::getAmBegin(), date('Y-m-d', strtotime($date_add . ' + 1 DAY')), $values, $type, $activity, $holidays);
               } else {
                  $values = self::setTimes($time, self::getAmBegin(), date('Y-m-d', strtotime($date_add . ' + 1 DAY')), $values, $type, $activity, $holidays);
               }

               // Appending/adding time
            } else if ($exeedingAM && !$exeedingPM) {
               if (isset($values[$type][$activity][$date_add . ' ' . self::$PM_BEGIN])) {
                  $values[$type][$activity][$date_add . ' ' . self::$PM_BEGIN] += $time;
               } else {
                  $values[$type][$activity][$date_add . ' ' . self::$PM_BEGIN] = $time;
               }
            } else if (isset($values[$type][$activity][$date_add . ' ' . $hour])) {
               $values[$type][$activity][$date_add . ' ' . $hour] += $time;

            } else {
               $values[$type][$activity][$date_add . ' ' . $hour] = $time;
            }

         } else {
            $values[$type][$activity][$date_add . ' ' . $hour] = $time;
         }

         // No addition weekend, trying next day
      } else {
         $values = self::setTimes($time, self::getAmBegin(), date('Y-m-d', strtotime($date_add . ' + 1 DAY')), $values, $type, $activity, $holidays);
      }
      return $values;

   }

   /**
    * Display the activities
    *
    * @param string $activity
    * @param int    $output_type
    * @param int    $row_num
    * @param int    $type
    *
    * @return int
    */
   function showActivity($activity, $output_type, $row_num, $type, $crit) {

      $tot = 0;

      $activity_data = [];
      $tot_act       = [];
       $opt = new PluginActivityOption();
       $opt->getFromDB(1);
       $use_hour_on_cra               = $opt->fields['use_hour_on_cra'];
       $use_planning_activity_hours = $opt->fields['use_planning_activity_hours'];
      switch ($type) {
         case self::$SICKNESS:
         case self::$PART_TIME:
         case self::$HOLIDAY:
            $activity_data[self::getHolidayName($type)] = $activity;
            break;
         case
         self::$WORK:
            $activity_data = $activity;
            break;
      }

      if (!empty($activity)) {
         foreach ($activity_data as $key => $times) {
            $row_num++;
            $num = 1;
            echo Search::showNewLine($output_type);
            $parent = $key;
            $child  = '';

            if (strstr($key, '>') !== false) {
               $delimiter = ">";
            } elseif (strstr($key, '&gt;') !== false) {
               $delimiter = "&gt;";
            }
            if (strstr($key, '>') || strstr($key, '&gt;')) {
               $childs = explode($delimiter, $key);
               echo Search::showItem($output_type, trim($childs[0]), $num, $row_num);
               if (isset($childs[2])) {
                  echo Search::showItem($output_type, trim($childs[2]), $num, $row_num);
               } else {
                  echo Search::showItem($output_type, trim($childs[1]), $num, $row_num);
               }
            } else {
               if ($type == self::$WORK) {
                  echo Search::showItem($output_type, $child, $num, $row_num);
                  echo Search::showItem($output_type, $parent, $num, $row_num);
               } else {
                  echo Search::showItem($output_type, $parent, $num, $row_num);
                  echo Search::showItem($output_type, $child, $num, $row_num);
               }
            }

            foreach ($times as $begin => $data) {
               // Use round values
               $data['values'] = self::TotalTpsPassesArrondis($data['values'], [
                   'arrondir_heure' => $use_hour_on_cra,
                   'use_planning_activity_hours' => $use_planning_activity_hours
               ]);

               // Get css
               $class = "class='center'";
               $style = "";
               if (isset($data['options']['weekend'])) {
                  $style = " style='background-color:#CCCCCC' ";
               }

               // Get tickets link for value
               $link = '';
               if ($output_type == Search::HTML_OUTPUT) {
                  $link = $this->getItemLink($data['values'], $key, date('Y-m-d', strtotime($begin)), ['depass' => isset($data['options']['depass']) ? $data['options']['depass'] : 0]);
               }
               echo Search::showItem($output_type, !empty($link) ? $link : '', $num, $row_num, $class . $style);

               // Total of all
               $tot += $data['values'];

               // Activity total
               if (isset($tot_act[$key])) {
                  $tot_act[$key] += $data['values'];
               } else {
                  $tot_act[$key] = $data['values'];
               }
            }
            // Total value depass
            if (self::isIncorrectValue($tot_act[$key]) > 0 && !$use_hour_on_cra) {
               $class = " class='center red'";
            }

            echo Search::showItem($output_type, Html::formatNumber($tot_act[$key], false, 3), $num, $row_num, $class);
            echo Search::showEndLine($output_type);
         }

      } else {
          $num = 1;
         $holiday = new PluginActivityHoliday();
         $holiday->setHolidays();
         $count = PluginActivityPlanningExternalEvent::getNbDays($crit["begin"], $crit["end"]);
         echo Search::showNewLine($output_type);
         $keys = array_keys($activity_data);
         echo Search::showItem($output_type, isset($keys[0]) ? $keys[0] : "", $num, $row_num);

         echo Search::showItem($output_type, "", $num, $row_num);

         for ($i = 0; $i != $count; $i++) {
            $d     = PluginActivityPlanningExternalEvent::dateAdd($i, $crit["begin"]);
            $class = "class='center'";
            $style = "";
            if ($holiday->countWe($d, $d, $holiday->getHolidays()) > 0) {
               $style = " style='background-color:#CCCCCC' ";
            }
            echo Search::showItem($output_type, '', $num, $row_num, $class . $style);
         }

         echo Search::showItem($output_type, Html::formatNumber(0, false, 3), $num, $row_num, $class . $style);
         echo Search::showEndLine($output_type);

      }

      return $tot;
   }

   /**
    * Set incorrect value display
    *
    * @param string $value
    *
    * @return string
    */
   static function isIncorrectValue($value) {

      if ((($value * 10) % 5) > 0) {
         return true;
      }

      return false;
   }


   /**
    * Get holiday name
    *
    * @param type $type
    *
    * @return type
    */
   static function getHolidayName($type) {

      switch ($type) {
         case self::$HOLIDAY:
            return __('Holidays or exceptional absences', 'activity');
         case self::$PART_TIME:
            return __('Part time', 'activity');
         case self::$SICKNESS:
            return __('Sickness or maternity', 'activity');
      }
   }

   /**
    * Display a link with the activity search options
    *
    * @param int    $value
    * @param string $activity
    * @param date   $begin
    * @param array  $options
    *
    * @return string url
    */
   function getItemLink($value, $activity, $begin, $options = []) {
      $output = $value;
       $opt = new PluginActivityOption();
       $opt->getFromDB(1);
       $use_hour_on_cra = $opt->fields['use_hour_on_cra'];
      if (!empty($value) && isset($this->item_search[$activity])) {
         $rand                    = mt_rand();
         $opt                     = [];
         $opt['is_deleted']       = 0;
         $opt['start']            = 0;
         $opt['_glpi_csrf_token'] = Session::getNewCSRFToken();
         $opt['depass']           = false;

         foreach ($this->item_search[$activity] as $itemtype => $data) {
            $opt['itemtype'] = $itemtype;
            $found           = false;
            foreach ($data as $items_begin => $items) {
               if ($begin == $items_begin) {
                  $found = true;
                  $nb    = 0;
                  foreach (array_unique($items) as $items_id) {
                     $opt['criteria'][$nb]['field']      = 2; // Search options
                     $opt['criteria'][$nb]['searchtype'] = 'equals';
                     $opt['criteria'][$nb]['value']      = $items_id;
                     $opt['criteria'][$nb]['link']       = 'OR';

                     $nb++;
                  }

                  foreach ($options as $key => $val) {
                     $opt[$key] = $val;
                  }
                  $target = Toolbox::getItemTypeSearchURL('PluginActivityCra');

                  $url   = $target . "?" . Toolbox::append_params($opt, '&amp;');
                  $style = null;
                  if ($opt['depass'] && !$use_hour_on_cra) {
                     $style = 'color:red';
                  }
                  $output = "<a style='$style' href=\"$url\" id=\"activity_link$rand\" target=\"_blank\" title=\"" . $value . "\">" . $value . "</a>";

                  break;
               }
            }

            // Get previous activity time if not found
            if (!$found) {
               $output = $this->getItemLink($value, $activity, date('Y-m-d', strtotime($begin . ' -1 DAY')), $options);
            }
         }
      }

      return $output;
   }


   /**
    * Display the total of all days
    *
    * @param type $tot
    * @param type $countAllDays
    * @param type $countopened
    * @param type $output_type
    * @param type $row_num
    */
   function showTotal($tot, $countAllDays, $countopened, $output_type, $row_num, $time) {

      $types = [self::$WORK, self::$HOLIDAY/*, self::$PART_TIME,  self::$SICKNESS*/];
       $opt = new PluginActivityOption();
       $opt->getFromDB(1);
       $use_hour_on_cra = $opt->fields['use_hour_on_cra'];
      $time_total = [];
      for ($y = 0; $y < $countAllDays; $y++) {
         $time_total[$y] = ['value' => 0, 'style' => ''];
      }
      foreach ($types as $type) {
         foreach ($time[$type] as $key => $times) {
            $row_num++;
            $i = 0;
            foreach ($times as $begin => $data) {
               // Use round values
               $data['values'] = self::TotalTpsPassesArrondis($data['values'], [
                       'arrondir_heure' => $use_hour_on_cra,
                   'use_planning_activity_hours' => $opt->fields['use_planning_activity_hours']
               ]);

               $time_total[$i]['value'] = $time_total[$i]['value'] + $data['values'];
               if (isset($data['options']['weekend'])) {
                  $time_total[$i]['style'] = " style='background-color:#CCCCCC' ";
               }
               $i++;
            }

         }
      }
       $opt = new PluginActivityOption();
       $opt->getFromDB(1);
       $use_hour_on_cra               = $opt->fields['use_hour_on_cra'];
      $num = 1;
      $row_num++;
      echo Search::showNewLine($output_type);
       if( !$use_hour_on_cra) {
           echo Search::showItem($output_type, __('Total') . " (" . $countopened . ")", $num, $row_num);
       } else {
           echo Search::showItem($output_type, __('Total'), $num, $row_num);
       }

      echo Search::showItem($output_type, '', $num, $row_num);
      foreach ($time_total as $key => $data) {
         if (!empty($data['style'])) {
            //week end
            $class = $data['style'];
            echo Search::showItem($output_type, '', $num, $row_num, $class);
         } else {
            $class = "class='center";
            if ($data['value'] != 1 ) {
                if( !$use_hour_on_cra) {
                    $class .= " red'";
                }

               echo Search::showItem($output_type, Html::formatNumber($data['value'], false, 2), $num, $row_num, $class);
            } else {
               $class .= "' ";
               echo Search::showItem($output_type, '', $num, $row_num, $class);
            }
         }
      }
      $class = "class='center";
       if( !$use_hour_on_cra) {
           if ($tot != $countopened) {
               $class .= " red'";
           } else {
               $class .= " green'";
           }
       }
      echo Search::showItem($output_type, Html::formatNumber($tot, false, 3), $num, $row_num, $class);
      echo Search::showEndLine($output_type);
   }

   /**
    * Display the column title and allow the sort
    *
    * @param      $output_type
    * @param      $num
    * @param      $title
    * @param      $columnname
    * @param bool $sort
    * @param      $options  string options to add (default '')
    *
    * @return mixed
    */
   static function showTitle($output_type, &$num, $title, $columnname, $sort = false, $options = '') {

      if ($output_type != Search::HTML_OUTPUT || $sort == false) {
         echo Search::showHeaderItem($output_type, $title, $num, '', '', '', $options);
         return;
      }
      $order  = 'ASC';
      $issort = false;
      if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == $columnname) {
         $issort = true;
         if (isset($_REQUEST['order']) && $_REQUEST['order'] == 'ASC') {
            $order = 'DESC';
         }
      }
      $link  = $_SERVER['PHP_SELF'];
      $first = true;
      foreach ($_REQUEST as $name => $value) {
         if (!in_array($name, ['sort', 'order', 'PHPSESSID'])) {
            $link  .= ($first ? '?' : '&amp;');
            $link  .= $name . '=' . urlencode($value);
            $first = false;
         }
      }
      $link .= ($first ? '?' : '&amp;') . 'sort=' . urlencode($columnname);
      $link .= '&amp;order=' . $order;
      echo Search::showHeaderItem($output_type, $title, $num,
                                  $link, $issort, ($order == 'ASC' ? 'DESC' : 'ASC'));
   }

   /**
    * Permet d'arrondir le total des temps passés avec les tranches définies en constantes.
    * Peut étre améliorée afin de boucler (while) sur les tranches pour ne pas avoir une suite de if, else if.
    *
    * @param $a_arrondir mixed Total à arrondir
    * @param $options array clés arrondir_heure
    *
    * @return Le total arrondi selon la régle de gestion.
    */
   static function TotalTpsPassesArrondis($a_arrondir,$options = []) {
       $opt = [
           'arrondir_heure' => 1,
           'use_planning_activity_hours' => 1
       ];
       foreach ($options as $key => $value) {
           $opt[$key] = $value;
       }
      $tranches_seuil   = 0.002;
      if(!$opt['arrondir_heure']) {
          $tranches_arrondi = [0, 0.25, 0.5, 0.75, 1];
      } else {
          if ($opt['use_planning_activity_hours']) {
              $allday = self::getAllDay();
          } else {
              $allday = 3600*24;
          }

          $hour = ((float) $a_arrondir)* (float)$allday;
          $a_arrondir = $hour / 3600;
          $tranches_arrondi = [0,0.25,0.5,0.75,1];

      }


      $result = 0;
      if (empty($a_arrondir)) {
         $a_arrondir = 0;
      }
      $partie_entiere = floor($a_arrondir);
      $reste          = $a_arrondir - $partie_entiere + 10; // Le + 10 permet de pallier é un probléme de comparaison (??) par la suite.
      /* Initialisation des tranches majorées du seuil supplémentaire. */
      $tranches_majorees = [];
      for ($i = 0; $i < count($tranches_arrondi); $i++) {
         // Le + 10 qui suit permet de pallier é un probléme de comparaison (??) par la suite.
         $tranches_majorees[] = $tranches_arrondi[$i] + $tranches_seuil + 10;
      }
       $find = false;



      if ($reste < $tranches_majorees[0]) {
         $result = $partie_entiere;
         $find = true;
      } else {
          for ($i = 1; $i < (count($tranches_arrondi)-1); $i++) {
              // Le + 10 qui suit permet de pallier é un probléme de comparaison (??) par la suite.
              if ($reste >= $tranches_majorees[($i-1)] && $reste < $tranches_majorees[$i]) {
                  $result = $partie_entiere + $tranches_arrondi[$i];
                  $find = 1;
                  break;
              }
//              $tranches_majorees[] = $tranches_arrondi[$i] + $tranches_seuil + 10;
          }
      }

      if(!$find) {
          $result = $partie_entiere + $tranches_arrondi[(count($tranches_arrondi)-1)];
      }


      return $result;
   }

   function send($doc) {

      $file = GLPI_DOC_DIR . "/" . $doc->fields['filepath'];

      if (!file_exists($file)) {
         die("Error file " . $file . " does not exist");
      }
      // Now send the file with header() magic
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: filename=\"" . $doc->fields['filename'] . "\"");
      header("Content-type: " . $doc->fields['mime']);

      readfile($file) or die ("Error opening file $file");
   }

    /**
     * Transform a string to uppercase, take its encoding into account if possible
     * @param $string
     * @return string
     */
   static function strtoupper_auto($string) {
       if(extension_loaded('mbstring')) {
           $encoding = mb_detect_encoding($string, mb_detect_order(), true);
           if ($encoding === false) {
               $encoding = 'UTF-8';
           }
           return mb_strtoupper($string, $encoding);
       }
       return strtoupper($string);
   }

    /**
     * Add an event/task to $values when building the CRA
     * @param array $values
     * @param DateTime $begin beginning of $event
     * @param DateTime $end end of $event
     * @param array $crit $crit from showCRA
     * @param array $event array representing the event, must have keys : begin (value string, "Y-m-d h:i:s"), end (value string, , "Y-m-d h:i:s"), actiontime (int, duration in seconds)
     * @param int $part see const from the class
     * @param string $title
     * @param string $itemtype of $event
     * @param int $events_id tickets_id if $event is ticket task, id if $event is planning external event
     * @return void
     * @throws Exception
     */
   private function addEventToValues(array &$values, DateTime $begin, DateTime $end, array $crit, array $event, int $part, string $title, string $itemtype, int $events_id) {
       $dateBegin = $begin->format('Y-m-d');
       $dateEnd = $end->format('Y-m-d');
       if ($dateBegin != $dateEnd) {
           $loopDate = $dateBegin;
           while ($loopDate <= $dateEnd) {
               $dateTimeLoop =  new DateTime($loopDate);
               $dateTimeLoop->modify('+1 day');
               if ($loopDate . ' 00:00:00' < $crit['end']) { // task between 2 months, exclude the part outside of the current month
                   $actionTime = self::getActiontimeOnPeriod(
                       $loopDate.' 00:00:00',
                       $dateTimeLoop->format('Y-m-d').' 00:00:00',
                       $event
                   );
                   $dayLength = round($actionTime / (60 * 60 * 24), 4);
                   if (isset($values[$part][$title][$loopDate . ' 00:00:00'])) {
                       $values[$part][$title][$loopDate . ' 00:00:00'] += $dayLength;
                   } else {
                       $values[$part][$title][$loopDate . ' 00:00:00'] = $dayLength;
                   }

                   $this->item_search[$title][$itemtype][$loopDate][] = $events_id;
               }
               // increment date
               $loopDate = $dateTimeLoop->format('Y-m-d');
           }
       } else {
           $dayLength = round($event['actiontime'] / (60 * 60 * 24), 4);
           if (isset($values[$part][$title][$dateBegin . ' 00:00:00'])) {
               $values[$part][$title][$dateBegin . ' 00:00:00'] += $dayLength;
           } else {
               $values[$part][$title][$dateBegin . ' 00:00:00'] = $dayLength;
           }
           $this->item_search[$title][$itemtype][$dateBegin][] = $events_id;
       }
   }

    /**
     * @param string $begin date format Y-m-d h:i:s
     * @param string $end date format Y-m-d h:i:s
     * @param array $event must have keys : begin (value string, same format as $begin), end (value string, same format as $end), actiontime (int, duration in seconds)
     * @return int duration of event on the given period (in seconds)
     */
   public static function getActiontimeOnPeriod($begin, $end, $event) {
       if (!isset($event['begin'])) { // doesn't have begin / end (like not planned ticket tasks)
           $event['end'] = $event['date'];
           $eventEnd = new DateTime($event['date']);
           $event['begin'] = $eventEnd->modify('-' . $event['actiontime'] . ' seconds')
               ->format('Y-m-d H:i:s');
       }

       if (!isset($event['actiontime'])) { // doesn't have actiontime (like planning external event)
           $eventBegin = new DateTime($event['begin']);
           $eventEnd = new DateTime($event['end']);
           $event['actiontime'] = $eventEnd->getTimestamp() - $eventBegin->getTimestamp();
       }

       if ($event['begin'] >= $begin && $event['end'] <= $end) { // entirety of the event happen on the period
           return $event['actiontime'];
       }

       $duration = $event['actiontime'];
       if ($event['begin'] < $begin) { // subtract time from before the period to the total
           $periodBegin = new DateTime($begin);
           $eventBegin = new DateTime($event['begin']);
           $duration -= $periodBegin->getTimestamp() - $eventBegin->getTimeStamp();
       }
       if ($event['end'] > $end) { // subtract time from after the period to the total
           $periodEnd = new DateTime($end);
           $eventEnd = new DateTime($event['end']);
           $duration -= $eventEnd->getTimeStamp() - $periodEnd->getTimestamp();
       }

       return $duration;
   }
}
