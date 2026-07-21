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

use CommonDBTM;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class HolidayCount extends CommonDBTM
{

    var    $dohistory = false;
    var    $holidays  = [];
    static $rightname = "plugin_activity";

    static function getTypeName($nb = 1)
    {
        return _n('Holiday counter', 'Holiday counters', $nb, 'activity');
    }

   /*
   function cleanDBonPurge() {
      $holidayValidation = new HolidayValidation();
      $holidayValidation->cleanDBonItemDelete($this->getType(),$this->fields['id']);

      parent::cleanDBonPurge();
   }*/


    function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    **/
    function prepareInputForAdd($input)
    {

        if ($input['plugin_activity_holidayperiods_id'] == 0) {
            Session::addMessageAfterRedirect(__("Holiday period is mandatory field", "activity"), false, ERROR);
            return false;
        }
        if ($input['plugin_activity_holidaytypes_id'] == 0) {
            Session::addMessageAfterRedirect(__('Holiday type is mandatory field', 'activity'), false, ERROR);
            return false;
        }

        $restrict = ["users_id" => $input['users_id'], "plugin_activity_holidayperiods_id" => $input['plugin_activity_holidayperiods_id']];
        $dbu      = new DbUtils();
        $hcounts  = $dbu->getAllDataFromTable($this->getTable(), $restrict);

        if (count($hcounts) > 0) {
            Session::addMessageAfterRedirect(__("Only one counter by period is allowed", "activity"), false, ERROR);
            return false;
        }

        $holidayperiod = new HolidayPeriod();
        $holidayperiod->getFromDB($input['plugin_activity_holidayperiods_id']);
        $input['name'] = $holidayperiod->getName();

        return $input;
    }


    function rawSearchOptions()
    {

        $holidaytype   = new HolidayType();
        $holidayperiod = new HolidayPeriod();

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
         'id'            => '3',
         'table'         => $holidaytype->getTable(),
         'field'         => 'name',
         'name'          => HolidayType::getTypeName(1),
         'datatype'      => 'dropdown',
         'massiveaction' => false,
        ];

        $tab[] = [
         'id'            => '4',
         'table'         => $holidayperiod->getTable(),
         'field'         => 'name',
         'name'          => HolidayPeriod::getTypeName(1),
         'datatype'      => 'dropdown',
         'massiveaction' => false,
        ];

        $tab[] = [
         'id'            => '9',
         'table'         => 'glpi_users',
         'field'         => 'name',
         'name'          => _n('User', 'Users', 1),
         'massiveaction' => false,
         'nosearch'      => true,
         'datatype'      => 'dropdown',
         'right'         => 'interface',
        ];

        $tab[] = [
         'id'       => '10',
         'table'    => $this->getTable(),
         'field'    => 'count',
         'name'     => __('Counter', 'activity'),
         'datatype' => 'decimal'
        ];

        $tab[] = [
         'id'            => '12',
         'table'         => $this->getTable(),
         'field'         => 'date_mod',
         'massiveaction' => false,
         'name'          => __('Last update'),
         'datatype'      => 'datetime'
        ];

        $tab[] = [
         'id'       => '30',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
        ];

        return $tab;
    }


   /**
    * Display the count holiday form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
    * */
    function showForm($ID, $options = [])
    {
        $dbu = new DbUtils();

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        ob_start();
        Dropdown::show(HolidayType::class, [
            'name'     => 'plugin_activity_holidaytypes_id',
            'value'    => $this->fields['plugin_activity_holidaytypes_id'],
            'comments' => 1,
        ]);
        $holiday_type_dropdown_html = ob_get_clean();

        ob_start();
        Dropdown::show(HolidayPeriod::class, [
            'name'     => 'plugin_activity_holidayperiods_id',
            'value'    => $this->fields['plugin_activity_holidayperiods_id'],
            'comments' => 1,
        ]);
        $holiday_period_dropdown_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@activity/holiday_count_form.html.twig', [
            'users_id'                    => Session::getLoginUserID(),
            'username'                    => $dbu->getUserName(Session::getLoginUserID()),
            'holiday_type_label'          => HolidayType::getTypeName(1),
            'holiday_type_dropdown_html'  => $holiday_type_dropdown_html,
            'holiday_period_label'        => HolidayPeriod::getTypeName(1),
            'holiday_period_dropdown_html' => $holiday_period_dropdown_html,
            'count'                       => Html::formatNumber($this->fields['count'], true),
        ]);

        $this->showFormButtons($options);

        return true;
    }

    function showCountForHolidayType($plugin_activity_holidaytypes_id)
    {
        global $DB;

        $user_id = Session::getLoginUserID();

        $count = 0;
       // Current year
        $old_annee  = intval(date('Y', time()) - 1);
        $next_annee = date('Y');
       // Next year
        if (time() > strtotime(date('Y') . "-05-31")) {
            $old_annee  = intval(date('Y', time()));
            $next_annee = intval(date('Y', time()) + 1);
        }

        $iterator = $DB->request([
            'SELECT'    => ['glpi_plugin_activity_holidaycounts.*'],
            'FROM'      => 'glpi_plugin_activity_holidaycounts',
            'LEFT JOIN' => [
                'glpi_plugin_activity_holidayperiods' => [
                    'ON' => [
                        'glpi_plugin_activity_holidaycounts' => 'plugin_activity_holidayperiods_id',
                        'glpi_plugin_activity_holidayperiods' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_plugin_activity_holidaycounts.users_id'                        => $user_id,
                'glpi_plugin_activity_holidaycounts.plugin_activity_holidaytypes_id' => $plugin_activity_holidaytypes_id,
                'glpi_plugin_activity_holidayperiods.begin' => ['>=', $old_annee . '-06-01'],
                'glpi_plugin_activity_holidayperiods.end'   => ['<=', $next_annee . '-05-31'],
            ],
        ]);

        foreach ($iterator as $data) {
            $count += $data['count'];
        }
        return $count;
    }

   /**
    * Get the current periods with the date
    *
    * @global mixed $DB
    *
    * @param mixed $start
    * @param mixed $end
    *
    * @return mixed
    */
    function getCurrentPeriods()
    {
        global $DB;

        $hcounts = [];

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_activity_holidayperiods',
            'WHERE' => [
                'NOT' => ['archived' => 1],
            ],
        ]);

        foreach ($iterator as $data) {
            $hcounts[] = $data;
        }
        return $hcounts;
    }

    function showCountForHolidayTypeAndPeriod($period, $periods)
    {
        global $DB;

        $hcounts = [];
        $user_id = Session::getLoginUserID();

        $period_id = [];
        if (isset($periods['period'])) {
            foreach ($periods['period'] as $key => $data) {
                $period_id[] = $key;
            }
        }

        $where = [
            'glpi_plugin_activity_holidaycounts.users_id'       => $user_id,
            'glpi_plugin_activity_holidayperiods.short_name'   => ['LIKE', $period]
        ];
        if (count($period_id) > 0) {
            $where['glpi_plugin_activity_holidaycounts.plugin_activity_holidayperiods_id'] = $period_id;
        }

        $iterator = $DB->request([
            'SELECT'    => ['glpi_plugin_activity_holidaycounts.*'],
            'FROM'      => 'glpi_plugin_activity_holidaycounts',
            'LEFT JOIN' => [
                'glpi_plugin_activity_holidayperiods' => [
                    'ON' => [
                        'glpi_plugin_activity_holidaycounts' => 'plugin_activity_holidayperiods_id',
                        'glpi_plugin_activity_holidayperiods' => 'id',
                    ],
                ],
            ],
            'WHERE' => $where,
        ]);

        foreach ($iterator as $data) {
            $hcounts[] = $data;
        }
        return $hcounts;
    }

   /*from lateralmenu*/
    function buildHolidayDetailsByPeriod($nbHolidays): array
    {
        if ($nbHolidays['total'] == 0) {
            return ['has_data' => false, 'rows' => []];
        }

        $rows     = [];
        $total_CP = 0;

        $CP = $this->showCountForHolidayTypeAndPeriod(HolidayType::CP, $nbHolidays);
        foreach ($CP as $val) {
            $remaining = $val['count'] - $nbHolidays['period'][$val['plugin_activity_holidayperiods_id']];
            if ($remaining > 0) {
                $holidayperiod = new HolidayPeriod();
                $holidayperiod->getFromDB($val['plugin_activity_holidayperiods_id']);
                $rows[] = [
                    'type'  => 'period_header',
                    'label' => Dropdown::getDropdownName('glpi_plugin_activity_holidayperiods', $val['plugin_activity_holidayperiods_id']),
                ];
                $rows[] = [
                    'type'     => 'period_detail',
                    'end_date' => Html::convDate($holidayperiod->fields['end']),
                    'count'    => $remaining,
                ];
            }
            $total_CP += $remaining;
        }

        $RT        = $this->showCountForHolidayTypeAndPeriod(HolidayType::RTT, $nbHolidays);
        $total_RTT = 0;
        foreach ($RT as $val) {
            $remaining = $val['count'] - $nbHolidays['period'][$val['plugin_activity_holidayperiods_id']];
            if ($remaining > 0) {
                $holidayperiod = new HolidayPeriod();
                $holidayperiod->getFromDB($val['plugin_activity_holidayperiods_id']);
                $rows[] = [
                    'type'  => 'period_header',
                    'label' => $holidayperiod->getName(),
                ];
                $rows[] = [
                    'type'     => 'period_detail',
                    'end_date' => Html::convDate($holidayperiod->fields['end']),
                    'count'    => $remaining,
                ];
            }
            $total_RTT += $remaining;
        }

        $rows[] = [
            'type'  => 'total',
            'count' => $total_CP + $total_RTT,
        ];

        return ['has_data' => true, 'rows' => $rows];
    }
}
