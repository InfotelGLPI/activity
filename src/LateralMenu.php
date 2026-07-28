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

use Ajax;
use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class LateralMenu extends CommonDBTM
{

    static function showMenu()
    {
        $listActions = array_merge(PlanningExternalEvent::getActionsOn(), Holiday::getActionsOn());

        $types = [
            Action::ADD_ACTIVITY,
            Action::HOLIDAY_REQUEST,
            Action::CRA,
            Action::APPROVE_HOLIDAYS,
        ];

        $actions = [];
        foreach ($listActions as $key => $action) {
            if (in_array($key, $types) && $action['rights']) {
                $actions[] = [
                    'link'    => $action['link'],
                    'label'   => $action['label'],
                    'onclick' => $action['onclick'] ?? '',
                ];
            }
        }

        Ajax::createIframeModalWindow(
            'holiday',
            PLUGIN_ACTIVITY_WEBDIR . "/front/holiday.form.php",
            ['title'         => __('Create a holiday request', 'activity'),
            'reloadonclose' => false,
            'width'         => 1180,
            'height'        => 700,
            // Custom class widens the dialog beyond the default modal-xl (see public/activity.css).
            'dialog_class'  => 'modal-xl activity_holiday_modal',
            ]
        );

        $holidays_summary = [];
        if (Session::haveRight("plugin_activity_can_requestholiday", 1)) {
            $holiday = new Holiday();
            $hcount  = new HolidayCount();
            $periods = $hcount->getCurrentPeriods();
            if (count($periods) > 0) {
                $nbHolidays      = $holiday->countNbHolidayByPeriod(Session::getLoginUserID(true), $periods);
                $holidays_summary = $hcount->buildHolidayDetailsByPeriod($nbHolidays);
            }
        }

        TemplateRenderer::getInstance()->display('@activity/lateral_menu.html.twig', [
            'actions'          => $actions,
            'holidays_summary' => $holidays_summary,
        ]);
    }

}
