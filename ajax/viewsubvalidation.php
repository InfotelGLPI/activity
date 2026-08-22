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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Activity\Holiday;
use GlpiPlugin\Activity\HolidayValidation;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight('plugin_activity_can_validate', READ);

if (!isset($_POST['type'])) {
    throw new NotFoundHttpException();
}
if (!isset($_POST['parenttype'])) {
    throw new NotFoundHttpException();
}

$allowed_types = [HolidayValidation::class, Holiday::class];
if (!in_array($_POST['type'], $allowed_types, true) || !in_array($_POST['parenttype'], $allowed_types, true)) {
    http_response_code(400);
    return;
}

$dbu = new DbUtils();
if (($item = $dbu->getItemForItemtype($_POST['type']))
    && ($parent = $dbu->getItemForItemtype($_POST['parenttype']))) {

    if (isset($_POST[$parent->getForeignKeyField()])
        && isset($_POST["id"])
        && $parent->getFromDB($_POST[$parent->getForeignKeyField()])) {
        $id = (int) $_POST["id"];
        // showForm() does not enforce rights on its own: the canonical per-record
        // read gate now lives in Holiday::canViewItem()/HolidayValidation::
        // canViewItem(), so can($id, READ) already scopes access to the owner /
        // designated validator.
        if (!$item->can($id, READ)) {
            throw new AccessDeniedHttpException();
        }
        // Belt-and-suspenders: replay at this parallel AJAX sink the exact
        // per-record guard of front/holidayvalidation.form.php so a validation
        // record cannot be read by anyone but a designated validator of the
        // target holiday or the holiday requester (HR data leak, IDOR).
        if ($item instanceof HolidayValidation) {
            if (!$item->getFromDB($id)) {
                throw new NotFoundHttpException();
            }
            $holidays_id = (int) $item->fields['plugin_activity_holidays_id'];
            $holiday     = new Holiday();
            $is_requester = $holiday->getFromDB($holidays_id)
                && (int) $holiday->fields['users_id'] === Session::getLoginUserID();
            if (!HolidayValidation::canValidate($holidays_id)
                && !$is_requester
                && !Session::haveRight('plugin_activity_all_users', 1)) {
                throw new AccessDeniedHttpException();
            }
        }
        $item->showForm($id, ['parent' => $parent]);

    } else {
        throw new AccessDeniedHttpException();
    }
}
