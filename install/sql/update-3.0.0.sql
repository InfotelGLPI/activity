--
-- -------------------------------------------------------------------------
-- activity plugin for GLPI
-- Copyright (C) 2019-2026 by the activity Development Team.
--
-- https://github.com/InfotelGLPI/activity
-- -------------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of activity.
--
-- activity is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- activity is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with activity. If not, see <http://www.gnu.org/licenses/>.
-- --------------------------------------------------------------------------
--

DROP TABLE IF EXISTS `glpi_plugin_activity_planningexternalevents`;
CREATE TABLE `glpi_plugin_activity_planningexternalevents` (
   `id` int(11) NOT NULL auto_increment,
   `is_oncra` tinyint(1) default '1',
   `planningexternalevents_id` int(11) NOT NULL,
   `actiontime` int(11) NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `is_oncra` (`is_oncra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_activity_holidays` CHANGE `date_mod` `date_mod` timestamp NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_activity_holidays` CHANGE `begin` `begin` timestamp NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_activity_holidays` CHANGE `end` `end` timestamp NULL DEFAULT NULL;

ALTER TABLE `glpi_plugin_activity_holidayvalidations` CHANGE `submission_date` `submission_date` timestamp NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_activity_holidayvalidations` CHANGE `validation_date` `validation_date` timestamp NULL DEFAULT NULL;

ALTER TABLE `glpi_plugin_activity_holidaycounts` CHANGE `date_mod` `date_mod` timestamp NULL DEFAULT NULL;

ALTER TABLE `glpi_plugin_activity_snapshots` CHANGE `date` `date` timestamp NULL DEFAULT NULL;

ALTER TABLE `glpi_plugin_activity_tickettasks` DROP INDEX `tickettasks_id`;
ALTER TABLE `glpi_plugin_activity_projecttasks` DROP INDEX `projecttasks_id`;
