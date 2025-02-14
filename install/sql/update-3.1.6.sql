ALTER TABLE `glpi_plugin_activity_options`
    ADD `show_planningevents_entity` tinyint DEFAULT '0';

ALTER TABLE `glpi_plugin_activity_options`
    ADD `use_planning_activity_hours` tinyint DEFAULT '1';
