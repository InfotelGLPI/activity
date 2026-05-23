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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Activity\Report;

Session::checkLoginUser();

if (isset($_GET["file"])) { // for other file
   $splitter = explode("/", $_GET["file"]);

   if (count($splitter) == 3) {
      $send = false;
      if (
         ($splitter[1] == "activity")
         && Session::haveRight("plugin_activity_statistics", READ)
      ) {
         $send = GLPI_DOC_DIR . "/" . $_GET["file"];
      }
      if ($send && file_exists($send)) {
         $real = realpath($send);
         $base = realpath(GLPI_DOC_DIR);
         if ($real === false || $base === false || strpos($real, $base) !== 0) {
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
