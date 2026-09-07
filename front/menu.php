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

use GlpiPlugin\Activity\Menu;
use GlpiPlugin\Activity\PlanningExternalEvent;
use GlpiPlugin\Activity\Holiday;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;

$can = Session::haveRight("plugin_activity", READ);
$canholiday = Session::haveRight("plugin_activity_can_requestholiday", 1);
$canvalidateholiday = Session::haveRight("plugin_activity_can_validate", 1);

if (!$can
    && !$canholiday
    && !$canvalidateholiday) {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() == 'central') {
    Html::header(PlanningExternalEvent::getTypeName(2), '', "tools", Menu::class);
} else {
    Html::helpHeader(PlanningExternalEvent::getTypeName(2));
}

$blocks = [];

if ($can) {
    $blocks[] = PlanningExternalEvent::menu(
        PlanningExternalEvent::class,
        PlanningExternalEvent::getActionsOn(),
    );
}
if ($canholiday
         || $canvalidateholiday) {
    $blocks[] = PlanningExternalEvent::menu(
        Holiday::class,
        Holiday::getActionsOn(),
    );
}

TemplateRenderer::getInstance()->display('@activity/menu.html.twig', [
    'title'  => _n('Activity', 'Activities', 2, 'activity'),
    'blocks' => $blocks,
]);

if (!$can && !$canholiday  && !$canvalidateholiday) {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() == 'central') {
    Html::footer();
} else {
    Html::helpFooter();
}
