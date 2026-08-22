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

ALTER TABLE `glpi_plugin_activity_options` ADD `use_project` tinyint(11) DEFAULT '0';
ALTER TABLE `glpi_plugin_activity_options` ADD `is_cra_default_project` tinyint(11) DEFAULT '0';

DROP TABLE IF EXISTS `glpi_plugin_activity_projecttasks`;
CREATE TABLE `glpi_plugin_activity_projecttasks` (
   `id` int(11) NOT NULL auto_increment,
   `is_oncra` tinyint(1) default '1',
   `projecttasks_id` int(11) NOT NULL,
   PRIMARY KEY  (`id`),
   FOREIGN KEY (`projecttasks_id`) REFERENCES glpi_projecttasks(id),
   KEY `is_oncra` (`is_oncra`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;