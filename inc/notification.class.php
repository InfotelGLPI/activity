<?php

/* 
 -------------------------------------------------------------------------
 Ticketreports plugin for GLPI
 Copyright (C) 2003-2016 by the Ticketreports Development Team.

 https://github.com/InfotelGLPI/ticketreports
 -------------------------------------------------------------------------

 LICENSE
      
 This file is part of Ticketreports.

 Ticketreports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Ticketreports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Ticketreports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

class PluginActivityNotification extends CommonDBTM {

   /**
    * @var boolean activate the history for the plugin
    */
   public $dohistory = true;

   /**
    * @param $mailing_options
   **/
   static function send($mailing_options, $additional_options) {

      $mail = new PluginTicketreportsNotificationMail();
      $mail->sendNotification(array_merge($mailing_options, $additional_options));
      $mail->ClearAddresses();
   }
   
   static function sendComm($input) {
      global $CFG_GLPI, $_FILES;
      $send = false;
      $date = date("Y-m-d H:i:s");



      // Subject
      $subject  = $input['mail_subject'];
      // Body
      $body     = $input['mail_body'];



      if ($subject == "") {
         Session::addMessageAfterRedirect(__('Please fill a subject', 'ticketreports'), Messages::MESSAGE_ERROR);
         
      } else if ($body == "") {
         Session::addMessageAfterRedirect(__('Please fill a mail body', 'ticketreports'), Messages::MESSAGE_ERROR);
         
      } else {
         // Envoi du mail
         $notificationMail = new PluginActivityNotificationMail();

         $cptMails = 0;
         $mails    = array();
         $mail     = "";
        



//               // Responsible mail
//               if (isset($input['cbs_responsible'])) {
//                  $report = new PluginTicketreportsReport();
//                  if($report->getFromDBByQuery('WHERE `tickets_id` = '. $input['tickets_id'] )){
//                     if($report->fields['responsible_id'] != 0){
//                        $user = new User();
//                        if($user->getFromDB($report->fields['responsible_id'])){
//                           $user_email = $user->getDefaultEmail();
//                           if(!in_array($user_email, $mails)){
//                              $notificationMail->AddAddress($user_email, getUserName($report->fields['responsible_id']));
//                              $mail.= " " . $user_email . ",";
//                              $cptMails++;
//                              $mails[] = $user_email;
//                           }
//                        }
//
//                     }
//                  }
//
//               }

         $user = new User();
         if($user->getFromDB($input['users_id'])){
            $user_email = $user->getDefaultEmail();
            if(!in_array($user_email, $mails)){
               $notificationMail->addCC($user_email, getUserName($input['validate_id']));
               $mail.= " " . $user_email . ",";
               $cptMails++;
               $mails[] = $user_email;
            }
         }


         $option = new PluginActivityOption();
         $option->getFromDB(1);
         $mail.= $option->getField('used_mail_for_holidays');
//          $config = new PluginTicketreportsConfig();
//            if($config->getConfigFromDB()){
//               $emails = $config->fields['email'];
//               if(!empty($emails)){
//                  $array_emails = explode(';', $emails);
//                  foreach($array_emails as $email){
//                     $notificationMail->addBCC($email, 'admin');
//                  }
//               }
//            }
            $options = array(
                'to'           => $option->getField('used_mail_for_holidays'),
                'toname'       => $option->getField('used_mail_for_holidays'),
                'from'         => $CFG_GLPI['admin_email'],
                'fromname'     => $CFG_GLPI['admin_email_name'],
                'replyto'      => $CFG_GLPI['admin_email'],
                'replytoname'  => $CFG_GLPI['admin_email_name'],
                'subject'      => stripslashes($subject),
                'content_html' => htmlspecialchars_decode(stripcslashes($body)),
                'content_text' => $body
            );
            
//            // Get other files attachment
//            if (isset($_FILES['filename'])) {
//               if (is_array($_FILES['filename']['name'])) {
//                  foreach ($_FILES['filename']['name'] as $key => $filename) {
//                     if (!empty($filename)) {
//                        $options['attachment'][] = array('name'     => $filename,
//                                                         'filepath' => $_FILES['filename']['tmp_name'][$key]);
//                     }
//                  }
//               } else {
//                  $options['attachment'][] = array('name'     => $_FILES['filename']['name'],
//                                                   'filepath' => $_FILES['filename']['tmp_name']);
//               }
//            }


//            $document_item = new Document_Item();
//
//            if($document_item->getFromDBByCrit(["items_id"=>$input["id"],'itemtype'=> PluginActivityHoliday::getType()])){
//                  $document = new Document();
//                  $document->getFromDB($document_item->getField('documents_id'));
//                  $options['attachment'][] = array('name'     => $document->fields['filename'],
//                                                   'filepath' => $document->fields['filepath']);
//
//            }
         $options['attachment'][] = array('name'     => $input['filename'],
                                          'filepath' => $input['filepath']);
            $send = $notificationMail->sendNotification($options);

            $mail = rtrim($mail, ",");

            // Historisation
            $opt[0] = 0;
            $opt[1] = __('Mail sent', 'activity');
            $opt[2] = sprintf(__('A mail has been sent to %s', 'activity'), $mail);

            if ($send) {
               Session::addMessageAfterRedirect(sprintf(__('A mail has been sent to %s', 'activity'), $mail));
               Log::history($input["id"],  PluginActivityHoliday::getType(), $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            } else {
               $opt[0] = 0;
               $opt[1] = __('Failed Mail send', 'activity');
               $opt[2] = sprintf(__('Fail to send a mail to %s', 'activity'), $mail);
               Log::history($input["id"], PluginActivityHoliday::getType(), $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            }


      }
      return $send;
   }
   
   
//   static function sendModel($input) {
//      global $CFG_GLPI, $_FILES;
//
//      $date = date("Y-m-d H:i:s");
//
//      $commmodel = PluginTicketreportsCommunicationModel::getInstance();
//
//      $model = new PluginTicketreportsModel();
//      if ($model->getfromDB($input["models_id"])) {
//
//         $translation = new NotificationTemplateTranslation();
//         $translation->getFromDBByQuery("WHERE `notificationtemplates_id`=".$model->fields['notificationtemplates_id']);
//
//         $commmodel->setCurrentNotifTemplateTranslation($translation);
//         $tags = $commmodel->getTranslatedTags();
//
//         $subject = "";
//         $body = "";
//         $body_text = "";
//
//         if ($tags != false){
//            $subject = NotificationTemplate::process($translation->fields['subject'],$tags);
//            $body_html = NotificationTemplate::process($translation->fields['content_html'],$tags);
//            $body_text = NotificationTemplate::process($translation->fields['content_text'],$tags);
//         }
//
//         if ($subject == "") {
//            Session::addMessageAfterRedirect(__('Please fill a subject', 'ticketreports'), Messages::MESSAGE_ERROR);
//
//         } else if ($body == "" && $body_text == "") {
//            Session::addMessageAfterRedirect(__('Please fill a mail body', 'ticketreports'), Messages::MESSAGE_ERROR);
//
//         } else {
//            // Envoi du mail
//            $notificationMail = new PluginTicketreportsNotificationMail();
//
//            $cptMails = 0;
//            $mails    = array();
//            $mail     = "";
//
//
//           $recipients = json_decode($model->fields['recipients'], true);
//
//            // Contact mail
//            if (count($recipients) > 0
//                  && isset($input['tickets_id'])) {
//
//               $ticket = new Ticket();
//               if ($ticket->getFromDB($input['tickets_id'])) {
//
//                  $notificationtarget = NotificationTargetCommonITILObject::getInstance($ticket,"new");
//
//                  $notificationtarget->addAdditionnalInfosForTarget();
//
//                  foreach ($recipients as $id => $recipient) {
//
//                     $data = array('type' => $recipient['type'],
//                                    'items_id' => $recipient['items_id']);
//                     $options = array('item' => $ticket);
//
//                     $notificationtarget->getAddressesByTarget($data, $options);
//
//                     foreach ($notificationtarget->getTargets() as $user_email => $users_infos) {
//
//                        if ($user_email != "" && !in_array($user_email, $mails)) {
//                           $notificationMail->AddAddress($user_email, $users_infos['username']);
//                           $mail.= " ".$user_email.",";
//                           $cptMails++;
//                        }
//
//                        $mails[] = $user_email;
//                     }
//                  }
//               }
//            }
//
//            // No mails selected
//            if ($cptMails == 0) {
//               Session::addMessageAfterRedirect(__('There is no emails associated to this mail', 'ticketreports'), Messages::MESSAGE_INFO);
//            } else {
//               $config = new PluginTicketreportsConfig();
//               $config->getConfigFromDB();
//               $emails = $config->fields['email'];
//               if (!empty($emails)) {
//                  $array_emails = explode(';', $emails);
//                  foreach ($array_emails as $email) {
//                     $notificationMail->addBCC($email, 'admin');
//                  }
//               }
//               $options = array(
//                  'to'           => null,
//                   'toname'       => null,
//                   'from'         => $CFG_GLPI['admin_email'],
//                   'fromname'     => $CFG_GLPI['admin_email_name'],
//                   'replyto'      => $CFG_GLPI['admin_email'],
//                   'replytoname'  => $CFG_GLPI['admin_email_name'],
//                   'subject'      => $subject,
//                   'content_html' => $body_html,
//                   'content_text' => $body_text
//               );
//
//               // Get other files attachment
//               //if (isset($_FILES['filename'])) {
//               //if (is_array($_FILES['filename']['name'])) {
//               //   foreach ($_FILES['filename']['name'] as $key => $filename) {
//               //      if (!empty($filename)) {
//               //         $options['attachment'][] = array('name'     => $filename,
//               //                                          'filepath' => $_FILES['filename']['tmp_name'][$key]);
//               //      }
//               //   }
//               //} else {
//               //   $options['attachment'][] = array('name'     => $_FILES['filename']['name'],
//               //                                    'filepath' => $_FILES['filename']['tmp_name']);
//               //}
//               //}
//               // Get ticket files attachment
//
//               if (isset($model->fields['send_cri'])
//                     && $model->fields['send_cri'] == 1) {
//                     //TODO
//                  $doc_item = new PluginTicketreportsDocument();
//                  $data     = $doc_item->find("`tickets_id` = '".$input['tickets_id']."'");
//                  foreach ($data as $val) {
//                     $doc = new Document();
//                     $doc->getFromDB($val['documents_id']);
//                     $options['attachment'][] = array('name'     => $doc->fields['filename'],
//                                                      'filepath' => GLPI_DOC_DIR.'/'.$doc->fields['filepath']);
//                  }
//               }
//
//               $send = $notificationMail->sendNotification($options);
//
//               $mail = rtrim($mail, ",");
//
//               // Historisation
//               $opt[0] = 0;
//               $opt[1] = __('Mail sent', 'ticketreports');
//               $opt[2] = sprintf(__('A mail has been sent to %s', 'ticketreports'), $mail);
//
//               if ($send) {
//                  Session::addMessageAfterRedirect(sprintf(__('A mail has been sent to %s', 'ticketreports'), $mail));
//                  Log::history($input['tickets_id'], 'Ticket', $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
//               } else {
//                  Log::history($input['tickets_id'], 'Ticket', $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
//               }
//               $commmodel->unsetSerializedSession();
//            }
//         }
//      }
//   }
}

