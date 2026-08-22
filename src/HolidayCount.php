<?php

/**
 * -------------------------------------------------------------------------
 * activity plugin for GLPI
 * Copyright (C) 2019-2026 by the activity Development Team.
 *
 * https://github.com/InfotelGLPI/activity
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of activity.
 *
 * activity is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * activity is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with activity. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Activity;

use CommonDBTM;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Group_User;
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class HolidayCount extends CommonDBTM
{
    public $dohistory = false;
    public $holidays  = [];
    public static $rightname = "plugin_activity";

    public static function getTypeName($nb = 1)
    {
        return _n('Holiday counter', 'Holiday counters', $nb, 'activity');
    }

    public function canViewItem(): bool
    {
        // Ownership: a holiday counter is user-scoped (users_id) but NOT
        // entity-assigned, so the default can($id, READ) collapses to the global
        // plugin_activity right and never checks ownership — a plain horizontal
        // IDOR on RH balances. Mirror Holiday::canViewItem(): only the owner, a
        // profile holding plugin_activity_all_users, or the manager responsible
        // for validating that user's holiday requests may read it. Every
        // can()/check()/display() path inherits this gate.
        if (Session::haveRight('plugin_activity_all_users', 1)) {
            return true;
        }
        if (isset($this->fields['users_id'])
            && $this->fields['users_id'] == Session::getLoginUserID()) {
            return true;
        }
        return isset($this->fields['users_id'])
            && $this->checkUserIsManager($this->fields['users_id']);
    }

    public function canUpdateItem(): bool
    {
        // Ownership: the global plugin_activity UPDATE right must NOT allow
        // editing another user's counter. Only the owner, or a profile holding
        // plugin_activity_all_users, may update it (mirrors Holiday::canUpdateItem()).
        // Otherwise check($id, UPDATE) would let a colleague falsify someone
        // else's holiday balance.
        if (Session::haveRight('plugin_activity_all_users', 1)) {
            return true;
        }
        return isset($this->fields['users_id'])
            && $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canPurgeItem(): bool
    {
        // Ownership: a counter may be purged by its owner, by a profile holding
        // plugin_activity_all_users, or by the manager responsible for validating
        // that user's holiday requests (mirrors Holiday::canPurgeItem()). The
        // global plugin_activity PURGE right alone is not enough, otherwise
        // check($id, PURGE) would be a plain IDOR deleting a colleague's balance.
        if (Session::haveRight('plugin_activity_all_users', 1)) {
            return true;
        }
        if (isset($this->fields['users_id'])
            && $this->fields['users_id'] == Session::getLoginUserID()) {
            return true;
        }
        return isset($this->fields['users_id'])
            && $this->checkUserIsManager($this->fields['users_id']);
    }

    /**
     * Whether the current user is a validating manager of $users_id (mirrors
     * Holiday::checkUserIsManager()): either declared as users_id_validate in the
     * target's activity preferences, or a group manager of one of their groups.
     */
    private function checkUserIsManager($users_id = 0): bool
    {
        $use_groupmanager = 0;
        $opt              = new Option();
        $opt->getFromDB(1);
        if ($opt) {
            $use_groupmanager = $opt->fields['use_groupmanager'];
        }
        $dbu = new DbUtils();
        if ($use_groupmanager == 0) {
            $datas = $dbu->getAllDataFromTable(
                "glpi_plugin_activity_preferences",
                ["users_id" => $users_id, "users_id_validate" => Session::getLoginUserID()],
            );
        } else {
            $datas      = [];
            $groupusers = Group_User::getUserGroups($users_id);
            $groups     = [];
            foreach ($groupusers as $groupuser) {
                $groups[] = $groupuser["id"];
            }

            $restrict = ["groups_id" => $groups, "is_manager" => 1];
            $managers = $dbu->getAllDataFromTable('glpi_groups_users', $restrict);

            foreach ($managers as $manager) {
                if ($manager['users_id'] == Session::getLoginUserID()) {
                    $datas['users_id_validate'] = $manager['users_id'];
                }
            }
        }

        return count($datas) > 0;
    }

    /*
    function cleanDBonPurge() {
       $holidayValidation = new HolidayValidation();
       $holidayValidation->cleanDBonItemDelete($this->getType(),$this->fields['id']);

       parent::cleanDBonPurge();
    }*/


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }

    /**
     * @see CommonDBTM::prepareInputForUpdate()
     **/
    public function prepareInputForUpdate($input)
    {
        // Security (identity spoofing): mirror prepareInputForAdd(). The check($id,
        // UPDATE) guard only proves the caller may edit the record as stored in DB;
        // it does not vet a users_id present in the payload. Since CommonDBTM::update()
        // persists any posted real column, a posted users_id would be written verbatim,
        // letting an owner re-assign the counter to a colleague (falsifying their
        // holiday balance). Realign the owner on the stored value unless the caller
        // holds plugin_activity_all_users.
        if (!Session::haveRight('plugin_activity_all_users', 1)) {
            $input['users_id'] = $this->fields['users_id'];
        }

        return $input;
    }

    /**
     * @see CommonDBTM::prepareInputForAdd()
     **/
    public function prepareInputForAdd($input)
    {
        // Security (identity spoofing): never trust a posted users_id. A holiday
        // counter must belong to the current user; only a profile holding
        // plugin_activity_all_users may manage one on behalf of someone else.
        // Without this, any holder of plugin_activity CREATE could POST
        // users_id=<colleague> and inflate/falsify their holiday balance.
        if (!Session::haveRight('plugin_activity_all_users', 1)) {
            $input['users_id'] = Session::getLoginUserID();
        }

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


    public function rawSearchOptions()
    {

        $holidaytype   = new HolidayType();
        $holidayperiod = new HolidayPeriod();

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(1),
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'itemlink_type' => $this->getType(),
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
            'datatype' => 'decimal',
        ];

        $tab[] = [
            'id'            => '12',
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'massiveaction' => false,
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
        ];

        $tab[] = [
            'id'       => '30',
            'table'    => $this->getTable(),
            'field'    => 'id',
            'name'     => __('ID'),
            'datatype' => 'number',
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
    public function showForm($ID, $options = [])
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

    public function showCountForHolidayType($plugin_activity_holidaytypes_id)
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
    public function getCurrentPeriods()
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

    public function showCountForHolidayTypeAndPeriod($period, $periods)
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
            'glpi_plugin_activity_holidayperiods.short_name'   => ['LIKE', $period],
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
    public function buildHolidayDetailsByPeriod($nbHolidays): array
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
