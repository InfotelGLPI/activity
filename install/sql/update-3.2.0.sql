UPDATE `glpi_displaypreferences` SET `itemtype` = 'GlpiPlugin\\Activity\\Holiday' WHERE `glpi_displaypreferences`.`itemtype` = 'PluginActivityHoliday';
UPDATE `glpi_notificationtemplates` SET `itemtype` = 'GlpiPlugin\\Activity\\Holiday' WHERE `itemtype` = 'PluginActivityHoliday';
UPDATE `glpi_notifications` SET `itemtype` = 'GlpiPlugin\\Activity\\Holiday' WHERE `itemtype` = 'PluginActivityHoliday';
