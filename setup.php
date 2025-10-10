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

define('PLUGIN_ACTIVITY_VERSION', '3.2.3');

global $CFG_GLPI;

use Glpi\Plugin\Hooks;
use GlpiPlugin\Activity\Dashboard;
use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\HolidayValidation;
use GlpiPlugin\Activity\Menu;
use GlpiPlugin\Activity\Option;
use GlpiPlugin\Activity\PlanningExternalEvent;
use GlpiPlugin\Activity\Preference;
use GlpiPlugin\Activity\Profile;
use GlpiPlugin\Activity\ProjectTask;
use GlpiPlugin\Activity\PublicHoliday;
use GlpiPlugin\Activity\Snapshot;
use GlpiPlugin\Activity\Ticket;
use GlpiPlugin\Activity\TicketTask;

if (!defined("PLUGIN_ACTIVITY_DIR")) {
    define("PLUGIN_ACTIVITY_DIR", Plugin::getPhpDir("activity"));
    $root = $CFG_GLPI['root_doc'] . '/plugins/activity';
    define("PLUGIN_ACTIVITY_WEBDIR", $root);
}

include_once PLUGIN_ACTIVITY_DIR . "/vendor/autoload.php";

// Init the hooks of the plugins -Needed
function plugin_init_activity()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['activity'] = true;
    $PLUGIN_HOOKS['change_profile']['activity'] = [Profile::class, 'initProfile'];
    if (isset($_SESSION["glpiactiveprofile"]["interface"])
        && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['activity'] = ['activity.css'];
        //        $PLUGIN_HOOKS['javascript']['activity'][]   = PLUGIN_ACTIVITY_WEBDIR . '/lib/sdashboard/lib/flotr2/flotr2.js';
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['activity'] = ['/lib/jquery/js/jquery.ui.touch-punch.min.js'];
    }

    // Lateral menu
    if (Session::haveRight("plugin_activity", UPDATE)
        || Session::haveRight("plugin_activity_can_requestholiday", 1)
        || Session::haveRight("plugin_activity_can_validate", 1)
        || Session::haveRight("plugin_activity_all_users", 1)) {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['activity'] = [
            'scripts/scripts-activitydate.js',
            'scripts/scripts-activityholidays.js',
            'scripts/activity_load_scripts.js.php',
        ];
        //        $PLUGIN_HOOKS['javascript']['activity']     = [PLUGIN_ACTIVITY_WEBDIR . "/scripts/scripts-activitydate.js",
        //                                                       PLUGIN_ACTIVITY_WEBDIR . "/scripts/scripts-activityholidays.js",
        //                                                       PLUGIN_ACTIVITY_WEBDIR . "/scripts/activity_load_scripts.js.php"];
    }

    //   if (Session::haveRight("plugin_activity_statistics", 1)) {
    //      /* Show Stats in standard stats page */
    //      if (class_exists(Stats::class)) {
    //         $common = new Stats();
    //         $stats  = $common->getAllStats();
    //
    //         if ($stats !== false) {
    //            foreach ($stats as $stat) {
    //               foreach ($stat['funct'] as $func) {
    //                  $PLUGIN_HOOKS['stats']['activity'] = ['front/stats.php?stat_id=' . $func['id'] => $func['title']];
    //               }
    //            }
    //         }
    //      }
    //   }
    $PLUGIN_HOOKS['post_init']['activity'] = 'plugin_activity_postinit';

    if (Plugin::isPluginActive("activity")) {
        Plugin::registerClass(Profile::class, ['addtabon' => 'Profile']);
        Plugin::registerClass(PublicHoliday::class, ['planning_types' => true]);
        Plugin::registerClass(TicketTask::class, ['planning_types' => true]);

        $opt = new Option();
        $opt->getFromDB(1);

        if (Plugin::isPluginActive("manageentities")
            || $opt->fields['use_timerepartition']) {
            unset($CFG_GLPI['planning_types'][3]);
            unset($_SESSION['glpi_plannings']['filters']['TicketTask']);
        }

        Plugin::registerClass(Holiday::class, [
            'planning_types' => true,
            'notificationtemplates_types' => true,
        ]);
        Plugin::registerClass(HolidayValidation::class, []);

        if (Session::getLoginUserID()) {
            if (Session::haveRight("plugin_activity", READ)
                || Session::haveRight("plugin_activity_can_requestholiday", 1)
                || Session::haveRight("plugin_activity_can_validate", 1)) {
                Plugin::registerClass(
                    Preference::class,
                    ['addtabon' => 'Preference']
                );

                if (Session::haveRight('plugin_activity', READ)) {
                    $PLUGIN_HOOKS["menu_toadd"]['activity'] = ['tools' => Menu::class];
                    $PLUGIN_HOOKS['helpdesk_menu_entry']['activity'] = PLUGIN_ACTIVITY_WEBDIR . '/front/menu.php';
                    $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['activity'] = Holiday::getIcon();
                }

                $PLUGIN_HOOKS['redirect_page']['activity'] = PLUGIN_ACTIVITY_WEBDIR . '/front/holiday.form.php';
            }
            $PLUGIN_HOOKS['mydashboard']['activity'] = [Dashboard::class];

            if (Session::haveRight("plugin_activity", UPDATE) && class_exists(Profile::class)) {
                $PLUGIN_HOOKS['config_page']['activity'] = 'front/config.form.php';
                $PLUGIN_HOOKS['use_massive_action']['activity'] = false;
            }

            $PLUGIN_HOOKS['pre_item_add']['activity'] = [
                'Ticket_User' => [
                    Ticket::class,
                    'afterAddUser',
                ],
            ];

            $PLUGIN_HOOKS['item_add']['activity'] = [
                'TicketTask' => [
                    TicketTask::class,
                    'taskAdd',
                ],
                'PlanningExternalEvent' => [
                    PlanningExternalEvent::class,
                    'activityAdd',
                ],
            ];

            $PLUGIN_HOOKS['pre_item_update']['activity'] = [
                'TicketTask' => [
                    TicketTask::class,
                    'taskUpdate',
                ],
                'PlanningExternalEvent' => [
                    PlanningExternalEvent::class,
                    'activityUpdate',
                ],
            ];

            $PLUGIN_HOOKS['post_prepareadd']['activity'] = [
                'PlanningExternalEvent' => [
                    PlanningExternalEvent::class,
                    'prepareInputToAddWithPluginOptions',
                ],
            ];

            $PLUGIN_HOOKS['pre_item_purge']['activity'] = [
                'Document' => [
                    Snapshot::class,
                    'purgeSnapshots',
                ],
            ];

            if ($opt->getUseProject() && strpos($_SERVER['REQUEST_URI'], "projecttask")) {
                $PLUGIN_HOOKS['item_add']['activity'] = [
                    'ProjectTask' => [
                        ProjectTask::class,
                        'taskAdd',
                    ],
                ];

                $PLUGIN_HOOKS['pre_item_update']['activity'] = [
                    'ProjectTask' => [
                        ProjectTask::class,
                        'taskUpdate',
                    ],
                ];
            }
        }

        $PLUGIN_HOOKS['post_item_form']['activity'] = 'plugin_activity_post_item_form';
    }
    //Planning hook
    $PLUGIN_HOOKS['display_planning']['activity'] = [
        PublicHoliday::class => "displayPlanningItem",
        Holiday::class => "displayPlanningItem",
    ];
    $PLUGIN_HOOKS['planning_populate']['activity'] = [
        PublicHoliday::class => "populatePlanning",
        Holiday::class => "populatePlanning",
    ];
}

// Get the name and the version of the plugin - Needed
function plugin_version_activity()
{
    return [
        'name' => _n('Activity', 'Activities', 2, 'activity'),
        'version' => PLUGIN_ACTIVITY_VERSION,
        'author'       => "<a href='https://blogglpi.infotel.com'>Infotel</a>, Xavier CAILLAUD",
        'license' => 'GPLv2+',
        'homepage' => '',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ],
    ];
}

/**
 * @return bool
 */
function plugin_activity_check_prerequisites()
{
    if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
        echo "Run composer install --no-dev in the plugin directory<br>";
        return false;
    }

    return true;
}
