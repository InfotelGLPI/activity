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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Activity\Config;
use GlpiPlugin\Activity\Menu;
use GlpiPlugin\Activity\Option;

if (Plugin::isPluginActive("activity")) {
    if (Session::haveRight("plugin_activity", UPDATE)) {
        $opt = new Option();

        if (isset($_POST["update"])) {
            Session::checkRight("config", UPDATE);
            $opt->update($_POST);
            Html::back();

        } elseif (isset($_POST["add_config"])) {
            Session::checkRight("config", UPDATE);
            $config = new Config();
            if (isset($_POST['entities_id'])) {
                $config->add($_POST);
            }
            Html::back();

        } elseif (isset($_POST["delete_item"])) {
            Session::checkRight("config", UPDATE);
            $config = new Config();
            foreach ($_POST["item"] as $key => $val) {
                if ($val != 0) {
                    $config->delete(['id' => $key]);
                }
            }
            Html::back();

        } else {
            Html::header(__('Setup'), '', "tools", Menu::class);
            $opt->getFromDB(1);
            $opt->display(['id' => 1]);
            Html::footer();
        }
    } else {
        throw new AccessDeniedHttpException();
    }
} else {
    Html::header(__s('Setup'), '', "config", "plugin");
    TemplateRenderer::getInstance()->display('@activity/plugin_inactive.html.twig', [
        'message' => __('Please activate the plugin', 'activity'),
    ]);
    Html::footer();
}
