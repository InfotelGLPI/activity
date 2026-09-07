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
 * Update from 2.5.1 to 2.6.0
 * Glpi upgrade to 9.5
 * @return bool for success (will die for most error)
 * */

ini_set("memory_limit", "-1");
ini_set("max_execution_time", 0);
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

//chdir(dirname($_SERVER["argv"][0]));

define("GLPI_DIR_ROOT", realpath(dirname($_SERVER["SCRIPT_FILENAME"]) . "/../../.."));
require_once GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);

if (is_writable(GLPI_SESSION_DIR)) {
    Session::setPath();
} else {
    die("Can't write in " . GLPI_SESSION_DIR . "\r\n");
}
Session::start();
$_SESSION['glpi_use_mode'] = 0;
Session::loadLanguage();

global $DB;

if (!$DB->connected) {
    die("No DB connection\r\n");
}
$CFG_GLPI['notifications_ajax']    = 0;
$CFG_GLPI['notifications_mailing'] = 0;
$CFG_GLPI['use_notifications']     = 0;

function update251to300()
{
    global $DB;
    $dbu = new DbUtils();
    $migration = new Migration("3.0.0");

    $add_temporary_column_query = "ALTER TABLE `glpi_planningeventcategories` ADD `old_id` int(11) NOT NULL DEFAULT 0;";
    $DB->doQuery($add_temporary_column_query);

    $cats = $dbu->getAllDataFromTable('glpi_plugin_activity_activitytypes');

    foreach ($cats as $cat) {
        // The category label is user-supplied and was interpolated into the SQL literal
        // with no escaping at all; the neighbouring values went through addslashes(),
        // which is not an SQL escaping function. The query builder quotes them against
        // the connection charset.
        $DB->insert('glpi_planningeventcategories', [
            'name'    => $cat['completename'],
            'comment' => $cat['comment'],
            'old_id'  => $cat['id'],
        ]);

    }


    $activities = $dbu->getAllDataFromTable('glpi_plugin_activity_activities');
    $add_temporary_column_query = "ALTER TABLE `glpi_planningexternalevents` ADD `old_id` int(11) NOT NULL DEFAULT 0;";
    $DB->doQuery($add_temporary_column_query);

    foreach ($activities as $activity) {

        if (strstr($activity['name'], '>')) {
            list($parent, $child) = explode('>', $activity['name']);
            $name = $child;
        } else {
            $name = $activity['name'];
        }
        $DB->insert('glpi_planningexternalevents', [
            'entities_id'                => $activity['entities_id'],
            'users_id'                   => $activity['users_id'],
            'name'                       => $name,
            'text'                       => $activity['comment'],
            'begin'                      => $activity['begin'],
            'end'                        => $activity['end'],
            'state'                      => $activity['is_planned'],
            'planningeventcategories_id' => $activity['plugin_activity_activitytypes_id'],
            'old_id'                     => $activity['id'],
        ]);

        $DB->insert('glpi_plugin_activity_planningexternalevents', [
            'is_oncra'                  => $activity['is_usedbycra'],
            'planningexternalevents_id' => $activity['id'],
            'actiontime'                => $activity['actiontime'],
        ]);
    }

    $new_cats = $dbu->getAllDataFromTable('glpi_planningeventcategories', ['old_id'  => ['>', 0]]);

    foreach ($new_cats as $new_cat) {

        if (strstr($new_cat['name'], '>')) {
            list($parent, $child) = explode('>', $new_cat['name']);
            $name = $child;
        } else {
            $name = $new_cat['name'];
        }
        $DB->update(
            'glpi_planningexternalevents',
            ['planningeventcategories_id' => $new_cat['id']],
            ['planningeventcategories_id' => $new_cat['old_id']],
        );

        $DB->insert('glpi_planningexternaleventtemplates', [
            'name'                       => $name,
            'state'                      => 1,
            'planningeventcategories_id' => $new_cat['id'],
        ]);
    }

    $remove_temporary_column_query = "ALTER TABLE `glpi_planningeventcategories` DROP `old_id`;";
    $DB->doQuery($remove_temporary_column_query);

    $new_events = $dbu->getAllDataFromTable('glpi_planningexternalevents', ['old_id'  => ['>', 0]]);

    foreach ($new_events as $new_event) {

        $DB->update(
            'glpi_plugin_activity_planningexternalevents',
            ['planningexternalevents_id' => $new_event['id']],
            ['planningexternalevents_id' => $new_event['old_id']],
        );


    }

    $remove_temporary_column_query = "ALTER TABLE `glpi_planningexternalevents` DROP `old_id`;";
    $DB->doQuery($remove_temporary_column_query);

    $migration->dropTable('glpi_plugin_activity_activitytypes');
    $migration->dropTable('glpi_plugin_activity_activities');
}
