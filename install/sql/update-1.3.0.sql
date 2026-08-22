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

ALTER TABLE `glpi_plugin_activity_profiles` DROP `create_activity`;
ALTER TABLE `glpi_plugin_activity_profiles` DROP `update_activity`;
ALTER TABLE `glpi_plugin_activity_profiles` DROP `delete_activity`;
ALTER TABLE `glpi_plugin_activity_profiles` CHANGE `is_default` `is_default` smallint(6) NOT NULL default '0';
UPDATE `glpi_plugin_activity_profiles` SET `is_default` = '0' WHERE `is_default` = '1';
UPDATE `glpi_plugin_activity_profiles` SET `is_default` = '1' WHERE `is_default` = '2';

RENAME TABLE `glpi_plugin_activity_tasks`  TO `glpi_plugin_activity` ;

ALTER TABLE `glpi_plugin_activity` CHANGE `date` `begin_date` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `glpi_plugin_activity` ADD `end_date` datetime NOT NULL default '0000-00-00 00:00:00' AFTER `begin_date`;
ALTER TABLE `glpi_plugin_activity` ADD `use_planning` smallint(6) NOT NULL default '1' AFTER `end_date`;
ALTER TABLE `glpi_plugin_activity` CHANGE `author` `tech_num` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_activity` CHANGE `contents` `comments` text;
ALTER TABLE `glpi_plugin_activity` ADD `FK_entities` int(11) NOT NULL default '0' AFTER `ID`;
ALTER TABLE `glpi_plugin_activity` CHANGE `deleted` `deleted` smallint(6) NOT NULL default '0';
UPDATE `glpi_plugin_activity` SET `deleted` = '0' WHERE `deleted` = '1';
UPDATE `glpi_plugin_activity` SET `deleted` = '1' WHERE `deleted` = '2';
UPDATE `glpi_plugin_activity` SET `end_date` = `begin_date`;

RENAME TABLE glpi_dropdown_plugin_activity_tasks_type  TO glpi_dropdown_plugin_activity_type ;

ALTER TABLE `glpi_dropdown_plugin_activity_type` ADD `parentID` INT( 11 ) NOT NULL DEFAULT '0' AFTER `ID` ;
ALTER TABLE `glpi_dropdown_plugin_activity_type` ADD `completename` TEXT  AFTER `name` ;
ALTER TABLE `glpi_dropdown_plugin_activity_type` ADD `level` INT( 11 ) NULL DEFAULT NULL AFTER `comments` ;
ALTER TABLE `glpi_dropdown_plugin_activity_type` ADD `FK_profiles` int(4) NOT NULL default '0' AFTER `level` ;
UPDATE `glpi_dropdown_plugin_activity_type` SET `level` = '1';
UPDATE `glpi_dropdown_plugin_activity_type` SET `FK_profiles` = '4';
UPDATE `glpi_dropdown_plugin_activity_type` SET `completename` = `name`;

INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'1100','2','1','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'1100','3','2','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'1100','4','3','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'1100','6','4','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'1100','7','5','0');