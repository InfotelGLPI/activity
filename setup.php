<?php

/**
 * -------------------------------------------------------------------------
 * activity plugin for GLPI
 * Copyright (C) 2019-2026 by the activity Development Team.
 *
 * https://github.com/InfotelGLPI/activity
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of activity.
 *
 * activity is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * activity is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with activity. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

define('PLUGIN_ACTIVITY_VERSION', '3.2.16');

global $CFG_GLPI;

use Glpi\Http\SessionManager;
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
    $root = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/activity';
    define("PLUGIN_ACTIVITY_WEBDIR", $root);
}

include_once PLUGIN_ACTIVITY_DIR . "/vendor/autoload.php";

// Init the hooks of the plugins -Needed
function plugin_init_activity()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['activity'] = [Profile::class, 'initProfile'];

    // The iCal feed of front/planning.php authenticates with a personal token, exactly
    // like the core /front/planning.php?genical route that SessionManager hardcodes as
    // stateless. Without this declaration the firewall refuses the anonymous request of
    // a calendar client - which never sends a cookie - before the controller runs, so
    // the feed could never work.
    //
    // Unlike the core rule, a plugin pattern is matched against the path only and cannot
    // test for the genical parameter, so it covers the whole controller. That is safe on
    // both counts. Functionally, the SessionStart listener runs at priority 130 while
    // plugins are initialised at 110, so the pattern is not yet registered when the
    // session is started: the regular planning page keeps its session exactly as before.
    // Security-wise, the only listener that does see the pattern is FirewallStrategyListener,
    // and every other branch of the controller opens with its own Session::checkRight(),
    // so dropping the firewall leaves them fail-closed.
    SessionManager::registerPluginStatelessPath(
        'activity',
        '#^/front/planning\.php$#',
    );


    $activityScripts = [
        'scripts/scripts-activitydate.js',
        'scripts/scripts-activityholidays.js',
        'scripts/activity_load_scripts.js',
    ];

    if (isset($_SESSION["glpiactiveprofile"]["interface"])
        && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['activity'] = ['activity.css'];
    }

    if (Session::haveRight("plugin_activity", UPDATE)
        || Session::haveRight("plugin_activity_can_requestholiday", 1)
        || Session::haveRight("plugin_activity_can_validate", 1)
        || Session::haveRight("plugin_activity_all_users", 1)) {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['activity'] = $activityScripts;
    }

    $PLUGIN_HOOKS[Hooks::POST_INIT]['activity'] = 'plugin_activity_postinit';

    if (Plugin::isPluginActive("activity")) {
        Plugin::registerClass(Profile::class, ['addtabon' => 'Profile']);
        Plugin::registerClass(PublicHoliday::class, ['planning_types' => true]);
        Plugin::registerClass(TicketTask::class, ['planning_types' => true]);

        // getFromDB() was called without testing its return value and fields was read straight
        // after: when the configuration row is missing - deleted by hand, or plugin activated
        // before the install script finished - CommonDBTM leaves fields empty and PHP emits an
        // "Undefined array key" warning. This block runs on every single request as long as the
        // plugin is active, so the warning was repeated on every page of the instance: visible
        // in the interface in debug mode, and drowning the real alerts in the error log
        // otherwise. Preference::showPreferenceForm() already applies this practice.
        $opt = new Option();
        $use_timerepartition = $opt->getFromDB(1)
            ? (bool) ($opt->fields['use_timerepartition'] ?? false)
            : false;

        if (Plugin::isPluginActive("manageentities")
            || $use_timerepartition) {
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
                    ['addtabon' => 'Preference'],
                );

                if (Session::haveRight('plugin_activity', READ)) {
                    $PLUGIN_HOOKS[Hooks::MENU_TOADD]['activity'] = ['tools' => Menu::class];
                    $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY]['activity'] = PLUGIN_ACTIVITY_WEBDIR . '/front/menu.php';
                    $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY_ICON]['activity'] = Holiday::getIcon();

                    // The dashboard widgets read activity data, so they belong behind the
                    // same right as the menu. Registered outside this block they were
                    // offered to every authenticated user of the central interface,
                    // including profiles holding no activity right at all.
                    $PLUGIN_HOOKS['mydashboard']['activity'] = [Dashboard::class];
                }

                $PLUGIN_HOOKS['redirect_page']['activity'] = PLUGIN_ACTIVITY_WEBDIR . '/front/holiday.form.php';
            }

            if (Session::haveRight("plugin_activity", UPDATE) && class_exists(Profile::class)) {
                $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['activity'] = 'front/config.form.php';
                $PLUGIN_HOOKS[Hooks::USE_MASSIVE_ACTION]['activity'] = false;
            }

            $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['activity'] = [
                'Ticket_User' => [
                    Ticket::class,
                    'afterAddUser',
                ],
            ];

            $PLUGIN_HOOKS[Hooks::ITEM_ADD]['activity'] = [
                'TicketTask' => [
                    TicketTask::class,
                    'taskAdd',
                ],
                'PlanningExternalEvent' => [
                    PlanningExternalEvent::class,
                    'activityAdd',
                ],
            ];

            $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['activity'] = [
                'TicketTask' => [
                    TicketTask::class,
                    'taskUpdate',
                ],
                'PlanningExternalEvent' => [
                    PlanningExternalEvent::class,
                    'activityUpdate',
                ],
            ];

            $PLUGIN_HOOKS[Hooks::POST_PREPAREADD]['activity'] = [
                'PlanningExternalEvent' => [
                    PlanningExternalEvent::class,
                    'prepareInputToAddWithPluginOptions',
                ],
            ];

            $PLUGIN_HOOKS[Hooks::PRE_ITEM_PURGE]['activity'] = [
                'Document' => [
                    Snapshot::class,
                    'purgeSnapshots',
                ],
            ];

            if ($opt->getUseProject() && strpos($_SERVER['REQUEST_URI'], "projecttask")) {
                $PLUGIN_HOOKS[Hooks::ITEM_ADD]['activity'] = [
                    'ProjectTask' => [
                        ProjectTask::class,
                        'taskAdd',
                    ],
                ];

                $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['activity'] = [
                    'ProjectTask' => [
                        ProjectTask::class,
                        'taskUpdate',
                    ],
                ];
            }
        }

        $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['activity'] = 'plugin_activity_post_item_form';
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
        'license' => 'GPLv3+',
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
