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
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Activity\Report;

if (isset($_GET["file"])) { // for other file
    $splitter = explode("/", $_GET["file"]);

    if (count($splitter) == 3) {
        $send = false;
        if (
            ($splitter[1] == "activity")
            && Session::haveRight("plugin_activity_statistics", READ)
        ) {
            // Security: plugin_activity_statistics is the nominal right of anyone
            // allowed to produce their own CRA, not an administration right, and the
            // PDF directory is shared by the whole instance. Authorising on the right
            // alone let any user read every colleague's report, so the ownership
            // encoded in the file name is checked here as well.
            $owner = Report::craPdfOwner($splitter[2]);
            if (
                $owner === null
                || ($owner !== (int) Session::getLoginUserID()
                    && !Session::haveRight("plugin_activity_all_users", 1))
            ) {
                throw new AccessDeniedHttpException();
            }
            $send = GLPI_DOC_DIR . "/" . $_GET["file"];
        }
        if ($send && file_exists($send)) {
            $real = realpath($send);
            // Only $splitter[1] used to be constrained, so the first segment was free:
            // any subdirectory of GLPI_DOC_DIR holding an "activity" folder was a valid
            // source. Confine to the single directory CRA reports are written to rather
            // than to GLPI_DOC_DIR at large -- the narrowest base that still serves
            // every legitimate file. The trailing separator matters: a bare prefix match
            // would let a sibling directory sharing the prefix (e.g. "activity_backup")
            // slip through the containment check.
            $base = realpath(Report::craPdfDir());
            if ($real === false || $base === false
                || !str_starts_with($real, rtrim($base, '/\\') . DIRECTORY_SEPARATOR)) {
                throw new BadRequestHttpException(__('Unauthorized access to this file'), true);
            }
            $doc = new Document();
            $doc->fields['filepath'] = $_GET["file"];
            $doc->fields['mime'] = 'application/pdf';
            $doc->fields['filename'] = $splitter[2];
            $report = new Report();
            $report->send($doc);
        } else {
            throw new BadRequestHttpException(__('Unauthorized access to this file'), true);
        }
    } else {
        throw new BadRequestHttpException(__('Invalid filename'), true);
    }
}
