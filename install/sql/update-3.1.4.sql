DROP TABLE IF EXISTS `glpi_plugin_activity_planningeventsubcategories`;
CREATE TABLE `glpi_plugin_activity_planningeventsubcategories`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8_unicode_ci NOT NULL default '',
    `comment`      text collate utf8_unicode_ci,
    `entities_id`  int unsigned NOT NULL,
    `is_recursive` tinyint                                          DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY            `name` (`name`),
    KEY            `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_activity_planningexternalevents`
    ADD `plugin_activity_planningeventsubcategories_id` int unsigned;
ALTER TABLE `glpi_plugin_activity_planningexternalevents`
    ADD KEY `plugin_activity_planningeventsubcategories_id` (`plugin_activity_planningeventsubcategories_id`);
ALTER TABLE `glpi_plugin_activity_configs`
    ADD `use_planningeventsubcategories` tinyint DEFAULT '0'
