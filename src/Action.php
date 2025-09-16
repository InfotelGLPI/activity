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

namespace GlpiPlugin\Activity;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Action
{
    const ADD_ACTIVITY = 'add_activity';
    const LIST_ACTIVITIES = 'list_activities';
    const HOLIDAY_REQUEST = 'holiday_request';
    const LIST_HOLIDAYS = 'list_holidays';
    const APPROVE_HOLIDAYS = 'validate_holidays';
    const CRA = 'cra';
    const HOLIDAY_COUNT = 'holiday_count';
    const MANAGER = 'manager';
}
