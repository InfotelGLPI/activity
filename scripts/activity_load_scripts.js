/**
 *  Load plugin scripts on page start
 */
(function ($) {
   $.fn.activity_load_scripts = function () {

      init();

      // Start the plugin
      function init() {
         //            $(document).ready(function () {
         var path = 'plugins/activity/';
         var url = window.location.href.replace(/front\/.*/, path);
         if (window.location.href.indexOf('plugins') > 0) {
            url = window.location.href.replace(/plugins\/.*/, path);
         }
         if (window.location.href.indexOf('marketplace') > 0) {
            url = window.location.href.replace(/marketplace\/.*/, path);
         }

         // Send data
         $.ajax({
            url: url+'ajax/loadscripts.php',
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
         if($("#activity_link").length === 0) {
            $("#c_preference ul #bookmark_link").before("\<li id='activity_link'><a class='fa fa-2x fa-calendar-alt' href='#' id='showLateralMenuLink'></a></li>");
         }
         //            });
      }

      return this;
   };
}(jQuery));

$(document).activity_load_scripts();
