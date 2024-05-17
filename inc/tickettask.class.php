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

class PluginActivityTicketTask extends CommonDBTM {

   var $dohistory = false;

   static $rightname = "plugin_activity";

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    * */
   static function getTypeName($nb = 0) {
      return _n('Cumulative ticket task', 'Cumulative ticket tasks', $nb, 'activity');
   }

   static function cleanForItem(CommonDBTM $item) {

      $temp = new self();
      $temp->deleteByCriteria(
         ['tickettasks_id' => $item->getField('id')]
      );
   }


    /**
     * post_item_form for TicketTask
     * @param $params
     * @return void
     */
    static public function postForm($params)
    {
        $item = $params['item'];
        $self = new self();
        if ($item->getID() && !empty($item->getID())) {
            $self->getFromDBForTask($item->getID());
        } else {
            $self->getEmpty();
        }

        $is_cra_default = 0;
        $opt = new PluginActivityOption();
        $opt->getFromDB(1);
        if ($opt) {
            $is_cra_default = $opt->fields['is_cra_default'];
        }

        if (Session::haveRight("plugin_activity_statistics", 1)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='3'></td>";
            echo '<td>';
            echo "<div id='is_oncra_" . $item->getID() . "' class='fa-label right'>
               <i class='ti ti-flag fa-fw'
                  title='" . __('Use in CRA', 'activity') . "'></i>";
            Dropdown::showYesNo(
                'is_oncra',
                (isset($self->fields['id']) && $self->fields['id']) > 0 ? $self->fields['is_oncra'] : $is_cra_default,
                -1,
                ['value' => 1]
            );
            echo '</div></td>';
            echo '</tr>';
        } else {
            echo Html::hidden('is_oncra', ['value' => 1]);
        }
    }


   function getFromDBForTask($tickettasks_id) {
      $dbu  = new DbUtils();
      $data = $dbu->getAllDataFromTable($this->getTable(), [$dbu->getForeignKeyFieldForTable('glpi_tickettasks') => $tickettasks_id]);

      $this->fields = array_shift($data);
   }

   function isDisplayableOnCra($tickettasks_id) {
      $data = $this->getFromDBForTask($tickettasks_id);
      if ($data['is_oncra']) {
         return true;
      }

      return false;
   }



   static function setTicketTask(TicketTask $item) {

      if (self::canCreate()) {
         $tickettask = new PluginActivityTicketTask();
         $is_exist   = $tickettask->getFromDBByCrit(["tickettasks_id" => $item->getID()]);



         if (isset($item->input['id'])
             && isset($item->input['is_oncra'])) {
            $tickettask->getFromDBForTask($item->input['id']);


            if (!empty($tickettask->fields)) {
               $tickettask->update(['id'             => $tickettask->fields['id'],
                                    'is_oncra'       => $item->input['is_oncra'],
                                    'tickettasks_id' => $item->input['id']]);

            } else if (!$is_exist) {
               $tickettask->add(['is_oncra'       => $item->input['is_oncra'],
                                 'tickettasks_id' => $item->getID()]);
            }
         } else {
            $is_cra_default = 0;
            $opt            = new PluginActivityOption();
            $opt->getFromDB(1);
            if ($opt) {
               $is_cra_default = $opt->fields['is_cra_default'];
            }
            if (!$is_exist) {
               $tickettask->add(['is_oncra'       => isset($item->input['is_oncra']) ? $item->input['is_oncra'] : $is_cra_default,
                                 'tickettasks_id' => $item->getID()]);
            }
         }
      }
   }

   static function taskUpdate(TicketTask $item) {

      if (!is_array($item->input) || !count($item->input)) {
         // Already cancel by another plugin
         return false;
      }
      self::setTicketTask($item);
   }

   static function taskAdd(TicketTask $item) {

      if (!is_array($item->input) || !count($item->input)) {
         // Already cancel by another plugin
         return false;
      }
      self::setTicketTask($item);
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
      $options = array_merge($default_options, $options);

      $interv   = [];

      if (!isset($options['begin']) || ($options['begin'] == 'NULL')
          || !isset($options['end']) || ($options['end'] == 'NULL')) {
         return $interv;
      }

      if (!$options['display_done_events']) {
         return $interv;
      }

      $who        = $options['who'];
      $begin      = $options['begin'];
      $end        = $options['end'];

      $query = "SELECT    `glpi_tickettasks`.*,
                          `glpi_plugin_activity_tickettasks`.`is_oncra`,
                          `glpi_entities`.`name` AS entity,
                          `glpi_tickets`.`name`,
                          `glpi_tickets`.`id` AS tickets_id,
                          `glpi_entities`.`id` AS entities_id
                     FROM `glpi_tickettasks`
                     INNER JOIN `glpi_tickets`
                        ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id` AND `glpi_tickets`.`is_deleted` = 0)
                     LEFT JOIN `glpi_entities` 
                        ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) 
                     LEFT JOIN `glpi_plugin_activity_tickettasks` 
                        ON (`glpi_tickettasks`.`id` = `glpi_plugin_activity_tickettasks`.`tickettasks_id`) ";
      $query .= "WHERE ";
      if (Plugin::isPluginActive('manageentities')) {
         $query .= "`glpi_tickettasks`.`tickets_id` 
                     NOT IN (SELECT `tickets_id` 
                              FROM `glpi_plugin_manageentities_cridetails`) AND ";
      }
      $dbu = new DbUtils();
      $query .= "((`glpi_tickettasks`.`begin` >= '".$begin."' 
                           AND `glpi_tickettasks`.`end` <= '".$end."'
                           AND `glpi_tickettasks`.`users_id_tech` = '".$who."' ".
                $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets", '',
                                                 $_SESSION["glpiactiveentities"], false);
      $query.= "                     ) 
                           OR (`glpi_tickettasks`.`date` >= '".$begin."' 
                           AND `glpi_tickettasks`.`date` <= '".$end."'
                           AND `glpi_tickettasks`.`users_id` = '".$who."'
                           AND `glpi_tickettasks`.`begin` IS NULL ".$dbu->getEntitiesRestrictRequest("AND", "glpi_tickets", '',
                                                                                                     $_SESSION["glpiactiveentities"], false);
      $query.= " )) AND `glpi_tickettasks`.`actiontime` != 0 
                  AND `glpi_plugin_activity_tickettasks`.`is_oncra` = 1";
      $query.= " ORDER BY `glpi_tickettasks`.`begin` ASC";
      $result = $DB->query($query);

      $number = $DB->numrows($result);

      $activities     = [];

      if ($number) {
         $allTickets       = [];
         $privateTickets   = [];

         while ($datat = $DB->fetchArray($result)) {
            $mtitle   = $datat["entity"]." > ".__('Ticket');
            $internal = PluginActivityConfig::getConfigFromDB($datat['entities_id']);
            if ($internal) {
               foreach ($internal as $field) {
                  $mtitle = $datat["entity"]." > ".$field["name"];
               }
            }

            if (!empty($datat["begin"]) && !empty($datat["end"])) {
               $begin = $datat["begin"];
            } else {
               $begin = $datat["date"];
            }
            $report     = new PluginActivityReport();
            $holiday    = new PluginActivityHoliday();
            $self        = new Self();
            $AllDay     = PluginActivityReport::getAllDay();
            $opt        = new PluginActivityOption();
            $opt->getFromDB(1);

            if (!$datat['is_private']) {
               if ($opt->fields['use_timerepartition']) {
                  $allTickets = $report->timeRepartition($datat['actiontime'] / $AllDay,
                                                         $begin,
                                                         $allTickets, PluginActivityReport::$WORK,
                                                         $mtitle,
                                                         $holiday->getHolidays());

               } else {
                  $allTickets[0][$mtitle][$begin] = $datat['actiontime'] / $AllDay;
               }
            } else {
               if ($opt->fields['use_timerepartition']) {
                  $privateTickets = $report->timeRepartition($datat['actiontime'] / $AllDay,
                                                             $begin,
                                                             $privateTickets, PluginActivityReport::$WORK,
                                                             $mtitle,
                                                             $holiday->getHolidays());
               } else {
                  $privateTickets[0][$mtitle][$begin] = $datat['actiontime'] / $AllDay;
               }
            }
         }

         $self->setTicketActivity($allTickets, $activities);
         $self->setTicketActivity($privateTickets, $activities, true);

      }

      if (count($activities) > 0) {
         foreach ($activities as $k => $int) {

            $key = $int["start"]."$$"."PluginActivityTicketTask".$int["id"];

            $interv[$key]['color']              = $options['color'];
            $interv[$key]['event_type_color']   = $options['event_type_color'];
            $interv[$key]["itemtype"]           = 'PluginActivityTicketTask';
            $interv[$key]["id"]                 = $int["id"];
            $interv[$key]["users_id"]           = $who;
            $interv[$key]["begin"]           = $int["start"];
            $interv[$key]["end"]             = $int["end"];
            $interv[$key]["editable"]         = false;
            $interv[$key]["name"]     = Glpi\Toolbox\Sanitizer::unsanitize(Html::resume_text($int["title"], $CFG_GLPI["cut"])); // name is re-encoded on JS side
            $interv[$key]["content"]  = Glpi\RichText\RichText::getSafeHtml(Html::resume_text($int["description"],$CFG_GLPI["cut"]));

         }
      }

      return $interv;

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

   /**
    * Display a Planning Item
    *
    * @param $parm Array of the item to display
    * @return Nothing (display function)
    **/
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {

      $html = "";
      $rand     = mt_rand();

      if ($val["name"]) {
         $html .= $val["name"]."<br>";
      }

      if ($val["end"]) {
         $html .= "<strong>".__('End date')."</strong> : ".Html::convdatetime($val["end"])."<br>";
      }

      if ($complete) {
         $html.= "<div class='event-description'>".$val["content"]."</div>";
      } else {
         $html.= Html::showToolTip($val["content"],
                                   ['applyto' => "cri_".$val["id"].$rand,
                                    'display' => false]);
      }

      return $html;
   }
}
