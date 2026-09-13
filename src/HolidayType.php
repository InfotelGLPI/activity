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

/// HolidayType class
class HolidayType extends CommonDropdown
{
    public $can_be_translated  = true;
    public static $rightname = "dropdown";

    public const RTT = 'RT';
    public const CP = 'CP';

    public static function canCreate(): bool
    {
        return Session::haveRight('plugin_activity', CREATE)
                 && Session::haveRight("plugin_activity_all_users", 1);
    }

    /**
     * Security: canCreate() was overridden but canUpdate()/canPurge() were not, so
     * they fell back to CommonDBTM and therefore to the core `dropdown` right --
     * routinely granted to Technician and Supervisor profiles and unrelated to the
     * HR entitlement the plugin models. A holiday type drives business logic:
     * auto_validated makes every request on that type ACCEPTED without ever going
     * through HolidayValidation, and is_holiday_counter=0 keeps the absence out of
     * the user's balance. Editing the reference data that governs auto-validation
     * must not be easier than editing the requests themselves.
     */
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

    // canView() is deliberately left on the inherited `dropdown` right: the holiday
    // type is a plain label that every requester has to read to fill the request form,
    // so restricting it to plugin_activity_all_users would empty that dropdown. Only
    // the write paths above carry the plugin's own administrative right.

    public static function getTypeName($nb = 0)
    {
        return _n('Holiday type', 'Holiday types', $nb, 'activity');
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
                'name'  => 'mandatory_comment',
                'label' => __('Mandatory comment', 'activity'),
                'type'  => 'bool',
                'list'  => true],
            [
                'name'  => 'auto_validated',
                'label' => __('Auto-validated', 'activity'),
                'type'  => 'bool',
                'list'  => true],
            [
                'name'  => 'is_holiday',
                'label' => __('Holiday', 'activity'),
                'type'  => 'bool',
                'list'  => true],
            [
                'name'  => 'is_holiday_counter',
                'label' => __('Use this holiday in the counter', 'activity'),
                'type'  => 'bool',
                'list'  => true],
            [
                'name'  => 'is_sickness',
                'label' => __('Sickness', 'activity'),
                'type'  => 'bool',
                'list'  => true],
            [
                'name'  => 'is_part_time',
                'label' => __('Part time', 'activity'),
                'type'  => 'bool',
                'list'  => true],
            [
                'name'  => 'is_period',
                'label' => __('Linked to a period', 'activity'),
                'type'  => 'bool',
                'list'  => true],
        ];
    }

    public function prepareInputForAdd($input)
    {
        parent::prepareInputForAdd($input);
        if (isset($input['is_holiday'])
                && isset($input['is_sickness'])
                && isset($input['is_part_time'])) {
            if (($input['is_holiday'] + $input['is_sickness'] + $input['is_part_time']) > 1) {
                Session::addMessageAfterRedirect(__("Can't be of more than one type at the same time", "activity"), false, ERROR);
                return false;
            }
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        parent::prepareInputForUpdate($input);
        if (isset($input['is_holiday'])
                && isset($input['is_sickness'])
                && isset($input['is_part_time'])) {
            if (($input['is_holiday'] + $input['is_sickness'] + $input['is_part_time']) > 1) {
                Session::addMessageAfterRedirect(__("Can't be of more than one type at the same time", "activity"), false, ERROR);
                return false;
            }
        }

        return $input;
    }

    /**
     * Tell whether the given holiday type is bound to a period.
     *
     * The id comes straight from an AJAX client, so an unknown one used to read
     * fields['is_period'] on an empty array and emit an undefined-key warning inside
     * the response body instead of answering plainly.
     *
     * @param int $holiday_type_id
     *
     * @return bool
     */
    public static function isPeriod($holiday_type_id): bool
    {
        $holidaytype = new self();
        if (!$holidaytype->getFromDB((int) $holiday_type_id)) {
            return false;
        }

        return (bool) $holidaytype->fields['is_period'];
    }
}
