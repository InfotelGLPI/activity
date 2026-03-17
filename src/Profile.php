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

namespace GlpiPlugin\Activity;

use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Html;
use ProfileRight;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Profile extends \Profile
{
    public static $rightname = "profile";

    public static function getTypeName($nb = 0)
    {
        return _n('Right management', 'Rights management', $nb, 'activity');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile'
            && $item->getField('interface') != 'helpdesk'
        ) {
            return self::createTabEntry(_n('Activity', 'Activities', 2, 'activity'));
        }
        return '';
    }

    /**
     * @return string
     */
    public static function getIcon()//self::createTabEntry(
    {
        return "ti ti-calendar-event";
    }


    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if (!$item instanceof \Profile || !self::canView()) {
            return false;
        }

        $profile = new \Profile();
        $profile->getFromDB($item->getID());

        $rights = self::getAllRights(true);

        $twig = TemplateRenderer::getInstance();
        $twig->display('@activity/profile.html.twig', [
            'id'      => $item->getID(),
            'profile' => $profile,
            'title'   => self::getTypeName(Session::getPluralNumber()),
            'rights'  => $rights,
        ]);

        return true;
    }


    public static function getAllRights($all = false)
    {
        $rights = [
           ['itemtype' => PlanningExternalEvent::class,
                 'label'    => _n('Activity', 'Activities', 1, 'activity'),
                 'field'    => 'plugin_activity'
           ]
        ];

        if ($all) {
            $rights[] = ['itemtype' => Holiday::class,
                              'label'    => __('Create a holiday request', 'activity'),
                              'field'    => 'plugin_activity_can_requestholiday',
                'rights' => [
                    READ  => __s('Read'),
                ],];

            $rights[] = ['itemtype' => Holiday::class,
                              'label'    => _n('Validate holiday', 'Validate holidays', 2, 'activity'),
                              'field'    => 'plugin_activity_can_validate',
                'rights' => [
                    READ  => __s('Read'),
                ],];

            $rights[] = ['itemtype' => PlanningExternalEvent::class,
                              'label'    => __('Statistics and reports', 'activity'),
                              'field'    => 'plugin_activity_statistics',
                'rights' => [
                    READ  => __s('Read'),
                ],];

            $rights[] = ['itemtype' => PlanningExternalEvent::class,
                              'label'    => __('Display activities of all', 'activity'),
                              'field'    => 'plugin_activity_all_users',
                'rights' => [
                    READ  => __s('Read'),
                ],];
        }

        return $rights;
    }

    /**
     * Init profiles
     *
     * */
    public static function translateARight($old_right)
    {
        switch ($old_right) {
            case '':
                return 0;
            case 'r':
                return READ;
            case 'w':
                return ALLSTANDARDRIGHT;
            case '0':
            case '1':
                return $old_right;

            default:
                return 0;
        }
    }

    /**
     * @since 0.85
     * Migration rights from old system to the new one for one profile
     * @param $profiles_id
     * @return bool
     */
    public static function migrateOneProfile()
    {
        global $DB;
        //Cannot launch migration if there's nothing to migrate...
        if (!$DB->tableExists('glpi_plugin_activity_profiles')) {
            return true;
        }
        $dbu = new DbUtils();
        $datas = $dbu->getAllDataFromTable('glpi_plugin_activity_profiles');

        foreach ($datas as $profile_data) {
            $matching       = ['activity'           => 'plugin_activity',
                                    'all_users'          => 'plugin_activity_all_users',
                                    'statistics'         => 'plugin_activity_statistics',
                                    'can_requestholiday' => 'plugin_activity_can_requestholiday',
                                    'can_validate'       => 'plugin_activity_can_validate'];
            // Search existing rights
            $used = [];
            $existingRights = $dbu->getAllDataFromTable('glpi_profilerights', ["profiles_id" => $profile_data['profiles_id']]);
            foreach ($existingRights as $right) {
                $used[$right['profiles_id']][$right['name']] = $right['rights'];
            }

            // Add or update rights
            foreach ($matching as $old => $new) {
                if (isset($used[$profile_data['profiles_id']][$new])) {
                    $DB->update('glpi_profilerights', ['rights' => self::translateARight($profile_data[$old])], [
                        'name'        => $new,
                        'profiles_id' => $profile_data['profiles_id']
                    ]);
                } else {
                    $DB->add('glpi_profilerights', ['rights' => self::translateARight($profile_data[$old])], [
                        'name'        => $new,
                        'profiles_id' => $profile_data['profiles_id']
                    ]);
                }
            }
        }
    }

    /**
     * Initialize profiles, and migrate it necessary
     */
    public static function initProfile()
    {
        global $DB;
        $profile = new self();
        $dbu     = new DbUtils();

        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights(true) as $data) {
            if ($dbu->countElementsInTable("glpi_profilerights", ["name" => $data['field']]) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        // Migration old rights in new ones
        self::migrateOneProfile();

        $it = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
                'name' => ['LIKE', '%plugin_activity%']
            ]
        ]);
        foreach ($it as $prof) {
            if (isset($_SESSION['glpiactiveprofile'])) {
                $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
            }
        }
    }

    public static function createFirstAccess($profiles_id)
    {
        self::addDefaultProfileInfos($profiles_id, ['plugin_activity'                    => ALLSTANDARDRIGHT,
                                                         'plugin_activity_statistics'         => 1,
                                                         'plugin_activity_all_users'          => 1,
                                                         'plugin_activity_can_requestholiday' => 1,
                                                         'plugin_activity_can_validate'       => 1], true);
    }

    public static function removeRightsFromSession()
    {
        foreach (self::getAllRights(true) as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }

    public static function removeRightsFromDB()
    {
        $plugprof = new ProfileRight();
        foreach (self::getAllRights(true) as $right) {
            $plugprof->deleteByCriteria(['name' => $right['field']]);
        }
    }

    /**
     * @param $profile
     * */
    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        global $DB;

        $dbu          = new DbUtils();
        $profileRight = new ProfileRight();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable('glpi_profilerights', ["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable('glpi_profilerights', ["profiles_id" => $profiles_id, "name" => $right])) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }
}
