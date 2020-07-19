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

ini_set("memory_limit", "-1");

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('..'));
}

include_once (GLPI_ROOT."/inc/autoload.function.php");
include_once (GLPI_ROOT."/inc/db.function.php");
include_once (GLPI_ROOT."/inc/based_config.php");
include_once (GLPI_CONFIG_DIR."/config_db.php");
include_once (GLPI_ROOT."/inc/define.php");

$GLPI = new GLPI();
$GLPI->initLogger();
Config::detectRootDoc();

if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
} else {
   die("Can't write in ".GLPI_SESSION_DIR."\r\n");
}
Session::start();
$_SESSION['glpi_use_mode'] = 0;
Session::loadLanguage();

if (!$DB->connected) {
   die("No DB connection\r\n");
}
$CFG_GLPI['notifications_ajax'] =0;
$CFG_GLPI['notifications_mailing'] =0;
$CFG_GLPI['use_notifications'] =0;

function update251to300() {
   global $DB;
   $dbu  = new DbUtils();

   $migration = new Migration("3.0.0");

   $migrate_cat_activities_query = "INSERT INTO `glpi_planningeventcategories`(`id`,`name`, `comment`, `date_creation`) 
           SELECT `glpi_plugin_activity_activitytypes`.`id`,`glpi_plugin_activity_activitytypes`.`completename`,`glpi_plugin_activity_activitytypes`.`comment`, NOW()  
            FROM  `glpi_plugin_activity_activitytypes`;";

   $DB->query($migrate_cat_activities_query);

   $dbu  = new DbUtils();
   $activities = $dbu->getAllDataFromTable('glpi_plugin_activity_activities');
   foreach ($activities as $activity) {

      $migrate_activities_query = 'INSERT INTO `glpi_planningexternalevents`(`id`,`entities_id`,`users_id`,`name`,`text`,`begin`,`end`,`planningeventcategories_id`,`state`)
                                    VALUES("'.$activity['id'].'", "'.$activity['entities_id'].'", "'.$activity['users_id'].'", "'.addslashes($activity['name']).'", "'.addslashes($activity['comment']).'",
                                     "'.$activity['begin'].'", "'.$activity['end'].'","'.$activity['plugin_activity_activitytypes_id'].'"
                                       , "'.$activity['is_planned'].'")';


      $DB->query($migrate_activities_query);

      $migrate_cra_activities_query = 'INSERT INTO `glpi_plugin_activity_planningexternalevents` (`is_oncra`, `planningexternalevents_id`, `actiontime`) VALUES 
                                           ("'.$activity['is_usedbycra'].'", "'.$activity['id'].'", "'. $activity['actiontime'] .'")';


      $DB->query($migrate_cra_activities_query);
   }

   $migration->dropTable('glpi_plugin_activity_activitytypes');
   $migration->dropTable('glpi_plugin_activity_activities');

}