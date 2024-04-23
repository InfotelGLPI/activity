DROP TABLE IF EXISTS `glpi_plugin_activity_planningeventsubcategories`;
CREATE TABLE `glpi_plugin_activity_planningeventsubcategories`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8_unicode_ci NOT NULL default '',
    `comment`      text collate utf8_unicode_ci,
    PRIMARY KEY (`id`),
    KEY            `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_activity_planningexternalevents`
    ADD `planningeventsubcategories_id` int unsigned;
ALTER TABLE `glpi_plugin_activity_planningexternalevents`
    ADD KEY `planningeventsubcategories_id` (`planningeventsubcategories_id`);
ALTER TABLE `glpi_plugin_activity_configs`
    ADD `use_planningeventsubcategories` tinyint DEFAULT '0';
ALTER TABLE `glpi_plugin_activity_options`
    ADD `use_planningeventsubcategories` tinyint DEFAULT '0'
