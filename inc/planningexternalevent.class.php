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

class PluginActivityActions
{
    const ADD_ACTIVITY = 'add_activity';
    const LIST_ACTIVITIES = 'list_activities';
    const HOLIDAY_REQUEST = 'holiday_request';
    const LIST_HOLIDAYS = 'list_holidays';
    const APPROVE_HOLIDAYS = 'validate_holidays';
    const CRA = 'cra';
    const HOLIDAY_COUNT = 'holiday_count';
    const MANAGER = 'manager';
}

class PluginActivityPlanningExternalEvent extends CommonDBTM
{

    // Event color
    static $HOLIDAY_COLOR = "#7DAEDF";
    static $ACTIVITY_COLOR = "#84BE6A";
    static $MANAGEENTITIES_COLOR = "#08A5AC";
    static $TICKET_COLOR = "#E85F0C";

    // Calendar views
    static $DAY = 'agendaDay';
    static $WEEK = 'agendaWeek';
    static $MONTH = 'month';

    // Event tags
    static $TICKET_TAG = 'ticket';
    static $MANAGEENTITIES_TAG = 'manageentities';
    static $ACTIVITY_TAG = 'activity';
    static $HOLIDAY_TAG = 'holiday';

    var $dohistory = false;

    static $rightname = "plugin_activity";


    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     * */
    static function getTypeName($nb = 0)
    {
        return PlanningExternalEvent::getTypeName($nb);
    }

    static function cleanForItem(CommonDBTM $item)
    {
        $temp = new self();
        $temp->deleteByCriteria(
            ['planningexternalevents_id' => $item->getField('id')]
        );
    }

    /**
     * Display menu
     */
    static function menu($class, $listActions, $widget = false)
    {
        global $CFG_GLPI;

        $i = 0;

        $allactions = [];
        foreach ($listActions as $actions) {
            if ($actions['rights']) {
                $allactions[] = $actions;
            }
        }

        $number = count($allactions);
        $return = "";
        if ($number > 0) {
            $return .= Ajax::createIframeModalWindow(
                'holiday',
                PLUGIN_ACTIVITY_WEBDIR . "/front/holiday.form.php",
                [
                    'title' => __('Create a holiday request', 'activity'),
                    'reloadonclose' => false,
                    'width' => '1200',
                    'height' => '600',
                    'dialog_class' => 'modal-xl',
                    'display' => false,
                ]
            );

            $return .= "<div align='center'>";

            if (!$widget) {
                $return .= "<table class='tab_cadre_fixe activity_menu' style='width: 400px;'>";
            }

            if (!$widget) {
                $return .= "<tr><th colspan='4'>" . $class::getTypeName(2) . "</th></tr>";
            } else {
                $return .= "<div>";
            }

            foreach ($allactions as $action) {
                if (!$widget) {
                    if ((($i % 2) == 0) && ($number > 1)) {
                        $return .= "<tr class='twhite'>";
                    }
                    if ($number == 1) {
                        $return .= "<tr class='twhite'>";
                    }
                }
                if (!$widget) {
                    $return .= "<td class='center'>";
                } else {
                    $return .= "<div class='nb'>";
                }
                $return .= "<a href=\"" . $action['link'] . "\"";
                if (isset($action['onclick']) && !empty($action['onclick'])) {
                    $return .= $action['onclick'];
                }
                $return .= ">";
                $return .= "<i class='" . $action['img'] . "' style='font-size: 3.5em;' title='" . $action['label'] . "'></i>";
                $return .= "<br><br>" . $action['label'] . "</a>";

                if (!$widget) {
                    $return .= "</td>";
                } else {
                    $return .= "</div>";
                }
                $i++;
                if (!$widget) {
                    if (($i == $number) && (($number % 2) != 0) && ($number > 1)) {
                        $return .= "<td></td>";
                        $return .= "</tr>";
                    }
                }
            }
            if ($widget) {
                $return .= "</tr>";
            } else {
                $return .= "</div>";
            }

            if (!$widget) {
                $return .= "</table>";
            }
            $return .= "</div>";
        }

        return $return;
    }

    static function getActionsOn()
    {
        global $CFG_GLPI;

        // Array of action user can do :
        //    link     -> url of link
        //    img      -> ulr of the img to show
        //    label    -> label to show
        //    onclick  -> if set, set the onclick value of the href
        //    rights   -> if true, action shown

        $listActions = [
            PluginActivityActions::ADD_ACTIVITY => [
                'link' => $CFG_GLPI["root_doc"] . "/front/planning.php",
                'img' => "ti ti-calendar-plus",
                'label' => __('Add an activity', 'activity'),
                'rights' => Session::haveRight("plugin_activity", CREATE),
            ],
            PluginActivityActions::LIST_ACTIVITIES => [
                'link' => PLUGIN_ACTIVITY_WEBDIR . "/front/planningexternalevent.php",
                'img' => "ti ti-calendar",
                'label' => __('List of activities', 'activity'),
                'rights' => Session::haveRight("plugin_activity", READ),
            ],
            PluginActivityActions::CRA => [
                'link' => PLUGIN_ACTIVITY_WEBDIR . "/front/cra.php",
                'img' => "ti ti-calendar-stats",
                'label' => __('CRA', 'activity'),
                'rights' => Session::haveRight("plugin_activity_statistics", 1),
            ]
        ];

        return $listActions;
    }


    /**
     * post_item_form for PlanningExternalEvent
     * @param $params
     * @return void
     */
    static public function postItemForm($params)
    {
        $item = $params['item'];
        $self = new self();
        if ($item->getID() && !empty($item->getID())) {
            $self->getFromDBForTask($item->getID());
        } else {
            $self->getEmpty();
        }

        $is_cra_default = 0;
        $opt = new PluginActivityOption();
        $opt->getFromDB(1);
        if ($opt) {
            $is_cra_default = $opt->fields['is_cra_default'];
        }

        if (Session::haveRight("plugin_activity_statistics", 1)) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'>";
            echo "<td>";
            echo __('Use in CRA', 'activity');
            echo "</td>";
            echo "<td>";
//               echo "<div id='is_oncra_" . $item->getID() . "' class='ti ti-label'>";
            Dropdown::showYesNo(
                'is_oncra',
                (isset($self->fields['id']) && $self->fields['id']) > 0 ? $self->fields['is_oncra'] : $is_cra_default,
                -1,
                ['value' => 1]
            );
//               echo "</div>";
            echo "</td>";
            echo "<td colspan='2' width='250px'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            echo Html::hidden('is_oncra', ['value' => 1]);
        }

        if ($opt->fields['use_planningeventsubcategories']) {
            $value = (isset($self->fields['id']) && $self->fields['id']) > 0 ? $self->fields['planningeventsubcategories_id'] : null;
            if ($item->input && isset($item->input['planningeventsubcategories_id'])) {
                $value = $item->input['planningeventsubcategories_id'];
            }
            $rand = Dropdown::show(
                PluginActivityPlanningeventsubcategory::class,
                [
                    'name' => 'planningeventsubcategories_id',
                    'value' => $value,
                    'entity' => Session::getActiveEntity()
                ]
            );
            $field_id = "dropdown_planningeventsubcategories_id" . $rand;
            $label = __('Subcategory', 'activity');
            echo "<script>
                        // variables created in a window object since the js can be repeatedly created when the form is in a modal (front/planning.php)
                        if (!window.plugin_activity) {
                            window.plugin_activity = {};
                        }
                        // element containing all the visible HTML generated by Dropdown::show
                        plugin_activity.subCategoryDropdownField = document.getElementById('$field_id').parentElement;
                        // planningevent category select from the core
                        plugin_activity.categorySelect = document.getElementsByName('planningeventcategories_id');
                        if (plugin_activity.categorySelect.length) {
                            plugin_activity.categorySelect = plugin_activity.categorySelect[0];
                            // create a row for the subcategory dropdown
                            plugin_activity.newRow = document.createElement('tr');
                            plugin_activity.labelTd = document.createElement('td');
                            plugin_activity.labelTd.innerText = '$label';
                            plugin_activity.newRow.append(plugin_activity.labelTd);
                            plugin_activity.dropdownTd = document.createElement('td');
                            plugin_activity.dropdownTd.colSpan = 3;
                            plugin_activity.dropdownTd.append(plugin_activity.subCategoryDropdownField);
                            plugin_activity.newRow.append(plugin_activity.dropdownTd);
                            // insert it after the category row
                            plugin_activity.categoryRow = plugin_activity.categorySelect.parentElement.parentElement.parentElement;
                            plugin_activity.categoryRow.parentElement.insertBefore(plugin_activity.newRow, plugin_activity.categoryRow.nextElementSibling);
                        }
                        plugin_activity = {};
                    </script>";
        }
    }

    static function setActivity(PlanningExternalEvent $item)
    {
        if (self::canCreate()) {
            // don't do anything if coming from cleanDBRelations
            if ((isset($item->input['plan'])
                    && (isset($item->input['plan']['_duration']) || (isset($item->input['plan']['begin']) && isset($item->input['plan']['end']))))
            || (isset($item->input['begin']) && isset($item->input['end']))) {
                global $DB;
                $extevent = new PluginActivityPlanningExternalEvent();
                $is_exist = $extevent->getFromDBByCrit(['planningexternalevents_id' => $item->getID()]);
                $actiontime = '';
                $options = PluginActivityOption::getConfigFromDB()[1];
                if (isset($item->input['plan']['_duration'])) {
                    $actiontime = $item->input['plan']['_duration'];
                } else {
                    $report = new PluginActivityReport();
                    if (isset($item->input['begin']) && isset($item->input['begin'])) {
                        $actiontime = $report->getActionTimeForExternalEvent(
                            $item->input['begin'],
                            $item->input['end'],
                            '',
                            '',
                            ''
                        );
                    } elseif (isset($item->input['plan']['begin']) && isset($item->input['plan']['end'])) {
                        $actiontime = $report->getActionTimeForExternalEvent(
                            $item->input['plan']['begin'],
                            $item->input['plan']['end'],
                            '',
                            '',
                            ''
                        );
                    }
                }

                if (isset($item->input['id'])
                    && isset($extevent->fields['is_oncra'])) {
                    $extevent->getFromDBForTask($item->input['id']);

                    if (!empty($extevent->fields)) {
                        $input = [
                            'id' => $extevent->fields['id'],
                            'is_oncra' => isset($item->input['is_oncra']) ? $item->input['is_oncra'] :
                                $extevent->getField('is_oncra'),
                            'planningexternalevents_id' => $item->input['id'],
                            'actiontime' => $actiontime
                        ];
                        if ($options['use_planningeventsubcategories']) {
                            if (isset($item->input['planningeventsubcategories_id'])) {
                                $input['planningeventsubcategories_id'] = $item->input['planningeventsubcategories_id'];
                            }
                        }
                        $extevent->update($input);
                    } elseif (!$is_exist) {
                        $input = [
                            'is_oncra' => isset($item->input['is_oncra']) ? $item->input['is_oncra'] : '',
                            'planningexternalevents_id' => $item->getID(),
                            'actiontime' => $actiontime
                        ];
                        if ($options['use_planningeventsubcategories']) {
                            if (isset($item->input['planningeventsubcategories_id'])) {
                                $input['planningeventsubcategories_id'] = $item->input['planningeventsubcategories_id'];
                            }
                        }
                        $extevent->add($input);
                    }
                } else {
                    $is_cra_default = 0;
                    $opt = new PluginActivityOption();
                    $opt->getFromDB(1);
                    if ($opt) {
                        $is_cra_default = $opt->fields['is_cra_default'];
                    }
                    if (isset($_POST['action']) && $_POST['action'] == 'clone_event') {
                        $iterator = $DB->request([
                            'FROM' => 'glpi_plugin_activity_planningexternalevents',
                            'LEFT JOIN' => [
                                'glpi_planningexternalevents' => [
                                    'FKEY' => [
                                        'glpi_planningexternalevents' => 'id',
                                        'glpi_plugin_activity_planningexternalevents' => 'planningexternalevents_id'
                                    ]
                                ]
                            ],
                            'WHERE' => ['planningexternalevents_id' => $_POST['event']['old_items_id']]
                        ]);
                        if (count($iterator)) {
                            foreach ($iterator as $data) {
                                $extevent->add([
                                    'is_oncra' => $data['is_oncra'],
                                    'planningexternalevents_id' => $item->getID(),
                                    'actiontime' => $data['actiontime'],
                                    'planningeventsubcategories_id' => $data['planningeventsubcategories_id']
                                ]);
                            }
                        }
                    } elseif (!$is_exist) { // add events and update events created before the plugin activation
                        $input = [
                            'is_oncra' => isset($item->input['is_oncra']) ? $item->input['is_oncra'] : $is_cra_default,
                            'planningexternalevents_id' => $item->getID(),
                            'actiontime' => $actiontime
                        ];
                        if ($options['use_planningeventsubcategories']) {
                            if (isset($item->input['planningeventsubcategories_id'])) {
                                $input['planningeventsubcategories_id'] = $item->input['planningeventsubcategories_id'];
                            }
                        }
                        $extevent->add($input);
                    }
                }
            }
        }
    }

    static function prepareInputToUpdateWithPluginOptions($item)
    {
        $holiday = new PluginActivityHoliday();
        $planning_ext_event = new PlanningExternalEvent();
        $holiday->setHolidays();

        $opt = new PluginActivityOption();
        $opt->getFromDB(1);

        $use_pairs = $opt->fields['use_pairs'];
        $use_integerschedules = $opt->fields['use_integerschedules'];
        $use_we = $opt->fields['use_weekend'];

        if ($opt && $opt->fields['use_type_as_name'] == 1 && isset($item->input['planningeventcategories_id'])) {
            if ($item->fields['name'] == Dropdown::getDropdownName(
                    'glpi_planningeventcategories',
                    $item->fields['planningeventcategories_id']
                )) {
                $item->input['name'] = addslashes(
                    Dropdown::getDropdownName(
                        'glpi_planningeventcategories',
                        $item->input['planningeventcategories_id']
                    )
                );
            }
        }

        if (isset($_POST['action']) && $_POST['action'] == 'update_event_times') {
            if ((isset($_POST['start']) && ($_POST['start'] != 'NULL')) && (isset($_POST['end']) && ($_POST['end'] != 'NULL'))) {
                $begin = $_POST['start'];
                $end = $_POST['end'];

                $begin_hour = date('i', strtotime($begin));
                $end_hour = date('i', strtotime($end));

                $delay = floor((strtotime($end) - strtotime($begin)) / 3600);

                if ($use_integerschedules && ($begin_hour != '00' || $end_hour != '00')) {
                    Session::addMessageAfterRedirect(
                        __('Only whole hours are allowed (no split times)', 'activity')
                    );
                    unset($item->input);
                    return false;
                }

                if ($use_pairs == 1 && ($delay % 2 > 0)) {
                    Session::addMessageAfterRedirect(
                        __('Only pairs schedules are allowed', 'activity'),
                        false,
                        ERROR
                    );
                    unset($item->input);
                    return false;
                }
            }
        } else {
            if (isset($item->fields['rrule'])
                && !empty($item->fields['rrule'])) {
                $rrule = $item->input['rrule'];
                $input_date_begin = explode("-", $item->fields['begin']);
                $crit["begin"] = $input_date_begin[0] . "-" . $input_date_begin[1] . "-01 00:00:00";
                $lastday = cal_days_in_month(CAL_GREGORIAN, "12", $input_date_begin[0]);
                $crit["end"] = $input_date_begin[0] . "-" . "12" . "-" . $lastday . " 23:59:59";
                if (isset($rrule['exceptions'])
                    && $rrule['exceptions'] != '') {
                    if (!is_array($rrule['exceptions'])) {
                        $rrule['exceptions'] = explode(",", $rrule['exceptions']);
                    }
                }

                $array_inputs_occurence = PluginActivityPlanningExternalEvent::prepareInputsForReccurOccurence(
                    $item->fields,
                    $crit,
                    $rrule
                );
                $occurences = $array_inputs_occurence['rset']->getOccurrencesBetween(
                    $array_inputs_occurence['begin_datetime'],
                    $array_inputs_occurence['end_datetime']
                );
                // add the found occurences to the final tab after replacing their dates

                foreach ($occurences as $currentDate) {
                    $input = [];
                    $input['current_date'] = $currentDate->format('Y-m-d H:i:s');
                    if ($use_we == 0) {
                        if ($holiday->isWeekend($input['current_date'], true)) {
                            Session::addMessageAfterRedirect(
                                __(
                                    'One of the dates is on weekend, please add the date in the exceptionsof the reccurent rule',
                                    'activity'
                                ),
                                false,
                                ERROR
                            );
                            unset($item->input);
                            return false;
                        }
                    }
                    $date_exception = PluginActivityHoliday::checkInHolidays($input, $holiday->getHolidays());
                    if ($date_exception != false) {
                        $array_inputs_occurence['rrule']['exceptions'][] = $date_exception;
                    }
                }
                $item->input['rrule'] = $planning_ext_event->encodeRrule($array_inputs_occurence['rrule']);
            }

            // the only time it shouldn't be set is when the event is updated by cleanDBRelations (associated template is deleted)
            if (isset($item->input["planningeventcategories_id"])) {
                if ($item->input["planningeventcategories_id"] == 0) {
                    Session::addMessageAfterRedirect(__('Activity type is mandatory field', 'activity'), false, ERROR);
                    unset($item->input);
                    return false;
                }
            }
            // same as above
            if (isset($item->input["users"])) {
                if ($item->input['users_id'] == 0) {
                    Session::addMessageAfterRedirect(__('User is mandatory field', 'activity'), false, ERROR);
                    unset($item->input);
                    return false;
                }
            }
            // same as above
            if (isset($item->input["plan"])) {
                if (PluginActivityHoliday::checkInHolidays($item->input['plan'], $holiday->getHolidays())) {
                    Session::addMessageAfterRedirect(
                        __('The chosen date is a public holiday', 'activity'),
                        false,
                        ERROR
                    );
                    unset($item->input);
                    return false;
                }
            }

            if ($use_we == 0) {
                $hol = new PluginActivityHoliday();
                if (isset($item->input['start'])) {
                    if ($hol->isWeekend($item->input['start'], true)) {
                        Session::addMessageAfterRedirect(
                            __('The chosen begin date is on weekend', 'activity'),
                            false,
                            ERROR
                        );
                        unset($item->input);
                        return false;
                    }
                }
                if (isset($item->input['end'])) {
                    if ($hol->isWeekend($item->input['end'], false)) {
                        Session::addMessageAfterRedirect(
                            __('The chosen end date is on weekend', 'activity'),
                            false,
                            ERROR
                        );
                        unset($item->input);
                        return false;
                    }
                }
            }
        }
    }

    static function prepareInputsForReccurOccurence($item, $crit, $rrule = '')
    {
        $array_inputs_occurence = [];
        $array_inputs_occurence['duration'] = strtotime($item['end']) - strtotime($item['begin']);
        if ($rrule != '') {
            $array_inputs_occurence['rrule'] = $rrule;
        } else {
            $array_inputs_occurence['rrule'] = json_decode($item['rrule'], 1);
        }
        $array_inputs_occurence['rset'] = PlanningExternalEvent::getRsetFromRRuleField(
            $array_inputs_occurence['rrule'],
            $item['begin']
        );
        $begin_datetime = new DateTime($crit["begin"], new DateTimeZone('UTC'));
        $array_inputs_occurence['begin_datetime'] = $begin_datetime->sub(
            new DateInterval("PT" . ($array_inputs_occurence['duration'] - 1) . "S")
        );
        $array_inputs_occurence['end_datetime'] = new DateTime($crit['end'], new DateTimeZone('UTC'));

        return $array_inputs_occurence;
    }


    static function prepareInputToAddWithPluginOptions(PlanningExternalEvent $item)
    {
        $holiday = new PluginActivityHoliday();
        $planning_ext_event = new PlanningExternalEvent();
        $holiday->setHolidays();

        $opt = new PluginActivityOption();
        $opt->getFromDB(1);

        $use_pairs = $opt->fields['use_pairs'];
        $use_integerschedules = $opt->fields['use_integerschedules'];
        $use_we = $opt->fields['use_weekend'];


        if ($opt && $opt->fields['use_type_as_name'] == 1) {
            $item->input["name"] = addslashes(
                Dropdown::getDropdownName(
                    'glpi_planningeventcategories',
                    $item->input['planningeventcategories_id']
                )
            );
        }

        if (!isset($item->input["planningeventcategories_id"]) || $item->input["planningeventcategories_id"] == 0) {
            Session::addMessageAfterRedirect(__('Activity type is mandatory field', 'activity'), false, ERROR);
            unset($item->input);
            return false;
        }

        if (!isset($item->input["users_id"]) || $item->input["users_id"] == 0) {
            Session::addMessageAfterRedirect(__('User is mandatory field', 'activity'), false, ERROR);
            unset($item->input);
            return false;
        }

        if ((isset($item->input['begin']) && ($item->input['begin'] != 'NULL')) &&
            (isset($item->input['end']) && ($item->input['end'] != 'NULL'))) {
            $begin_hour = date('i', strtotime($item->input['begin']));
            $end_hour = date('i', strtotime($item->input['end']));

            //add exceptions for holidays
            if (isset($item->input['rrule']) && !empty($item->input['rrule'])) {
                $input_date_begin = explode("-", $item->input['begin']);
                $crit["begin"] = $input_date_begin[0] . "-" . $input_date_begin[1] . "-01 00:00:00";
                $lastday = cal_days_in_month(CAL_GREGORIAN, "12", $input_date_begin[0]);
                $crit["end"] = $input_date_begin[0] . "-" . "12" . "-" . $lastday . " 23:59:59";


                if (isset($_POST['action']) && $_POST['action'] == 'clone_event') {
                    $item->input['rrule'] = $item->getField('rrule');
                }
                $array_inputs_occurence = PluginActivityPlanningExternalEvent::prepareInputsForReccurOccurence(
                    $item->input,
                    $crit
                );

                $occurences = $array_inputs_occurence['rset']->getOccurrencesBetween(
                    $array_inputs_occurence['begin_datetime'],
                    $array_inputs_occurence['end_datetime']
                );
                // add the found occurences to the final tab after replacing their dates

                foreach ($occurences as $currentDate) {
                    $input = [];
                    $input['current_date'] = $currentDate->format('Y-m-d H:i:s');
                    if ($use_we == 0) {
                        if ($holiday->isWeekend($input['current_date'], true)) {
                            Session::addMessageAfterRedirect(
                                __(
                                    'One of the dates is on weekend, please add the date in the exceptionsof the reccurent rule',
                                    'activity'
                                ),
                                false,
                                ERROR
                            );
                            unset($item->input);
                            return false;
                        }
                    }
                    $date_exception = PluginActivityHoliday::checkInHolidays($input, $holiday->getHolidays());

                    if ($date_exception != false) {
                        if (!isset($array_inputs_occurence['rrule']['exceptions'])) {
                            $array_inputs_occurence['rrule']['exceptions'][] = $date_exception;
                        } else {
                            array_push($array_inputs_occurence['rrule']['exceptions'], $date_exception);
                        }
                    }
                }
                $item->input['rrule'] = $planning_ext_event->encodeRrule($array_inputs_occurence['rrule']);
            }

            $delay = floor((strtotime($item->input['end']) - strtotime($item->input['begin'])) / 3600);

            if ($use_integerschedules && ($begin_hour != '00' || $end_hour != '00')) {
                Session::addMessageAfterRedirect(__('Only whole hours are allowed (no split times)', 'activity'));
                unset($item->input);
                return false;
            }

            if ($use_pairs == 1 && ($delay % 2 > 0)) {
                Session::addMessageAfterRedirect(__('Only pairs schedules are allowed', 'activity'), false, ERROR);
                unset($item->input);
                return false;
            }

            if (PluginActivityHoliday::checkInHolidays($item->input, $holiday->getHolidays())) {
                Session::addMessageAfterRedirect(
                    __('The chosen date is a public holiday', 'activity'),
                    false,
                    ERROR
                );
                unset($item->input);
                return false;
            }


            if ($use_we == 0) {
                $hol = new PluginActivityHoliday();
                if ($hol->isWeekend($item->input["begin"], true)) {
                    Session::addMessageAfterRedirect(
                        __('The chosen begin date is on weekend', 'activity'),
                        false,
                        ERROR
                    );
                    unset($item->input);
                    return false;
                }
                if ($hol->isWeekend($item->input["end"], false)) {
                    Session::addMessageAfterRedirect(
                        __('The chosen end date is on weekend', 'activity'),
                        false,
                        ERROR
                    );
                    unset($item->input);
                    return false;
                }
            }
        }
    }


    static function activityUpdate(PlanningExternalEvent $item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        if (isset($_POST['action']) && $_POST['action'] == 'delete_event') {
            return false;
        }

        self::prepareInputToUpdateWithPluginOptions($item);
        self::setActivity($item);
    }

    static function activityAdd(PlanningExternalEvent $item)
    {
        if (!is_array($item->input) || !count($item->input)) {
            // Already cancel by another plugin
            return false;
        }

        self::setActivity($item);
    }

    static function queryAllExternalEvents($criteria)
    {
        $dbu = new DbUtils();
        $options = PluginActivityOption::getConfigFromDB()[1];
        $subcategoriesSelect = $options['use_planningeventsubcategories'] ? '`glpi_plugin_activity_planningexternalevents`.`planningeventsubcategories_id` AS subtype, `glpi_plugin_activity_planningeventsubcategories`.`name` AS subname, ' : '';
        $subcategoriesJoin = $options['use_planningeventsubcategories'] ? 'LEFT JOIN `glpi_plugin_activity_planningeventsubcategories`
                         ON (`glpi_plugin_activity_planningexternalevents`.`planningeventsubcategories_id` = `glpi_plugin_activity_planningeventsubcategories`.`id`)' : '';
        $query = "SELECT `glpi_planningexternalevents`.`planningeventcategories_id` AS type,
                $subcategoriesSelect
                    SUM(`glpi_plugin_activity_planningexternalevents`.`actiontime`) AS total_actiontime, 
                        `glpi_planningeventcategories`.`name` AS name
                     FROM `glpi_planningexternalevents` 
                     INNER JOIN `glpi_planningeventcategories` 
                        ON (`glpi_planningeventcategories`.`id` = `glpi_planningexternalevents`.`planningeventcategories_id`)
                        INNER JOIN `glpi_plugin_activity_planningexternalevents`
                         ON (`glpi_planningexternalevents`.`id` = `glpi_plugin_activity_planningexternalevents`.`planningexternalevents_id`)
                        $subcategoriesJoin
                         ";
        $query .= "WHERE (`glpi_planningexternalevents`.`begin` >= '" . $criteria["begin"] . "' 
                  AND `glpi_planningexternalevents`.`begin` <= '" . $criteria["end"] . "') ";
        $query .= "  AND `glpi_planningexternalevents`.`users_id` = '" . $criteria["users_id"] . "' "
            . $dbu->getEntitiesRestrictRequest("AND", "glpi_planningexternalevents");
        $subcategoriesGroup = $options['use_planningeventsubcategories'] ? ', `glpi_plugin_activity_planningexternalevents`.`planningeventsubcategories_id` ' : '';
        $query .= " GROUP BY `glpi_planningexternalevents`.`planningeventcategories_id` $subcategoriesGroup
                 ORDER BY name";

        return $query;
    }

    static function queryManageentities($criteria)
    {
        $dbu = new DbUtils();
        $query = "SELECT `glpi_tickets_users`.`users_id`,
                       `glpi_entities`.`name` AS entity, 
                       `glpi_plugin_manageentities_cridetails`.`date`, 
                       `glpi_plugin_manageentities_cridetails`.`technicians`, 
                       `glpi_plugin_manageentities_cridetails`.`plugin_manageentities_critypes_id`, 
                       `glpi_plugin_manageentities_cridetails`.`withcontract`, 
                       `glpi_plugin_manageentities_cridetails`.`contracts_id`, 
                       `glpi_tickets`.`id`AS tickets_id "
            . " FROM `glpi_plugin_manageentities_cridetails` "
            . " LEFT JOIN `glpi_tickets` ON (`glpi_plugin_manageentities_cridetails`.`tickets_id` = `glpi_tickets`.`id`)"
            . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
            . " LEFT JOIN `glpi_tickets_users` ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id`)"
            . " LEFT JOIN `glpi_tickettasks` ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`)"
            . " LEFT JOIN `glpi_plugin_manageentities_critechnicians` ON (`glpi_plugin_manageentities_cridetails`.`tickets_id` = `glpi_plugin_manageentities_critechnicians`.`tickets_id`) "
            . " WHERE `glpi_tickets_users`.`type` = " . Ticket::ASSIGNED . " 
                  AND (`glpi_tickettasks`.`begin` >= '" . $criteria["begin"] . "' 
                  AND `glpi_tickettasks`.`end` <= '" . $criteria["end"] . "') "
            . " AND `glpi_tickets`.`is_deleted` = 0"
            . " AND (`glpi_tickets_users`.`users_id` ='" . $criteria["users_id"] . "' OR `glpi_plugin_manageentities_critechnicians`.`users_id` ='" . $criteria["users_id"] . "') ";
        $query .= $dbu->getEntitiesRestrictRequest(
            "AND",
            "glpi_tickets",
            '',
            $_SESSION["glpiactiveentities"],
            false
        );
        $query .= " AND `glpi_tickettasks`.`actiontime` != 0";
        $query .= " GROUP BY `glpi_plugin_manageentities_cridetails`.`tickets_id` ";
        $query .= " ORDER BY `glpi_plugin_manageentities_cridetails`.`date` ASC";

        return $query;
    }

    static function queryTickets($criteria)
    {
        $query = "SELECT    `glpi_tickettasks`.*,
                          `glpi_plugin_activity_tickettasks`.`is_oncra`,
                          `glpi_entities`.`name` AS entity,
                          `glpi_entities`.`id` AS entities_id
                     FROM `glpi_tickettasks`
                     INNER JOIN `glpi_tickets`
                        ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id` AND `glpi_tickets`.`is_deleted` = 0)
                     LEFT JOIN `glpi_entities` 
                        ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) 
                     LEFT JOIN `glpi_plugin_activity_tickettasks` 
                        ON (`glpi_tickettasks`.`id` = `glpi_plugin_activity_tickettasks`.`tickettasks_id`) ";
        $query .= "WHERE ";
        if (Plugin::isPluginActive('manageentities')) {
            $query .= "`glpi_tickettasks`.`tickets_id` 
                     NOT IN (SELECT `tickets_id` 
                              FROM `glpi_plugin_manageentities_cridetails`) AND ";
        }
        $dbu = new DbUtils();
        $query .= "((`glpi_tickettasks`.`begin` >= '" . $criteria["begin"] . "' 
                           AND `glpi_tickettasks`.`end` <= '" . $criteria["end"] . "'
                           AND `glpi_tickettasks`.`users_id_tech` = '" . $criteria["users_id"] . "' " .
            $dbu->getEntitiesRestrictRequest(
                "AND",
                "glpi_tickets",
                '',
                $_SESSION["glpiactiveentities"],
                false
            );
        $query .= "                     ) 
                           OR (`glpi_tickettasks`.`date` >= '" . $criteria["begin"] . "' 
                           AND `glpi_tickettasks`.`date` <= '" . $criteria["end"] . "'
                           AND `glpi_tickettasks`.`users_id_tech` = '" . $criteria["users_id"] . "'
                           AND `glpi_tickettasks`.`begin` IS NULL " . $dbu->getEntitiesRestrictRequest(
                "AND",
                "glpi_tickets",
                '',
                $_SESSION["glpiactiveentities"],
                false
            );
        $query .= " )) AND `glpi_tickettasks`.`actiontime` != 0 AND `glpi_plugin_activity_tickettasks`.`is_oncra` = 1";
        $query .= " ORDER BY `glpi_tickettasks`.`begin` ASC";

        return $query;
    }

    static function queryUserExternalEvents($criteria)
    {
        $opt = new PluginActivityOption();
        $opt->getFromDB(1);

        $dbu = new DbUtils();
        $query = "SELECT `glpi_planningexternalevents`.`name` AS name,
                       `glpi_planningexternalevents`.`id` AS id,
                       `glpi_plugin_activity_planningexternalevents`.`actiontime` AS actiontime,
                       `glpi_planningexternalevents`.`text` AS text,
                       `glpi_planningeventcategories`.`name` AS type,
                       `glpi_plugin_activity_planningeventsubcategories`.`name` as subtype,
                       `glpi_planningexternalevents`.`begin` AS begin,
                       `glpi_planningexternalevents`.`end` AS end,
                       `glpi_planningexternalevents`.`rrule` AS rrule,
                       `glpi_planningexternalevents`.`planningeventcategories_id` AS type_id,
                        `glpi_plugin_activity_planningexternalevents`.`planningeventsubcategories_id` as subtype_id,
                       `glpi_entities`.`name` AS entity
               FROM `glpi_planningexternalevents` ";
        $query .= " LEFT JOIN `glpi_plugin_activity_planningexternalevents` 
                     ON (`glpi_planningexternalevents`.`id` = `glpi_plugin_activity_planningexternalevents`.`planningexternalevents_id`)";
        $query .= " LEFT JOIN `glpi_plugin_activity_planningeventsubcategories` 
                     ON (`glpi_plugin_activity_planningeventsubcategories`.`id` = `glpi_plugin_activity_planningexternalevents`.`planningeventsubcategories_id`)";
        $query .= " LEFT JOIN `glpi_users` 
                     ON (`glpi_users`.`id` = `glpi_planningexternalevents`.`users_id`)";
        $query .= " LEFT JOIN `glpi_planningeventcategories` 
                     ON (`glpi_planningeventcategories`.`id` = `glpi_planningexternalevents`.`planningeventcategories_id`)";
        $query .= " LEFT JOIN `glpi_entities` 
                     ON (`glpi_planningexternalevents`.`entities_id` = `glpi_entities`.`id`)";
        $query .= " WHERE ";
        $query .= "  `glpi_planningexternalevents`.`users_id` = '" . $criteria["users_id"] . "' "
            . $dbu->getEntitiesRestrictRequest("AND", "glpi_planningexternalevents") . "
                  AND ((`glpi_planningexternalevents`.`begin` >= '" . $criteria["begin"] . "' 
                  AND `glpi_planningexternalevents`.`begin` <= '" . $criteria["end"] . "') ";
        // reoccuring events where the serie started before the ending date and ended during the ending month or later
        $year = date('Y', strtotime($criteria["end"]));
        $month = date('m', strtotime($criteria["end"]));
        $query .= "OR (
        `glpi_planningexternalevents`.`begin` < '" . $criteria["end"] . "'
         AND (
            `glpi_planningexternalevents`.`rrule` REGEXP '\"until\":\"" . $year . "-(";
        // regexp to add recurring events that can happen until the end of the year
        $months = [];
        for ($m = (int)$month; $m < 13; $m++) {
            $value = $m < 10 ? '0' . $m : $m;
            $months[] = $value;
        }
        $query .= implode('|', $months);
        $query .= ")'
            OR `glpi_planningexternalevents`.`rrule` REGEXP '\"until\":\"(";
        // regexp to add recurring events which continue up to 5 years into the future
        for ($i = 0; $i < 5; $i++) {
            $year++;
            if ($i != 0) {
                $query .= '|';
            }
            $query .= "$year";
        }
        $query .= ")')))";

        if ($criteria["is_usedbycra"]) {
            $query .= " AND `glpi_plugin_activity_planningexternalevents`.`is_oncra` ";
        }
        $query .= " AND `glpi_plugin_activity_planningexternalevents`.`actiontime` != 0";
        $query .= " ORDER BY `glpi_planningexternalevents`.`name`, `subtype`";
        if ($opt->fields['show_planningevents_entity']) {
            $query .= ", `entity`";
        }
        return $query;
    }

    static function dateAdd($v, $d = null, $f = "Y-m-d")
    {
        $d = ($d ? $d : date("Y-m-d"));
        return date($f, strtotime($v . " days", strtotime($d)));
    }

    static function getNbDays($debut, $fin)
    {
        $diff = strtotime(date('Y-m-d', strtotime($fin))) - strtotime(date('Y-m-d', strtotime($debut)));

        return (round($diff / (3600 * 24)) + 1);
    }


    function getFromDBForTask($projecttasks_id)
    {
        $dbu = new DbUtils();
        $data = $dbu->getAllDataFromTable(
            $this->getTable(),
            [$dbu->getForeignKeyFieldForTable('glpi_planningexternalevents') => $projecttasks_id]
        );

        $this->fields = array_shift($data);
    }

    /**
     * @param string $begin date format "Y-md h:i:s"
     * @param string $end date format "Y-md h:i:s"
     * @param int $users_id id of tech for the tasks
     * @return array ticket tasks with activity during the selected period
     */
    public static function getTicketTasksManageentities(string $begin, string $end, int $users_id)
    {
        global $DB;

        $ticketUserTable = Ticket_User::getTable();
        $entityTable = Entity::getTable();
        $criDetailTable = PluginManageentitiesCriDetail::getTable();
        $criTechnicianTable = PluginManageentitiesCriTechnician::getTable();
        $ticketTable = Ticket::getTable();
        $ticketTaskTable =  TicketTask::getTable();

        $request = [
            'SELECT' => [
                $ticketUserTable.'.users_id',
                $entityTable.'.name AS entity',
                $criDetailTable.'.date',
                $criDetailTable.'.technicians',
                $criDetailTable.'.plugin_manageentities_critypes_id',
                $criDetailTable.'.withcontract',
                $criDetailTable.'.contracts_id',
                $ticketTable.'.id AS tickets_id'
            ],
            'FROM' => $criDetailTable,
            'LEFT JOIN' => [
                $ticketTable => [
                    'FKEY' => [
                        $ticketTable => 'id',
                        $criDetailTable => 'tickets_id'
                    ]
                ],
                $entityTable => [
                    'FKEY' => [
                        $ticketTable => 'entities_id',
                        $entityTable => 'id'
                    ]
                ],
                $ticketUserTable => [
                    'FKEY' => [
                        $ticketTable => 'id',
                        $ticketUserTable => 'tickets_id'
                    ]
                ],
                $ticketTaskTable => [
                    'FKEY' => [
                        $ticketTable => 'id',
                        $ticketTaskTable => 'tickets_id'
                    ]
                ],
                $criTechnicianTable => [
                    'FKEY' => [
                        $criTechnicianTable => 'tickets_id',
                        $criDetailTable => 'tickets_id'
                    ]
                ]
            ],
            'WHERE' => [
                $ticketUserTable.'.type' => Ticket::ASSIGNED,
                $ticketTable.'.is_deleted' => 0,
                self::getTicketTaskDateCriterias($begin, $end),
                [
                    'OR' => [
                        $ticketUserTable.'.users_id' => $users_id,
                        $criTechnicianTable.'.users_id' => $users_id
                    ]
                ],
                getEntitiesRestrictCriteria($ticketTable, '', $_SESSION["glpiactiveentities"], false)
            ],
            'ORDERBY' => $criDetailTable.'.date ASC',
            'GROUPBY' => $criDetailTable.'.tickets_id'
        ];

        $results = $DB->request($request);
        $tasks = [];
        foreach($results as $result) {
            $tasks[] = $result;
        }
        return $tasks;
    }

    /**
     * @param string $begin date format "Y-md h:i:s"
     * @param string $end date format "Y-md h:i:s"
     * @param int $users_id id of user owner of the events
     * @param boolean $cra if true, only events visible on CRA
     * @param boolean $subcat retrieve subcategory of the events
     * @return array events with activity during the selected period, events with repetitions during the repetition have a record for each repetition individually (repetition during holidays or day not worked aren't included)
     */
    public static function getExternalEventWithActivityOnPeriod(string $begin, string $end, int $users_id, bool $cra = true, bool $subCat = false)
    {
        global $DB, $CFG_GLPI;

        $year = date('Y', strtotime($end));
        $month = date('m', strtotime($end));

        // regex to get rrule which last until $end's month or after
        $monthRegex = "\"until\":\"$year-(";
        $months = [];
        for ($m = (int)$month; $m < 13; $m++) {
            $value = $m < 10 ? '0' . $m : $m;
            $months[] = $value;
        }
        $monthRegex .= implode('|', $months);
        $monthRegex .= ")";

        // regex to get rrule which last until $end's year or within the next 5 years
        $yearRegex = "\"until\":\"(";
        for ($i = 0; $i < 5; $i++) {
            $year++;
            if ($i != 0) {
                $yearRegex .= '|';
            }
            $yearRegex .= "$year";
        }
        $yearRegex .= ")";

        $table = PlanningExternalEvent::getTable();
        $entityTable = Entity::getTable();
        $categoryTable = PlanningEventCategory::getTable();
        $subQueryCrit = [
            'SELECT' =>[$table.'.id'],
            'FROM' => $table,
            'WHERE' => [
                $table.'.users_id' => $users_id,
                'OR' => [
                    [ // events within the period
                        [$table.'.end' => ['>', $begin]],
                        [$table.'.begin' => ['<', $end]]
                    ],
                    [ // events which may have a repetition within the period
                        $table.'.begin' => ['<', $end],
                        'OR' => [
                            [$table.'.rrule' => ['REGEXP', $monthRegex]],
                            [$table.'.rrule' => ['REGEXP', $yearRegex]],
                        ]
                    ]
                ]
            ]
        ];

        $pluginTable = PluginActivityPlanningExternalEvent::getTable();
        if ($cra) {
            $subQueryCrit['JOIN'] = [
                 $pluginTable => [
                    'FKEY' => [
                        $table => 'id',
                        $pluginTable => 'planningexternalevents_id'
                    ]
                ]
            ];
            $subQueryCrit['WHERE'][] = [
                $pluginTable.'.is_oncra' => 1
            ];
        }

        $request = [
            'SELECT' => [
                $table.'.*',
                $entityTable.'.name as entity_name',
                $categoryTable.'.name as category_name'
            ],
            'FROM' => $table,
            'JOIN' => [
                $entityTable => [
                    'FKEY' => [
                        $table => 'entities_id',
                        $entityTable => 'id'
                    ]
                ],
                $categoryTable => [
                    'FKEY' => [
                        $table => 'planningeventcategories_id',
                        $categoryTable => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                $table.'.id' => new QuerySubQuery($subQueryCrit)
            ]
        ];

        if ($subCat) {
            $subCatTable = PluginActivityPlanningeventsubcategory::getTable();
            $request['LEFT JOIN'] = [
                $pluginTable => [
                    'FKEY' => [
                        $table => 'id',
                        $pluginTable => 'planningexternalevents_id'
                    ]
                ],
                $subCatTable => [
                    'FKEY' => [
                        $subCatTable => 'id',
                        $pluginTable => 'planningeventsubcategories_id'
                    ]
                ]
            ];
            $request['SELECT'][] = $pluginTable.'.planningeventsubcategories_id as subcategories_id';
            $request['SELECT'][] = $subCatTable.'.name as subcategory_name';
        }

        $events = $DB->request($request);

        $beginDateTime = new DateTime($begin);
        $endDateTime = new DateTime($end);

        // active entity's calendar
        $calendar = new Calendar();
        $entity = new Entity();
        $entity->getFromDB(Session::getActiveEntity());
        $calendars_id = $entity->fields['calendars_id'];
        if ($calendars_id == Entity::CONFIG_PARENT) {
            $calendars_id = Entity::getUsedConfig('calendars_strategy', Session::getActiveEntity(), 'calendars_id', 0);
        }
        $calendar->getFromDB($calendars_id);

        $results = [];
        foreach($events as $event) {
            $eventBegin = new DateTime($event['begin']);
            $eventEnd = new DateTime($event['end']);
            $event['actiontime'] = $eventEnd->getTimestamp() - $eventBegin->getTimestamp();
            // for events having a rrule, add each occurrence happening during the period as an individual record
            if ($event['rrule'] && $rrule = json_decode($event['rrule'], 1)) {
                $rset = PlanningExternalEvent::getRsetFromRRuleField($rrule, $event['begin']);
                $ocurrences = $rset->getOccurrencesBetween($begin, $end);
                foreach ($ocurrences as $occurrenceStart) {
                    $occurrenceEnd = clone $occurrenceStart;
                    $occurrenceEnd->modify('+'.$event['actiontime'].' seconds');
                    if ($occurrenceStart < $endDateTime && $occurrenceEnd > $beginDateTime) {
                        $weekday = $occurrenceStart->format('w');
                        // exclude occurrence happening on a non-working day
                        if (in_array($weekday, $CFG_GLPI["planning_work_days"])) {
                            // exclude occurrence happening on a holiday (defined by the current entity)
                            $isGlobalHoliday = false;
                            if ($calendar->getID() > 0) {
                                if ($calendar->isHoliday($occurrenceStart->format('Y-m-d H:i:s'))) {
                                    $isGlobalHoliday = true;
                                }
                            }
                            if (!$isGlobalHoliday) {
                                $holiday = new PluginActivityHoliday();
                                // exclude occurrence happening when the user is on leave/vacation/etc.
                                // TODO use global_validation ?
                                if (!$holiday->find([
                                    'users_id' => $users_id,
                                    'begin' => ['<', $occurrenceStart->format('Y-m-d H:i:s')],
                                    'end' => ['>', $occurrenceEnd->format('Y-m-d H:i:s')]
                                ])) {
                                    $copy = $event;
                                    $copy['date'] = $occurrenceStart->format('Y-m-d H:i:s');
                                    $copy['begin'] = $occurrenceStart->format('Y-m-d H:i:s');
                                    $copy['end'] = $occurrenceEnd->format('Y-m-d H:i:s');
                                    $results[] = $copy;
                                }
                            }
                        }
                    }
                }
            } else {
                $results[] = $event;
            }
        }

        return $results;
    }

    /**
     * @param string $begin date format "Y-md h:i:s"
     * @param string $end date format "Y-md h:i:s"
     * @param int $users_id id of tech for the tasks
     * @param boolean $cra if true, only tasks visible on CRA
     * @return array ticket tasks with activity during the selected period
     */
    public static function getTicketTaskWithActivityOnPeriod(string $begin, string $end, int $users_id, bool $cra = true) {
        global $DB;

        $taskTable = TicketTask::getTable();
        $ticketTable = Ticket::getTable();
        $entityTable = Entity::getTable();
        $pluginTable = PluginActivityTicketTask::getTable();
        $request = [
            'SELECT' => [
                $taskTable.'.*',
                $entityTable.'.id as entities_id',
                $entityTable.'.name as entity_name'
            ],
            'FROM' => $taskTable,
            'JOIN' => [
                $ticketTable => [
                    'FKEY' => [
                        $taskTable => 'tickets_id',
                        $ticketTable => 'id'
                    ]
                ],
                $entityTable => [
                    'FKEY' => [
                        $ticketTable => 'entities_id',
                        $entityTable => 'id'
                    ]
                ],
            ],
            'WHERE' => [
                $taskTable.'.users_id' => $users_id,
                $ticketTable.'.is_deleted' => 0,
                self::getTicketTaskDateCriterias($begin, $end),
                getEntitiesRestrictCriteria($ticketTable, '', $_SESSION["glpiactiveentities"], false)
            ]
        ];

        if ($cra) {
            $request['JOIN'][$pluginTable] = [
                'FKEY' => [
                    $taskTable => 'id',
                    $pluginTable => 'tickettasks_id'
                ]
            ];
            $request['SELECT'][] = $pluginTable.'.is_oncra as is_oncra';
            $request['WHERE'][$pluginTable.'.is_oncra'] = 1;
        }

        $results = $DB->request($request);
        $tasks =  [];
        foreach($results as $task) {
            $tasks[] = $task;
        }
        return $tasks;
    }

    /**
     * Generate date criterias for request needing all ticket tasks on a period
     * @param string $begin
     * @param string $end
     * @return array
     */
    public static function getTicketTaskDateCriterias(string $begin, string $end) {
        $ticketTaskTable = TicketTask::getTable();
        return [
            'AND' => [
                [
                    'OR' => [
                        [$ticketTaskTable.'.end' => ['>', $begin]],
                        [
                            [$ticketTaskTable.'.date' => ['>', $begin]], // if begin = null, date = end
                            [$ticketTaskTable.'.begin' => null]
                        ]

                    ]
                ],
                [
                    'OR' => [
                        [$ticketTaskTable.'.begin' => ['<', $end]],
                        [
                            'OR' => [
                                [
                                    [$ticketTaskTable.'.date' => ['<', $end]],
                                    [$ticketTaskTable.'.begin' => null]
                                ],
                                [ // not planned task with a date after the end, but an actiontime which indicate a start before the end
                                    "(TIMESTAMPADD(SECOND, -$ticketTaskTable.actiontime, $ticketTaskTable.date) < '$end')",
                                    [$ticketTaskTable.'.begin' => null]
                                ]
                            ]

                        ]

                    ]
                ]
            ],
            $ticketTaskTable.'.actiontime' => ['!=', 0] // skip tasks with no time (like the ones created by escalade)
        ];
    }
}
