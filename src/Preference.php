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
use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Group_User;
use Html;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * class plugin_activity_preference
 * Load and store the preference configuration from the database
 */
class Preference extends CommonDBTM
{
    public static $rightname = "plugin_activity";

    /**
     * Get Tab Name used for itemtype
     *
     * NB : Only called for existing object
     *      Must check right on what will be displayed + template
     *
     * @since 0.83
     *
     * @param CommonGLPI $item         Item on which the tab need to be displayed
     * @param boolean    $withtemplate is a template object ? (default 0)
     *
     *  @return string tab name
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Preference') {
            return self::createTabEntry(_n('Activity', 'Activities', 1, 'activity'));
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-calendar-event";
    }


    /**
     * show Tab content
     *
     * @since 0.83
     *
     * @param CommonGLPI $item         Item on which the tab need to be displayed
     * @param integer    $tabnum       tab number (default 1)
     * @param boolean    $withtemplate is a template object ? (default 0)
     *
     * @return boolean
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        $pref = new Preference();
        $pref->showPreferenceForm(Session::getLoginUserID());
        return true;
    }

    /**
     * @param $user_id
     *
     * @return void
     */
    public function showPreferenceForm($user_id)
    {
        $use_groupmanager = 0;
        $opt = new Option();
        $opt->getFromDB(1);
        if ($opt) {
            $use_groupmanager = $opt->fields['use_groupmanager'];
        }

        if ($use_groupmanager == 0) {
            $raw_managers = getAllDataFromTable('glpi_plugin_activity_preferences', ['users_id' => $user_id]);
            $managers = array_map(static function (array $row): array {
                return [
                    'id'       => $row['id'],
                    'username' => getUserName($row['users_id_validate']),
                    'users_id_validate' => $row['users_id_validate'],
                ];
            }, $raw_managers);
        } else {
            $groupusers = Group_User::getUserGroups($user_id);
            $groups = array_column($groupusers, 'id');

            $raw_managers = getAllDataFromTable('glpi_groups_users', [
                'groups_id' => $groups,
                'is_manager' => 1,
                'NOT' => ['users_id' => $user_id],
            ]);
            $managers = array_map(static function (array $row): array {
                return [
                    'id'       => $row['id'],
                    'username' => getUserName($row['users_id']),
                ];
            }, $raw_managers);
        }

        TemplateRenderer::getInstance()->display('@activity/preference_form.html.twig', [
            'form_url'         => Toolbox::getItemTypeFormURL(Preference::class),
            'use_groupmanager' => $use_groupmanager,
            'managers'         => $managers,
        ]);

        $this->showAddManagerView($managers, $use_groupmanager);
    }

    /**
     * @param array $managers     Already-normalized manager rows (with 'users_id_validate' key when use_groupmanager==0)
     * @param int   $use_groupmanager
     *
     * @return void
     */
    public function showAddManagerView(array $managers = [], int $use_groupmanager = -1)
    {
        if ($use_groupmanager === -1) {
            // Fallback: called standalone without the pre-computed flag
            $use_groupmanager = 0;
            $opt = new Option();
            $opt->getFromDB(1);
            if ($opt) {
                $use_groupmanager = $opt->fields['use_groupmanager'];
            }
        }

        if ($use_groupmanager != 0) {
            return;
        }

        $used = [Session::getLoginUserID()];
        foreach ($managers as $manager) {
            if (isset($manager['users_id_validate'])) {
                $used[] = $manager['users_id_validate'];
            }
        }

        ob_start();
        User::dropdown([
            'name'   => 'users_id_validate',
            'entity' => $_SESSION['glpiactiveentities'],
            'right'  => 'all',
            'used'   => $used,
        ]);
        $user_dropdown_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@activity/preference_add_manager.html.twig', [
            'form_url'          => Toolbox::getItemTypeFormURL(Preference::class),
            'use_groupmanager'  => $use_groupmanager,
            'users_id'          => Session::getLoginUserID(),
            'user_dropdown_html' => $user_dropdown_html,
        ]);
    }
}
