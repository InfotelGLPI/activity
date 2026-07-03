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
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class ProjectTask extends CommonDBTM
{
    public $dohistory = false;

    public static $rightname = "plugin_activity";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     * */
    public static function getTypeName($nb = 0)
    {
        return \Project::getTypeName($nb);
    }

    public static function taskUpdate(\ProjectTask $item)
    {

        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        self::setProjectTask($item);

        //      if (isset($item->input['plan'])) {
        //         self::manageBeginAndEndPlanDates($item->input);
        //      }
    }

    public static function taskAdd(\ProjectTask $item)
    {

        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        self::setProjectTask($item);
    }

    public static function setProjectTask(\ProjectTask $item)
    {
        if (self::canCreate()) {
            $projecttask = new self();
            if (isset($item->input['id'])
                && isset($item->input['is_oncra'])) {
                $projecttask->getFromDBForTask($item->input['id']);

                if (!empty($projecttask->fields)) {
                    $projecttask->update(['id'             => $projecttask->fields['id'],
                        'is_oncra'       => $item->input['is_oncra'],
                        'projecttasks_id' => $item->input['id']]);
                } else {
                    $projecttask->add(['is_oncra'        => $item->input['is_oncra'],
                        'projecttasks_id' => $item->getID()]);
                }
            } else {
                $is_cra_default = 0;
                $opt = new Option();
                $opt->getFromDB(1);
                if ($opt) {
                    $is_cra_default = $opt->fields['is_cra_default'];
                }
                $projecttask->add(['is_oncra'        => $item->input['is_oncra'] ?? $is_cra_default,
                    'projecttasks_id' => $item->getID()]);
            }
        }
    }


    public function getFromDBForTask($projecttasks_id)
    {
        $dbu = new DbUtils();
        $data = $dbu->getAllDataFromTable($this->getTable(), [$dbu->getForeignKeyFieldForTable('glpi_projecttasks') => $projecttasks_id]);

        $this->fields = array_shift($data);
    }

    /**
     * post_item_form for ProjectTask
     * @param $params
     * @return void
     */
    public static function addField($params)
    {
        $item = $params['item'];

        $opt = new Option();
        $opt->getFromDB(1);

        $projecttask = new self();

        $is_cra_default = $opt->getIsCraDefaultProject();

        if ($item->getID()) {
            $projecttask->getFromDBForTask($item->getID());
            $is_cra_default = $projecttask->fields['is_oncra'] ?? $is_cra_default;
        }

        ob_start();
        Dropdown::showYesNo('is_oncra', $is_cra_default, -1, ['value' => 1]);
        $is_oncra_dropdown_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@activity/projecttask_post_form.html.twig', [
            'is_oncra_dropdown_html' => $is_oncra_dropdown_html,
        ]);
    }

    public static function queryProjectTask($criteria)
    {
        $begin = $criteria["begin"];
        $end   = $criteria["end"];
        $who   = $criteria["users_id"];

        $where = [
            'glpi_plugin_activity_projecttasks.is_oncra' => 1,
            'glpi_projecttasks.percent_done'             => ['<', 100],
            ['glpi_projecttasks.plan_end_date'   => ['>', $begin]],
            ['glpi_projecttasks.plan_start_date' => ['<', $end]],
            'OR' => [
                ['glpi_projectstates.is_finished' => 0],
                ['glpi_projectstates.is_finished' => null],
            ],
        ];

        if ($who > 0) {
            $where['glpi_projecttaskteams.itemtype']  = 'User';
            $where['glpi_projecttaskteams.items_id']  = $who;
        } else {
            $where['glpi_projecttaskteams.itemtype'] = 'User';
            $where['glpi_projecttaskteams.items_id'] = ['IN', new QuerySubQuery([
                'SELECT DISTINCT' => 'glpi_profiles_users.users_id',
                'FROM'            => 'glpi_profiles',
                'LEFT JOIN'       => [
                    'glpi_profiles_users' => [
                        'ON' => ['glpi_profiles' => 'id', 'glpi_profiles_users' => 'profiles_id'],
                    ],
                ],
                'WHERE' => array_merge(
                    ['glpi_profiles.interface' => 'central'],
                    getEntitiesRestrictCriteria('glpi_profiles_users', '', $_SESSION["glpiactive_entity"], true)
                ),
            ])];
        }

        return [
            'SELECT'     => ['glpi_projecttasks.*'],
            'FROM'       => 'glpi_projecttaskteams',
            'INNER JOIN' => [
                'glpi_projecttasks' => [
                    'ON' => ['glpi_projecttasks' => 'id', 'glpi_projecttaskteams' => 'projecttasks_id'],
                ],
            ],
            'LEFT JOIN'  => [
                'glpi_projectstates' => [
                    'ON' => ['glpi_projecttasks' => 'projectstates_id', 'glpi_projectstates' => 'id'],
                ],
                'glpi_plugin_activity_projecttasks' => [
                    'ON' => ['glpi_plugin_activity_projecttasks' => 'projecttasks_id', 'glpi_projecttasks' => 'id'],
                ],
            ],
            'WHERE' => $where,
            'ORDER' => 'glpi_projecttasks.plan_start_date',
        ];
    }

}
