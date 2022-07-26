<?php

/*
 -------------------------------------------------------------------------
 Activity plugin for GLPI
 Copyright (C) 2019-2022 by the Activity Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Activity.

 Activity is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Activity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Activity. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * class plugin_activity_preference
 * Load and store the preference configuration from the database
 */
class PluginActivityPreference extends CommonDBTM
{

    static $rightname = "plugin_activity";

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
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType()=='Preference') {
            return _n('Activity', 'Activities', 1, 'activity');
        }
        return '';
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
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;
        $pref = new PluginActivityPreference();
        $pref->showPreferenceForm(Session::getLoginUserID());
        return true;
    }

   /**
    * @param $user_id
    *
    * @return void
    */
    function showPreferenceForm($user_id)
    {

        $use_groupmanager = 0;
        $opt = new PluginActivityOption();
        $opt->getFromDB(1);
        if ($opt) {
            $use_groupmanager = $opt->fields['use_groupmanager'];
        }

        if ($use_groupmanager == 0) {
           // Liste des managers déclarés
            $restrict = ["users_id" => $user_id];
            $dbu = new DbUtils();
            $managers = $dbu->getAllDataFromTable('glpi_plugin_activity_preferences', $restrict);

            echo "<form method='post' action='".Toolbox::getItemTypeFormURL("PluginActivityPreference")."'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1' >";
            echo "<th colspan='2'>".__('List of my managers', 'activity')."</th>";
            echo "</tr>";
            if (sizeof($managers) == 0) {
                echo "<tr class='tab_bg_1' id='no_manager_left'>";
                echo "<td colspan='2'>".__('You have not declared any manager yet.', 'activity')."</td>";
                echo "</tr>";
            } else {
                echo "<tr class='tab_bg_1'>";
                echo "<th>"._n('User', 'Users', 1)."</th>";
                echo "<th>"._n('Action', 'Actions', 1)."</th>";
                echo "</tr>";
                foreach ($managers as $manager) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td>".$dbu->getUserName($manager['users_id_validate'])."</td>";
                    echo "<td>";
                    echo Html::hidden('id', ['value' => $manager['id']]);
                    echo Html::submit(_sx('button', 'Delete permanently'),
                                      ['name' => 'delete', 'class' => 'btn btn-primary']);
                    echo "</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            Html::closeForm();
        } else {
            $groupusers = Group_User::getUserGroups($user_id);
            $groups = [];
            foreach ($groupusers as $groupuser) {
                $groups[] = $groupuser["id"];
            }

            $dbu = new DbUtils();
            $restrict = ["groups_id" => $groups,
                   "is_manager" => 1,
                    "NOT" => ["users_id"  => $user_id]];
            $managers = $dbu->getAllDataFromTable('glpi_groups_users', $restrict);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1' >";
            echo "<th colspan='2'>".__('List of my managers', 'activity')."</th>";
            echo "</tr>";

            if (sizeof($managers) <= 0) {
                echo "<tr class='tab_bg_1' id='no_manager_left'>";
                echo "<td colspan='2'>".__('There is no manager for your groups', 'activity')."</td>";
                echo "</tr>";
            } else {
                echo "<tr class='tab_bg_1'>";
                echo "<th>"._n('User', 'Users', count($managers))."</th>";
                echo "</tr>";
                foreach ($managers as $manager) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td>".$dbu->getUserName($manager['users_id'])."</td>";
                    echo "</tr>";
                }
            }

            echo "</table>";
        }

         $this->showAddManagerView($managers);
    }

   /**
    * @param $managers
    *
    * @return void
    */
    public function showAddManagerView($managers)
    {

        $use_groupmanager = 0;
        $opt = new PluginActivityOption();
        $opt->getFromDB(1);
        if ($opt) {
            $use_groupmanager = $opt->fields['use_groupmanager'];
        }

        if ($use_groupmanager == 0) {
            echo "<br/>";
            echo "<form method='post' action='".Toolbox::getItemTypeFormURL("PluginActivityPreference")."'>";
            echo "<table class='tab_cadre_fixe'> ";
            echo "<tr class='tab_bg_1' >";
            echo "<th colspan='2'>".__('Add a manager', 'activity')."</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1' >";
            echo "<td>";
            $used = [Session::getLoginUserID()];

            foreach ($managers as $manager) {
                $used[] = $manager['users_id_validate'];
            }

            $rand = User::dropdown([
               'name' => 'users_id_validate',
               'entity' => $_SESSION['glpiactiveentities'],
               'right' => 'all',
               'used' => $used]);

            echo "</td>";
            echo "<td>";

            echo Html::hidden('users_id', ['value' => Session::getLoginUserID()]);
            echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
            echo "</td>";
            echo "</tr>";

            echo "</table>";
            Html::closeForm();
        }
    }
}
