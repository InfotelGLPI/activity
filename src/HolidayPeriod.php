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

use CommonDropdown;
use Session;

class HolidayPeriod extends CommonDropdown
{
    public $can_be_translated  = true;

    /**
     * Security: this dropdown is the HR reference of the holiday periods (the half/full
     * day labels and their date range) that the request form and the counters are built
     * on. The class declared neither $rightname nor any can*() override, so CommonDropdown
     * applied the core `dropdown` right -- routinely granted to Technician and Supervisor
     * profiles -- and any of them could rewrite or purge the reference of the whole
     * instance. The plugin's own administrative right is required instead, exactly as in
     * HolidayType.
     */
    public static function canCreate(): bool
    {
        return Session::haveRight('plugin_activity', CREATE)
                 && Session::haveRight("plugin_activity_all_users", 1);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRight('plugin_activity', UPDATE)
                 && Session::haveRight("plugin_activity_all_users", 1);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight('plugin_activity', PURGE)
                 && Session::haveRight("plugin_activity_all_users", 1);
    }

    // canView() is deliberately left on the inherited `dropdown` right, for the same
    // reason as in HolidayType: every requester has to read these labels to fill the
    // request form, so restricting the read would empty that dropdown.

    public static function getTypeName($nb = 0)
    {
        return _n('Holiday period', 'Holiday periods', $nb, 'activity');
    }


    public function getAdditionalFields()
    {

        return [
            [
                'name'  => 'short_name',
                'label' => __('Short name', 'activity'),
                'type'  => 'text',
                'list'  => true],
            [
                'name'  => 'begin',
                'label' => __('Begin date'),
                'type'  => 'date',
                'list'  => true],
            [
                'name'  => 'end',
                'label' => __('End date'),
                'type'  => 'date',
                'list'  => true],
            [
                'name'  => 'archived',
                'label' => __('Archived', 'activity'),
                'type'  => 'bool',
                'list'  => true],
        ];
    }

    public function prepareInputForAdd($input)
    {

        if (!isset($input['begin']) || $input['begin'] == "") {
            Session::addMessageAfterRedirect(__('Please fill a begin date', 'activity'), false, ERROR);
            return false;
        }
        if (!isset($input['end']) || $input['end'] == "") {
            Session::addMessageAfterRedirect(__('Please fill an end date', 'activity'), false, ERROR);
            return false;
        }

        return $input;
    }

}
