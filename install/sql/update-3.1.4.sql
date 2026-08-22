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

DROP TABLE IF EXISTS `glpi_plugin_activity_planningeventsubcategories`;
CREATE TABLE `glpi_plugin_activity_planningeventsubcategories`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8_unicode_ci NOT NULL default '',
    `comment`      text collate utf8_unicode_ci,
    PRIMARY KEY (`id`),
    KEY            `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_activity_planningexternalevents`
    ADD `planningeventsubcategories_id` int unsigned;
ALTER TABLE `glpi_plugin_activity_planningexternalevents`
    ADD KEY `planningeventsubcategories_id` (`planningeventsubcategories_id`);
ALTER TABLE `glpi_plugin_activity_configs`
    ADD `use_planningeventsubcategories` tinyint DEFAULT '0';
ALTER TABLE `glpi_plugin_activity_options`
    ADD `use_planningeventsubcategories` tinyint DEFAULT '0';
