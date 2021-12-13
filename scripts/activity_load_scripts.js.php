<?php
use Glpi\Event;
include('../../../inc/includes.php');
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
            $('.ms-md-4').before("\<a class='ti fa-1x ti-calendar-event' href='#' id='showLateralMenuLink'></a>");
         }

         //            });
      }

      return this;
   };
}(jQuery));

$(document).activity_load_scripts();
