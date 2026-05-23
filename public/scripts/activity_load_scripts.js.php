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

header('Content-Type: text/javascript');

?>

var root_activity_doc = "<?php echo PLUGIN_ACTIVITY_WEBDIR; ?>";
(function ($) {
   $.fn.activity_load_scripts = function () {

      init();

      // Start the plugin
      function init() {

         // Send data
         $.ajax({
            url: root_activity_doc + '/ajax/loadscripts.php',
            type: "POST",
            dataType: "html",
            data: 'action=load',
            success: function (response, opts) {
               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts = scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
         if($("#showLateralMenuLink").length === 0) {
            $('.ms-md-4').before("\<a class='ti ti-calendar-event' href='#' id='showLateralMenuLink'></a>");
         }

         //            });
      }

      return this;
   };
}(jQuery));

$(document).activity_load_scripts();
