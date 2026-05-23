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
 the Free Software Foundation; either version 2 of the License, or
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

        echo "<div class='d-grid gap-2 mb-3'>";
        foreach ($listActions as $key => $action) {
            if (in_array($key, $types) && $action['rights']) {
                echo "<a href=\"" . $action['link'] . "\" class='btn btn-info'";
                if (isset($action['onclick']) && !empty($action['onclick'])) {
                    echo $action['onclick'];
                }
                echo ">";
                echo $action['label'];
                echo "</a>";
            }
        }
        echo "</div>";

        Ajax::createIframeModalWindow(
            'holiday',
            PLUGIN_ACTIVITY_WEBDIR . "/front/holiday.form.php",
            ['title'         => __('Create a holiday request', 'activity'),
            'reloadonclose' => false,
            'width'         => 1180,
            'height'        => 500,
            ]
        );

        if (Session::haveRight("plugin_activity_can_requestholiday", 1)) {
            $holiday = new Holiday();
            $hcount  = new HolidayCount();

            $periods = $hcount->getCurrentPeriods();

            if (count($periods) > 0) {
                $nbHolidays = $holiday->countNbHolidayByPeriod(Session::getLoginUserID(true), $periods);
                echo "<table class='tab_cadre' width='100%'>";
                $hcount->showHolidayDetailsByPeriod($nbHolidays);
                echo "</table>";
            }
        }
    }

    /**
     * Create a Bootstrap Offcanvas side panel
     *
     * @param string $name    name / id of the offcanvas element
     * @param array  $options Possible options:
     *          - title     Title to display
     *          - position  position (either left or right - defaults to right)
     *          - url       URL to load content from
     *          - display   display or get string? (default true)
     *
     * @return void|string (see $options['display'])
     */
    static function createSlidePanel($name, $options = [])
    {
        $param = [
            'title'    => '',
            'position' => 'right',
            'url'      => '',
            'display'  => true,
            'icon'     => false,
            'icon_url' => false,
            'icon_txt' => false,
        ];

        foreach ($options as $key => $val) {
            if (isset($param[$key])) {
                $param[$key] = $val;
            }
        }

        $js_name      = json_encode($name);
        $js_title     = json_encode($param['title']);
        $js_url       = json_encode($param['url']);
        $js_close_lbl = json_encode(__s('Close'));
        $js_pos_class = ($param['position'] === 'left') ? 'offcanvas-start' : 'offcanvas-end';

        $out = "<script type='text/javascript'>
(function () {
    var ocId = $js_name;
    if (document.getElementById(ocId)) { return; }
    var oc = document.createElement('div');
    oc.id = ocId;
    oc.className = 'offcanvas $js_pos_class';
    oc.tabIndex = -1;
    oc.setAttribute('data-bs-scroll', 'true');
    var spinner = '<div class=\"text-center p-4\"><div class=\"spinner-border\" role=\"status\"></div></div>';
    oc.innerHTML =
        '<div class=\"offcanvas-header border-bottom\">'
        + '<h5 class=\"offcanvas-title\">' + $js_title + '</h5>'
        + '<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"offcanvas\" aria-label=\"' + $js_close_lbl + '\"></button>'
        + '</div>'
        + '<div class=\"offcanvas-body\" id=\"' + ocId + '_body\">' + spinner + '</div>';
    document.body.appendChild(oc);
    function injectHtml(container, html) {
        container.innerHTML = '';
        var frag = document.createRange().createContextualFragment(html);
        container.appendChild(frag);
    }
    oc.addEventListener('show.bs.offcanvas', function () {
        var bd = document.getElementById(ocId + '_body');
        bd.innerHTML = spinner;
        fetch($js_url)
            .then(function (r) { return r.text(); })
            .then(function (html) { injectHtml(bd, html); })
            .catch(function () { bd.innerHTML = '<div class=\"alert alert-danger m-2\">Erreur de chargement</div>'; });
    });
    var trigger = document.getElementById(ocId + 'Link');
    if (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            bootstrap.Offcanvas.getOrCreateInstance(oc).show();
        });
    }
}());
</script>";

        if ($param['display']) {
            echo $out;
        } else {
            return $out;
        }
    }
}
