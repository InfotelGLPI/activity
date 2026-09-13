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

/// PlanningEventSubCategory class
class PlanningEventSubCategory extends CommonDropdown
{
    public $can_be_translated  = true;
    public static $rightname = "dropdown";

    public static function canCreate(): bool
    {
        return Session::haveRight('plugin_activity', CREATE)
            && Session::haveRight("plugin_activity_all_users", 1);
    }

    /**
     * Security: canCreate() was overridden but canUpdate() and canPurge() were not, so
     * they fell back to CommonDBTM and therefore to $rightname, that is to the core
     * `dropdown` right -- routinely granted to Technician and Supervisor profiles. The
     * creation of a subcategory was thus reserved to the plugin administrators while its
     * modification and its deletion were not, which makes the restriction on creation
     * pointless: an existing row could simply be renamed. Same asymmetry as the one
     * already fixed in HolidayType.
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

    public static function getTypeName($nb = 0)
    {
        return _n('Event subcategory', 'Event subcategories', $nb, 'activity');
    }
}
