<?php

/*
 -------------------------------------------------------------------------
 Activity plugin for GLPI
 Copyright (C) 2019 by the Activity Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Activity.

 Activity is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Activity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Activity. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginActivityPlanning extends Planning {

   static function canView() {
      if (Session::getCurrentInterface() != 'central') {
         return true;
      }
      return Session::haveRightsOr(self::$rightname, [self::READMY, self::READGROUP,
                                                           self::READALL]);
   }



   static function showPlanning($fullview = true) {
      global $CFG_GLPI;

      $fullview_str = $fullview?"true":"false";

      $pl_height = "$(document).height()-280";
      if ($_SESSION['glpilayout'] == "vsplit") {
         $pl_height = "$('.ui-tabs-panel').height()-30";
      }

      $date_formats = [0 => 'YYYY MMM DD',
                           1 => 'DD MMM YYYY',
                           2 => 'MMM DD YYYY'];
      $date_format = $date_formats[$_SESSION["glpidate_format"]];

      self::initSessionForCurrentUser();

      if ($fullview) {
         Planning::showPlanningFilter();
         $default_view = "agendaWeek";
         $header = "{
            left:   'prev,next,today',
            center: 'title',
            right:  'month,agendaWeek,agendaDay,listYear'
         }";
      } else {
         $default_view = "listYear";
         $header = "false";
         $pl_height = "400";
      }

      echo "<div id='planning'></div>";
      echo Html::scriptBlock("
      $(document).ready(function() {
         var disable_qtip = false,
             disable_edit = false;
         $('html')
            .mousedown(function() {
               disable_qtip = true;
               $('.qtip').hide();
            })
            .mouseup(function() {
               disable_qtip = false;
            });

         var window_focused = true;
         var loaded = false;
         window.onblur = function() { window_focused = false; }
         window.onfocus = function() { window_focused = true; }

         $('#planning').fullCalendar({
            height:      $pl_height,
            theme:       true,
            weekNumbers: ".($fullview?'true':'false').",
            defaultView: '$default_view',
            timeFormat:  'H:mm',
            eventLimit:  true, // show 'more' button when too mmany events
            minTime:     '".$CFG_GLPI['planning_begin']."',
            maxTime:     '".$CFG_GLPI['planning_end']."',
            listDayAltFormat: false,
            windowResize: function(view) {
               $(this).fullCalendar('option', 'height', $pl_height);
            },
            header: $header,
            views: {
               month: {
                  titleFormat: '$date_format'
               },
               agendaWeek: {
                  titleFormat: '$date_format'
               },
               agendaDay: {
                  titleFormat: '$date_format'
               }
            },
            viewRender: function(view){ // on date changes, replicate to datepicker
               var currentdate = view.intervalStart;
               $('#planning_datepicker').datepicker('setDate', new Date(currentdate));
            },
            eventRender: function(event, element, view) {
               var eventtype_marker = '<span class=\"event_type\" style=\"background-color: '+event.typeColor+'\"></span>';
               element.find('.fc-content').after(eventtype_marker);
               element.find('.fc-list-item-title > a').prepend(eventtype_marker);

               var content = event.content;
               if(view.name !== 'month' && view.name !== 'listYear' && !event.allDay){
                  element
                     .append('<div class=\"content\">'+content+'</div>');
               }

               // add classes to current event
               if (typeof event.end !== 'undefined'
                   && event.end !== null) {
                  added_classes = event.end.isBefore(moment())      ? ' event_past'   : '';
                  added_classes+= event.end.isAfter(moment())       ? ' event_future' : '';
                  added_classes+= event.end.isSame(moment(), 'day') ? ' event_today'  : '';
               }
               if (event.state != '') {
                  added_classes+= event.state == 0 ? ' event_info'
                                : event.state == 1 ? ' event_todo'
                                : event.state == 2 ? ' event_done'
                                : '';
               }
               if (added_classes != '') {
                  element.addClass(added_classes);
               }

               // add tooltip to event
               if (!disable_qtip) {
                  var qtip_position = {
                     viewport: 'auto'
                  };
                  if (view.name === 'listYear') {
                     qtip_position.target= element.find('a');
                  }
                  element.qtip({
                     position: qtip_position,
                     content: content,
                     style: {
                        classes: 'qtip-shadow qtip-bootstrap'
                     },
                     show: {
                        solo: true,
                        delay: 400
                     },
                     hide: {
                        fixed: true,
                        delay: 100
                     },
                     events: {
                        show: function(event, api) {
                           if(!window_focused) {
                              event.preventDefault();
                           }
                        }
                     }
                  });
               }
            },
            viewRender: function(view, element) {
               // force refetch events from ajax on view change (don't refetch on firt load)
               if (loaded) {
                  $('#planning').fullCalendar('refetchEvents')
               }
            },
            eventAfterAllRender: function(view) {
               // set a var to force refetch events (see viewRender callback)
               loaded = true;

               // scroll div to first element needed to be viewed
               var scrolltoevent = $('#planning .event_past.event_todo').first();
               if (scrolltoevent.length == 0) {
                  scrolltoevent = $('#planning .event_today').first();
               }
               if (scrolltoevent.length == 0) {
                  scrolltoevent = $('#planning .event_future').first();
               }
               if (scrolltoevent.length == 0) {
                  scrolltoevent = $('#planning .event_past').last();
               }
               if (scrolltoevent.length) {
                  $('#planning .fc-scroller').scrollTop(scrolltoevent.prop('offsetTop')-25);
               }
            },
            eventSources: [{
               url:  '".$CFG_GLPI['root_doc']."/ajax/planning.php',
               type: 'POST',
               data: function() {
                  var view_name = $('#planning').fullCalendar('getView').name;
                  var display_done_events = 1;
                  if (view_name == 'listYear') {
                     display_done_events = 0;
                  }
                  return {
                     'action': 'get_events',
                     'display_done_events': display_done_events
                  };
               },
               success: function(data) {
                  if (!$fullview_str && data.length == 0) {
                     $('#planning').fullCalendar('option', 'height', 0);
                  }
               },
               error: function() {
                  console.log('there was an error while fetching events!');
               }
            }],

            // EDIT EVENTS
            editable: true, // we can drag and resize events
            eventResize: function(event, delta, revertFunc) {
               editEventTimes(event, revertFunc);
            },
            eventResizeStart: function() {
               disable_edit = true;
            },
            eventResizeStop: function() {
               setTimeout(function(){
                  disable_edit = false;
               }, 300);
            },
            eventDrop: function(event, delta, revertFunc) {
               editEventTimes(event, revertFunc);
            },
            eventClick: function(event) {
               if (event.ajaxurl && event.editable && !disable_edit) {
                  $('<div>')
                     .dialog({
                        modal:  true,
                        width:  'auto',
                        height: 'auto'
                     })
                     .load(event.ajaxurl, function() {
                        $(this).dialog('option', 'position', ['center', 'center'] );
                     });
                  return false;
               };
            },


            // ADD EVENTS
            selectable: true,
            /*selectHelper: function(start, end) {
               return $('<div class=\"planning-select-helper\" />').text(start+' '+end);
            },*/ // doesn't work anymore: see https://github.com/fullcalendar/fullcalendar/issues/2832
            select: function(start, end, jsEvent) {
               $('<div>').dialog({
                  modal:  true,
                  width:  'auto',
                  height: 'auto',
                  open: function () {
                      $(this).load(
                        '".$CFG_GLPI['root_doc']."/ajax/planning.php?action=add_event_fromselect',
                        {
                           begin: start.format(),
                           end:   end.format()
                        },
                        function() {
                           $(this).dialog('option', 'position', ['center', 'center'] );
                        }
                      );
                  },
                  position: {
                     my: 'center',
                     at: 'center',
                     viewport: $(window)
                  }
               });

               $('#planning').fullCalendar('unselect');
            }
         });


         // send ajax for event storage (on event drag/resize)
         var editEventTimes = function(event, revertFunc) {
            if (event._allDay) {
               var start = event.start.format()+'T00:00:00';
               var end = start;
               if (typeof event.end != 'undefined') {
                  if (event.end == null) {
                     end = $.fullCalendar.moment(event.start)
                              .add(1, 'days')
                              .format()+'T00:00:00';
                  } else {
                     end = event.end.format()+'T00:00:00';
                  }
               }

            } else {
               var start = event.start.format();
               if (event.end == null) {
                  var end = $.fullCalendar.moment(event.start)
                              .add(2, 'hours')
                              .format();
               } else {
                  var end = event.end.format();
               }
            }

            $.ajax({
               url:  '".$CFG_GLPI['root_doc']."/ajax/planning.php',
               type: 'POST',
               data: {
                  action:   'update_event_times',
                  start:    start,
                  end:      end,
                  itemtype: event.itemtype,
                  items_id: event.items_id
               },
               success: function(html) {
                  if (!html) {
                     revertFunc();
                  }
                  $('#planning').fullCalendar('updateEvent', event);
                  displayAjaxMessageAfterRedirect();
               },
               error: function() {
                  revertFunc();
               }
            });
         };

         // attach button (planning and refresh) in planning header
         $('#planning .fc-toolbar .fc-center h2')
            .after(
               $('<i id=\"refresh_planning\" class=\"fas fa-sync-alt fa-1x pointer\"></i>')
            ).after(
               $('<input type=\"hidden\" id=\"planning_datepicker\">')
            );

         $('#refresh_planning').click(function() {
            $('#planning').fullCalendar('refetchEvents')
         })

         // datepicker behavior
         $('#planning_datepicker').datepicker({
            changeMonth:     true,
            changeYear:      true,
            numberOfMonths:  3,
            showOn:          'both',
            buttonText: '<i class=\"far fa-calendar-alt\"></i>',
            dateFormat:      'DD, d MM, yy',
            onSelect: function(dateText, inst) {
               var selected_date = $(this).datepicker('getDate');
               $('#planning').fullCalendar('gotoDate', selected_date);
            }
         });
      });"
      );
      return;
   }

}
