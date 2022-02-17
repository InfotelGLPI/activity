DROP TABLE IF EXISTS `glpi_plugin_activity_holidays`;
CREATE TABLE `glpi_plugin_activity_holidays` (
   `id` int unsigned NOT NULL auto_increment,
   `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
   `date_mod` timestamp NULL DEFAULT NULL,
   `begin` timestamp NULL DEFAULT NULL,
   `end`  timestamp NULL DEFAULT NULL,
   `is_planned` tinyint NOT NULL DEFAULT '0',
   `global_validation` tinyint NOT NULL DEFAULT '0' COMMENT '1 -> NONE ; 2 -> Waiting ; 3 -> accepted ; 4 -> rejected',
   `comment` text COLLATE utf8mb4_unicode_ci default NULL,
   `actiontime` int unsigned NOT NULL DEFAULT '0',
   `plugin_activity_holidaytypes_id` int unsigned NOT NULL default '0',
   `users_id` int unsigned NOT NULL default '0',
   `allDay` tinyint NOT NULL DEFAULT '0',
   `validation_percent` int unsigned NOT NULL DEFAULT '0',
   `plugin_activity_holidayperiods_id` int unsigned default '0',
   PRIMARY KEY  (`id`),
   KEY `users_id` (`users_id`),
   KEY `plugin_activity_holidaytypes_id` (`plugin_activity_holidaytypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_holidaytypes`;
CREATE TABLE `glpi_plugin_activity_holidaytypes` (
   `id` int unsigned NOT NULL auto_increment,
   `name` varchar(255)  collate utf8mb4_unicode_ci NOT NULL default '',
   `comment` text collate utf8mb4_unicode_ci,
   `short_name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `mandatory_comment` tinyint NOT NULL default '0',
   `auto_validated` tinyint default '0',
   `is_holiday` tinyint default '1',
   `is_sickness` tinyint default '0',
   `is_part_time` tinyint default '0',
   `is_holiday_counter` tinyint default '0',
   `is_period` tinyint default '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_configs`;
CREATE TABLE `glpi_plugin_activity_configs` (
   `id` int unsigned NOT NULL auto_increment,
   `name` varchar(255)  collate utf8mb4_unicode_ci NOT NULL default '',
   `entities_id` int unsigned NOT NULL,
   `use_mandaydisplay` tinyint DEFAULT '0',
   `use_integerschedules` tinyint DEFAULT '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_options`;
CREATE TABLE `glpi_plugin_activity_options` (
   `id` int unsigned NOT NULL auto_increment,
   `principal_client` varchar(255)  collate utf8mb4_unicode_ci NOT NULL default '',
   `cra_footer` text collate utf8mb4_unicode_ci,
   `used_mail_for_holidays` varchar(255)  collate utf8mb4_unicode_ci NOT NULL default '',
   `use_type_as_name` tinyint default '0',
   `use_timerepartition` tinyint default '0',
   `use_mandaydisplay` tinyint default '0',
   `use_pairs` tinyint default '0',
   `use_integerschedules` tinyint default '0',
   `use_groupmanager` tinyint default '0',
   `default_validation_percent` int unsigned NOT NULL DEFAULT '0',
   `is_cra_default` tinyint unsigned NOT NULL DEFAULT '0',
   `use_project` tinyint unsigned NOT NULL DEFAULT '0',
   `use_weekend` tinyint unsigned NOT NULL DEFAULT '0',
   `is_cra_default_project` tinyint unsigned NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_activity_options` (`principal_client`, `cra_footer`, `used_mail_for_holidays`)
VALUES('Glpi', 'Footer', '');

DROP TABLE IF EXISTS `glpi_plugin_activity_tickettasks`;
CREATE TABLE `glpi_plugin_activity_tickettasks` (
   `id` int unsigned NOT NULL auto_increment,
   `is_oncra` tinyint default '1',
   `tickettasks_id` int unsigned NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `is_oncra` (`is_oncra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_preferences`;
CREATE TABLE `glpi_plugin_activity_preferences` (
   `id` int unsigned NOT NULL auto_increment,
   `users_id` int unsigned NOT NULL DEFAULT '0',
   `users_id_validate` int unsigned NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_holidayvalidations`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_activity_holidayvalidations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_activity_holidays_id` int unsigned NOT NULL DEFAULT '0',
  `users_id_validate` int unsigned NOT NULL DEFAULT '0',
  `comment_validation` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint (1) NOT NULL DEFAULT 2 COMMENT '1 -> NONE ; 2 -> Waiting ; 3 -> accepted ; 4 -> rejected',
  `submission_date` timestamp NULL DEFAULT NULL,
  `validation_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id_validate` (`users_id_validate`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_holidaycounts`;
CREATE TABLE `glpi_plugin_activity_holidaycounts` (
   `id` int unsigned NOT NULL auto_increment,
   `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
   `date_mod` timestamp NULL DEFAULT NULL,
   `count` decimal(20,1) NOT NULL DEFAULT '0.00',
   `plugin_activity_holidayperiods_id` int unsigned NOT NULL default '0',
   `plugin_activity_holidaytypes_id` int unsigned NOT NULL default '0',
   `users_id` int unsigned NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `users_id` (`users_id`),
   KEY `plugin_activity_holidayperiods_id` (`plugin_activity_holidayperiods_id`),
   KEY `plugin_activity_holidaytypes_id` (`plugin_activity_holidaytypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_holidayperiods`;
CREATE TABLE `glpi_plugin_activity_holidayperiods` (
   `id` int unsigned NOT NULL auto_increment,
   `name` varchar(255)  collate utf8mb4_unicode_ci NOT NULL default '',
   `short_name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `comment` text collate utf8mb4_unicode_ci,
   `begin` timestamp NULL DEFAULT NULL,
   `end`  timestamp NULL DEFAULT NULL,
   `archived` tinyint (1) NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_snapshots`;
CREATE TABLE `glpi_plugin_activity_snapshots` (
   `id` int unsigned NOT NULL auto_increment,
   `documents_id` int unsigned NOT NULL,
   `date` timestamp NULL DEFAULT NULL,
   `month` int unsigned NOT NULL,
   `year` int unsigned NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_projecttasks`;
CREATE TABLE `glpi_plugin_activity_projecttasks` (
   `id` int unsigned NOT NULL auto_increment,
   `is_oncra` tinyint default '1',
   `projecttasks_id` int unsigned NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `is_oncra` (`is_oncra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_activity_externalevents`;
CREATE TABLE `glpi_plugin_activity_planningexternalevents` (
   `id` int unsigned NOT NULL auto_increment,
   `is_oncra` tinyint default '1',
   `planningexternalevents_id` int unsigned NOT NULL,
   `actiontime` int unsigned NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `is_oncra` (`is_oncra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Holidays validation', 'PluginActivityHoliday', '2014-01-09 08:36:14','',NULL, '2014-01-09 08:36:14');
