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

namespace GlpiPlugin\Activity;

use CommonDBTM;
use Glpi\RichText\RichText;
use Html;

class PublicHoliday extends CommonDBTM
{
    public $dohistory = false;
    public static $rightname = "plugin_activity";

    // From CommonDBTM
    public $auto_message_on_action    = false;

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     * */
    public static function getTypeName($nb = 1)
    {
        return _n('Public holiday', 'Public holidays', $nb, 'activity');
    }

    /**
    * Add items in the items fields of the parm array
    * Items need to have an unique index beginning by the begin date of the item to display
    * needed to be correcly displayed
    **/
    public static function populatePlanning($options = [])
    {
        global $DB, $CFG_GLPI;

        $default_options = [
            'color'               => '',
            'event_type_color'    => '',
            'check_planned'       => false,
            'display_done_events' => true,
        ];
        $options = array_merge($default_options, $options);

        $interv   = [];
        $holiday = new Holiday();

        if (!isset($options['begin']) || ($options['begin'] == 'NULL')
            || !isset($options['end']) || ($options['end'] == 'NULL')) {
            return $interv;
        }

        if (!$options['display_done_events']) {
            return $interv;
        }

        $who        = $options['who'];
        $begin      = $options['begin'];
        $end        = $options['end'];

        $holiday = new Holiday();
        $holiday->setHolidays();
        $holidays = $holiday->getHolidays();

        if (!empty($holidays)) {
            foreach ($holidays as $k => $holiday) {
                if ($holiday['is_perpetual'] == 1) {
                    for ($i = 0; $i <= 100; $i++) {
                        $annee_courante = date('Y', time()) - 10;
                        $year[$i]       = ($annee_courante + $i);
                        $hb             = date('m-d', strtotime($holiday['begin']));
                        $he             = date('m-d', strtotime($holiday['end']));
                        $begin          = $year[$i] . '-' . $hb . ' 00:00:00';
                        $end            = $year[$i] . '-' . $he . ' 00:00:00';

                        if (($options['begin'] <= $begin && $begin <= $options['end'])
                           || ($options['begin'] <= $end && $end <= $options['end'])) {
                            $key                              = $begin . "$$" . "GlpiPlugin\Activity\PublicHoliday" . $k;
                            $interv[$key]['color']            = $options['color'];
                            $interv[$key]['event_type_color'] = $options['event_type_color'];
                            $interv[$key]["itemtype"]         = PublicHoliday::class;
                            $interv[$key]["id"]               = $k;
                            $interv[$key]["users_id"]         = 0;
                            $interv[$key]["begin"]            = $begin;
                            $interv[$key]["end"]              = $end;
                            $interv[$key]["name"]             = self::getTypeName(1);
                            $interv[$key]["content"]          = " ";
                            $interv[$key]["editable"]         = false;
                        }
                    }

                } else {

                    $begin                            = $holiday['begin'] . ' 00:00:00';
                    $end                              = $holiday['end'] . ' 00:00:00';
                    if (($options['begin'] <= $begin && $begin <= $options['end'])
                       || ($options['begin'] <= $end && $end <= $options['end'])) {
                        $key                              = $begin . "$$" . "GlpiPlugin\Activity\PublicHoliday" . $k;
                        $interv[$key]['color']            = $options['color'];
                        $interv[$key]['event_type_color'] = $options['event_type_color'];
                        $interv[$key]["itemtype"]         = PublicHoliday::class;
                        $interv[$key]["id"]               = $k;
                        $interv[$key]["users_id"]         = 0;
                        $interv[$key]["begin"]            = $begin;
                        $interv[$key]["end"]              = $end;
                        $interv[$key]["name"]             = self::getTypeName(1);
                        $interv[$key]["content"]          = " ";
                        $interv[$key]["editable"]         = false;
                    }
                }
            }
        }

        return $interv;

    }

    /**
     * Display a Planning Item
     *
     * @param $parm Array of the item to display
     * @return Nothing (display function)
     **/
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {

        $html = "";
        $rand     = mt_rand();

        if ($val["name"]) {
            // Security (stored XSS): Planning places the fragment returned here into the
            // "content" and "tooltip" properties of the fullcalendar events, which are rendered
            // as HTML and not as text, so escaping belongs to this sink - the sister class does
            // exactly that in Holiday::displayPlanningItem(), and so does the core in
            // Glpi\Features\PlanningEvent::displayPlanningItem(). What kept the omission
            // harmless so far is upstream, not here: populatePlanning() of this very class fills
            // "name" with the static translated type name and "content" with a single space.
            // Nothing in the signature of this method says so, and the day either key is fed
            // from the database the escaping has to already be in place.
            $html .= htmlescape($val["name"]) . "<br>";
        }

        if ($val["end"]) {
            $html .= "<strong>" . __('End date') . "</strong> : " . Html::convdatetime($val["end"]) . "<br>";
        }

        // The description is meant to carry markup, so it is sanitised rather than escaped:
        // Html::showToolTip() renders its argument as HTML too. Holiday applies the same
        // RichText::getSafeHtml() to the same key, only at populate time rather than at the
        // sink, which is why its own displayPlanningItem() may interpolate it directly.
        $content = RichText::getSafeHtml($val["content"]);

        if ($complete) {
            $html .= "<div class='event-description'>" . $content . "</div>";
        } else {
            $html .= Html::showToolTip(
                $content,
                ['applyto' => "cri_" . $val["id"] . $rand,
                    'display' => false],
            );
        }

        return $html;
    }

}
