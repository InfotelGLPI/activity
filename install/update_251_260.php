<?php

/*
 -------------------------------------------------------------------------
 Activity plugin for GLPI
 Copyright (C) 2019 by the Activity Development Team.
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

/**
 * Update from 2.5.1 to 2.6.0
 * Glpi upgrade to 9.5
 * @return bool for success (will die for most error)
 * */

function update251to260() {
   global $DB;
   $migration = new Migration("2.6.0");

   $migrate_cat_activities_query = "INSERT INTO `glpi_planningeventcategories`(`id`,`name`, `comment`, `date_creation`) 
           SELECT `glpi_plugin_activity_activitytypes`.`id`,`glpi_plugin_activity_activitytypes`.`name`,`glpi_plugin_activity_activitytypes`.`comment`, NOW()  FROM  `glpi_plugin_activity_activitytypes`;";

   $DB->query($migrate_cat_activities_query);

   $migrate_activities_query = "INSERT INTO `glpi_planningexternalevents`(`id`,`entities_id`,`date`,`users_id`,`name`,`text`,`begin`,`end`,`rrule`,`planningeventcategories_id`,`state`)
                SELECT `glpi_plugin_activity_activities`.`id`, `glpi_plugin_activity_activities`.`entities_id`, '', `glpi_plugin_activity_activities`.`users_id`, `glpi_plugin_activity_activities`.`name`,
                        `glpi_plugin_activity_activities`.`comment`, `glpi_plugin_activity_activities`.`begin`, `glpi_plugin_activity_activities`.`end`, '',
                        `glpi_plugin_activity_activities`.`plugin_activity_activitytypes_id`, `glpi_plugin_activity_activities`.`is_planned` FROM `glpi_plugin_activity_activities`;";
   $DB->query($migrate_activities_query);

   $migration->dropTable('glpi_plugin_activity_activitytypes');
   $migration->dropTable('glpi_planningexternalevents');

}