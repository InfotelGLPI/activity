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

header('Content-Type: text/javascript');

?>

var root_activity_doc = "<?php echo PLUGIN_ACTIVITY_WEBDIR; ?>";
(function ($) {
   $.fn.activity_load_scripts = function () {

      init();

      // Build the lateral activity offcanvas from plain JSON parameters.
      // All text is injected with textContent (never innerHTML) so titles and
      // labels cannot introduce markup.
      function buildSlidePanel(cfg) {
         var ocId = cfg.name;
         if (document.getElementById(ocId)) {
            return;
         }
         var oc = document.createElement('div');
         oc.id = ocId;
         oc.className = 'offcanvas ' + (cfg.position === 'left' ? 'offcanvas-start' : 'offcanvas-end');
         oc.tabIndex = -1;
         oc.setAttribute('data-bs-scroll', 'true');

         var spinner = '<div class="text-center p-4"><div class="spinner-border" role="status"></div></div>';

         var header = document.createElement('div');
         header.className = 'offcanvas-header border-bottom';
         var titleEl = document.createElement('h5');
         titleEl.className = 'offcanvas-title';
         titleEl.textContent = cfg.title;
         var closeBtn = document.createElement('button');
         closeBtn.type = 'button';
         closeBtn.className = 'btn-close';
         closeBtn.setAttribute('data-bs-dismiss', 'offcanvas');
         closeBtn.setAttribute('aria-label', cfg.close_label);
         header.appendChild(titleEl);
         header.appendChild(closeBtn);

         var body = document.createElement('div');
         body.className = 'offcanvas-body';
         body.id = ocId + '_body';
         body.innerHTML = spinner;

         oc.appendChild(header);
         oc.appendChild(body);
         document.body.appendChild(oc);

         oc.addEventListener('show.bs.offcanvas', function () {
            body.innerHTML = spinner;
            fetch(cfg.url)
               .then(function (r) { return r.text(); })
               .then(function (html) {
                  body.innerHTML = '';
                  var frag = document.createRange().createContextualFragment(html);
                  body.appendChild(frag);
               })
               .catch(function () {
                  body.innerHTML = '<div class="alert alert-danger m-2">Erreur de chargement</div>';
               });
         });

         var trigger = document.getElementById(ocId + 'Link');
         if (trigger) {
            trigger.addEventListener('click', function (e) {
               e.preventDefault();
               bootstrap.Offcanvas.getOrCreateInstance(oc).show();
            });
         }
      }

      // Start the plugin
      function init() {

         // Request data as JSON and act on it directly — no eval() of server output.
         $.ajax({
            url: root_activity_doc + '/ajax/loadscripts.php',
            type: "POST",
            dataType: "json",
            data: 'action=load',
            success: function (response) {
               if (!response) {
                  return;
               }
               if (response.lang_month && typeof changeClickTodayActivity === 'function') {
                  changeClickTodayActivity({lang_month: response.lang_month});
               }
               if (response.slidepanel) {
                  buildSlidePanel(response.slidepanel);
               }
            }
         });
         if($("#showLateralMenuLink").length === 0) {
            $('.ms-md-4').before("\<a class='ti ti-calendar-event' href='#' id='showLateralMenuLink'></a>");
         }
      }

      return this;
   };
}(jQuery));

$(document).activity_load_scripts();
