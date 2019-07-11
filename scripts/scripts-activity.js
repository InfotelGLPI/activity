function showTicketTaskOnCRA(options) {
    //################## TICKET TASK ################################################################
    $(document).ready(function () {
        // Only in ticket.php
      if (location.pathname.indexOf('ticket.form.php') > 0) {
          // get tickets_id
          var tickets_id = getUrlParam(window.location.href, 'id');
          //only in edit form
         if (tickets_id == undefined || tickets_id == 0) {
             return;
         }

          // Launched on each complete Ajax load
          $(document).ajaxComplete(function (event, xhr, option) {
              setTimeout(function () {
                  // We execute the code only if the ticket form display request is done
               if (option.data != undefined) {
                   var tid;

                   // Get the right tab
                  if (getUrlParam(option.data, 'type') == 'TicketTask'
                           && (option.url.indexOf("ajax/timeline.php") != -1 || option.url.indexOf("ajax/viewsubitem.php") != -1)) {

                      var taskId = getUrlParam(option.data, '&id');
                      var tid = 0;
                     if ((taskId != undefined)) {
                         tid = taskId;
                     }

                     if (tid == -1) {
                        $.ajax({
                           url: options.root_doc + '/plugins/activity/ajax/tickettask.php',
                           type: "POST",
                           dataType: "html",
                           data: {
                              'tickets_id': tickets_id,
                              'tickettasks_id': tid,
                              'action': 'showTicketTaskOnCra'
                           },
                           success: function (response, opts) {
                               var inputAdd = $("input[name='add']");
                               var taskForm = $("input[name='add']").closest("tr").prev("tr");
                              if ($("div[name='is_oncra_"+tid+"']").length == 0) {
                                  $(response).insertAfter(taskForm);
                              }

                                 var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                              while (scripts = scriptsFinder.exec(response)) {
                                 eval(scripts[1]);
                              }
                           }
                             });
                     } else {
                         $.ajax({
                              url: options.root_doc + '/plugins/activity/ajax/tickettask.php',
                              type: "POST",
                              dataType: "html",
                              data: {
                                 'tickets_id': tickets_id,
                                 'tickettasks_id': tid,
                                 'action': 'showTicketTaskOnCra'
                              },
                              success: function (response, opts) {

                                  var taskForm = $("input[value='"+tid+"']").prev("tr");
                                 if ($("div[name='is_oncra_"+tid+"']").length == 0) {
                                     $(response).insertBefore(taskForm);
                                 }

                                  var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                                 while (scripts = scriptsFinder.exec(response)) {
                                     eval(scripts[1]);
                                 }
                              }
                                 });
                     }
                  }

                     // Get the right tab
                  if (getUrlParam(option.data, 'type') == 'TicketTask'
                           && option.url.indexOf("ajax/planning.php") != -1) {

                      var taskId = getUrlParam(option.data, '&id');
                      var tid = 0;
                     if ((taskId != undefined)) {
                         tid = taskId;
                     }

                         $.ajax({
                              url: options.root_doc + '/plugins/activity/ajax/tickettask.php',
                              type: "POST",
                              dataType: "html",
                              data: {
                                 'tickets_id': tickets_id,
                                 'tickettasks_id': tid,
                                 'action': 'showPlanningDuration'
                              },
                              success: function (response, opts) {
                                 var planDuration = $("select[id*='plan__duration']").closest("td");
                                 if (planDuration.length != 0) {
                                     planDuration.html(response);
                                 }

                                 var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                                 while (scripts = scriptsFinder.exec(response)) {
                                     eval(scripts[1]);
                                 }
                              }
                              });
                  }
               }
              }, 100);
          }, this);
      } else if (location.pathname.indexOf('planning.php') > 0) {

          // Launched on each complete Ajax load
          $(document).ajaxComplete(function (event, xhr, option) {

              setTimeout(function () {
                  var tid;

                  // Get the right tab
               if ((option.url.indexOf("ajax/planning.php") != -1 && option.url.indexOf("front/ticket.form.php") != -1)) {
                   var taskId = getUrlParam(option.url, '&id');
                   var tickets_id = getUrlParam(option.url, '?id');
                   var tid = 0;
                  if ((taskId != undefined)) {
                      tid = taskId;
                  }
                     $.ajax({
                        url: options.root_doc + '/plugins/activity/ajax/tickettask.php',
                        type: "POST",
                        dataType: "html",
                        data: {
                           'tickets_id': tickets_id,
                           'tickettasks_id': tid,
                           'action': 'showTicketTaskOnCra'
                        },
                        success: function (response, opts) {
                            var taskForm = $("input[value='"+tid+"']").prev("tr");
                           if ($("div[name='is_oncra_"+tid+"']").length == 0) {
                               $(response).insertBefore(taskForm);
                           }

                              var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                           while (scripts = scriptsFinder.exec(response)) {
                              eval(scripts[1]);
                           }
                        }
                        });
               }
              }, 100);
          }, this);
      }
    });
}

function getUrlParam(url, name) {
    var results = new RegExp('[?&]?' + name + '=([^&]+)(&|$)').exec(url);
   if (results != null) {
       return results[1] || 0;
   }

    return undefined;
}