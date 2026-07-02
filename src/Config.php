<?php

/*
 -------------------------------------------------------------------------
 activity plugin for GLPI
 Copyright (C) 2019-2026 by the activity Development Team.

 https://github.com/InfotelGLPI/activity
 -------------------------------------------------------------------------

 LICENSE

 This file is part of activity.

 activity is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 activity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with activity. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Activity;

use CommonDBTM;
use DbUtils;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Config extends CommonDBTM
{
    public static $rightname = "plugin_activity";

    public static function getTypeName($nb = 0)
    {

        return _n('Activity', 'Activity', $nb, 'activity');
    }

    public function showForm($ID, $options = [])
    {
        $this->getfromDB($ID);
        if (!$this->canView()) {
            return false;
        }

        $dbu = new DbUtils();
        $dataConfig = $dbu->getAllDataFromTable($this->getTable());
        $used_entities = array_column($dataConfig, 'entities_id');

        ob_start();
        Dropdown::show('Entity', ['name' => 'entities_id', 'used' => $used_entities]);
        $entity_dropdown_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('is_recursive');
        $is_recursive_html = ob_get_clean();

        $entries = [];
        foreach ($dataConfig as $field) {
            $entries[] = [
                'id'           => $field['id'],
                'entity'       => Dropdown::getDropdownName('glpi_entities', $field['entities_id']),
                'is_recursive' => Dropdown::getYesNo($field['is_recursive']),
                'name'         => $field['name'],
            ];
        }

        TemplateRenderer::getInstance()->display('@activity/config_form.html.twig', [
            'form_url'            => Toolbox::getItemTypeFormURL(Config::class),
            'entity_dropdown_html' => $entity_dropdown_html,
            'is_recursive_html'   => $is_recursive_html,
            'entries'             => $entries,
            'canedit'             => $this->canCreate(),
        ]);

        return true;
    }


    /**
     * getConfigFromDB : get all configs in the database
     *
     * @param type $ID : configs_id
     * @return array
     *@global type $DB
     */
    public static function getConfigFromDB($entities_id)
    {

        $ancestor_entities_id = getAncestorsOf(Entity::getTable(), $entities_id);
        if (!empty($ancestor_entities_id)) {
            $restrict = [
                "OR" => [
                    "entities_id" => $entities_id,
                    "AND" => [
                        "entities_id" => $ancestor_entities_id,
                        "is_recursive" => 1,
                    ],
                ],

            ];
        } else {
            $restrict = [  "entities_id" => $entities_id];
        }

        $dbu = new DbUtils();
        $dataConfig = $dbu->getAllDataFromTable("glpi_plugin_activity_configs", $restrict);

        return $dataConfig;
    }
}
