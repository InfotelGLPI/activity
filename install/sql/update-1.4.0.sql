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

ALTER TABLE `glpi_plugin_activity` CHANGE `begin_date` `begin_date` DATETIME NULL default NULL;
ALTER TABLE `glpi_plugin_activity` CHANGE `end_date` `end_date` DATETIME NULL default NULL;
UPDATE `glpi_plugin_activity` SET `begin_date` = NULL WHERE `begin_date` ='0000-00-00 00:00:00';
UPDATE `glpi_plugin_activity` SET `end_date` = NULL WHERE `end_date` ='0000-00-00 00:00:00';

ALTER TABLE `glpi_plugin_activity` ADD INDEX `tech_num` (`tech_num`);
ALTER TABLE `glpi_plugin_activity` ADD INDEX `end_date` (`end_date`);
ALTER TABLE `glpi_plugin_activity` ADD INDEX `deleted` (`deleted`);
ALTER TABLE `glpi_plugin_activity` ADD INDEX `begin_date` (`begin_date`);
ALTER TABLE `glpi_plugin_activity` ADD INDEX `type` (`type`);
ALTER TABLE `glpi_plugin_activity` ADD INDEX `FK_entities` (`FK_entities`);

ALTER TABLE `glpi_dropdown_plugin_activity_type` ADD INDEX `FK_profiles` (`FK_profiles`);