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

class PluginActivityLateralmenu extends CommonDBTM {

   static function showMenu() {
      global $CFG_GLPI;

      $listActions = array_merge(PluginActivityPlanningExternalEvent::getActionsOn(), PluginActivityHoliday::getActionsOn());

      $types = [
         PluginActivityActions::ADD_ACTIVITY,
         PluginActivityActions::HOLIDAY_REQUEST,
         PluginActivityActions::CRA,
         PluginActivityActions::APPROVE_HOLIDAYS
      ];

      foreach ($listActions as $key => $action) {
         if (in_array($key, $types) && $action['rights']) {
            echo "<tr class='tab_bg_1 center'>";
            echo "<td colspan='2'>";
            echo "<a href=\"" . $action['link'] . "\"  class='submit btn btn-info'";
            if (isset($action['onclick']) && !empty($action['onclick'])) {
               echo $action['onclick'];
            }
            echo ">";
            echo $action['label'];
            echo "</a>";
            echo "</td>";
            echo "</tr>";
         }
      }

      Ajax::createIframeModalWindow('holiday',
                                    PLUGIN_ACTIVITY_WEBDIR . "/front/holiday.form.php",
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
    * Create a side slide panel
    *
    * @param string $name    name of the js object
    * @param array  $options Possible options:
    *          - title       Title to display
    *          - position    position (either left or right - defaults to right)
    *          - display     display or get string? (default true)
    *          - icon        Path to aditional icon
    *          - icon_url    Link for aditional icon
    *          - icon_txt    Alternative text and title for aditional icon_
    *
    * @return void|string (see $options['display'])
    */
   static function createSlidePanel($name, $options = []) {
      global $CFG_GLPI;

      $param = [
         'title'     => '',
         'position'  => 'right',
         'url'       => '',
         'display'   => true,
         'icon'      => false,
         'icon_url'  => false,
         'icon_txt'  => false
      ];

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }

      $out  =  "<script type='text/javascript'>\n";
      $out .= "$(function() {";
      $out .= "$('<div id=\'$name\' class=\'slidepanel on{$param['position']}\'><div class=\"header\">" .
              "<button type=\'button\' class=\'close ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close\' title=\'". __s('Close') . "\'><span class=\'ui-button-icon-primary ui-icon ui-icon-closethick\'></span><span class=\'ui-button-text\'>X</span></button>";

      if ($param['icon']) {
         $icon = "<img class=\'icon\' src=\'{$CFG_GLPI['root_doc']}{$param['icon']}\' alt=\'{$param['icon_txt']}\' title=\'{$param['icon_txt']}\'/>";
         if ($param['icon_url']) {
            $out .= "<a href=\'{$param['icon_url']}\'>$icon</a>";
         } else {
            $out .= $icon;
         }
      }

      if ($param['title'] != '') {
         $out .= "<h3>{$param['title']}</h3>";
      }

      $out .= "</div><div class=\'contents\'></div></div>')
         .hide()
         .appendTo('body');\n";
      $out .= "$('#{$name} .close').on('click', function() {
         $('#$name').hide(
            'slow',
            function () {
               $(this).find('.contents').empty();
            }
         );
       });\n";
      $out .= "$('#{$name}Link').on('click', function() {
         $('#$name').show(
            'slow',
            function() {
               _load$name();
            }
         );
      });\n";
      $out .= "});";
      if ($param['url'] != null) {
         $out .= "var _load$name = function() {
            $.ajax({
               url: '{$param['url']}',
               beforeSend: function() {
                  var _loader = $('<div id=\'loadingslide\'><div class=\'loadingindicator\'>" . __s('Loading...') . "</div></div>');
                  $('#$name .contents').html(_loader);
               }
            })
            .always( function() {
               $('#loadingslide').remove();
            })
            .done(function(res) {
               $('#$name .contents').html(res);
            });
         };\n";
      }
      $out .= "</script>";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }
}
