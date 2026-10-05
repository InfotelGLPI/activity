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

/**
 * Update from 2.2.3 to 2.2.4
 *
 * @return bool for success (will die for most error)
 * */
function update223to224()
{
    global $DB;

    $users = $DB->request([
        'SELECT'   => 'users_id',
        'DISTINCT' => true,
        'FROM'     => 'glpi_plugin_activity_holidays',
    ]);

    foreach ($users as $data_user) {
        $user_id = (int) $data_user['users_id'];

        // Remaining CP and RTT counters of the user, by holiday period
        $periods = [];
        foreach (['CP', 'RT'] as $short_name) {
            $periods[$short_name] = [];
            $counts = $DB->request([
                'FROM'      => 'glpi_plugin_activity_holidaycounts',
                'LEFT JOIN' => [
                    'glpi_plugin_activity_holidayperiods' => [
                        'ON' => [
                            'glpi_plugin_activity_holidaycounts'  => 'plugin_activity_holidayperiods_id',
                            'glpi_plugin_activity_holidayperiods' => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'users_id'                                     => $user_id,
                    'glpi_plugin_activity_holidaycounts.count'     => ['>', 0],
                    'glpi_plugin_activity_holidayperiods.short_name' => ['LIKE', $short_name],
                ],
            ]);
            foreach ($counts as $data_count) {
                $periods[$short_name][$data_count['plugin_activity_holidayperiods_id']] = [
                    'count' => $data_count['count'],
                    'begin' => $data_count['begin'],
                    'end'   => $data_count['end'],
                ];
            }
        }

        $holidays = $DB->request([
            'FROM'  => 'glpi_plugin_activity_holidays',
            'WHERE' => ['users_id' => $user_id],
            'ORDER' => 'id',
        ]);

        // Attach each holiday to the first CP period covering its start date, else to the
        // first RTT one, consuming one unit of the period counter each time
        foreach ($holidays as $data) {
            $begin = strtotime($data['begin']);
            foreach (['CP', 'RT'] as $short_name) {
                foreach ($periods[$short_name] as $period_id => $period) {
                    if ($period['count'] > 0
                        && $begin >= strtotime($period['begin'])
                        && $begin <= strtotime($period['end'])) {
                        $periods[$short_name][$period_id]['count'] -= 1;
                        $DB->update(
                            'glpi_plugin_activity_holidays',
                            ['plugin_activity_holidayperiods_id' => $period_id],
                            ['id' => $data['id']],
                        );
                        continue 3;
                    }
                }
            }
        }
    }

    return true;
}
