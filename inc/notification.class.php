<?php

/*
 -------------------------------------------------------------------------
 Activity plugin for GLPI
 Copyright (C) 2019-2022 by the Activity Development Team.
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

class PluginActivityNotification extends CommonDBTM {

   /**
    * @var boolean activate the history for the plugin
    */
   public $dohistory = true;

   /**
    * @param $mailing_options
    **/
   static function send($mailing_options, $additional_options) {

      $mail = new PluginActivityNotificationMail();
      $mail->sendNotification(array_merge($mailing_options, $additional_options));
      $mail->ClearAddresses();
   }

   static function sendComm($input) {
      global $_FILES, $CFG_GLPI;
      $send = false;

      // Subject
      $subject = $input['mail_subject'];
      // Body
      $body = $input['mail_body'];


      if ($subject == "") {
         Session::addMessageAfterRedirect(__('Please fill a subject', 'activity'), Messages::MESSAGE_ERROR);

      } else if ($body == "") {
         Session::addMessageAfterRedirect(__('Please fill a mail body', 'activity'), Messages::MESSAGE_ERROR);

      } else {
         // Envoi du mail
         $notificationMail = new PluginActivityNotificationMail();

         $mails    = [];
         $mail     = "";

         $user = new User();
         if ($user->getFromDB($input['users_id'])) {
            $user_email = $user->getDefaultEmail();
            if (!in_array($user_email, $mails)) {
               $notificationMail->addCC($user_email, getUserName($input['users_id']));
               $mail .= " " . $user_email . ",";
               $mails[] = $user_email;
            }
         }

         $user = new User();
         if ($user->getFromDB($input['validate_id'])) {
            $validate_email = $user->getDefaultEmail();
            $validate_name = getUserName($input['validate_id']);
            if (!in_array($validate_email, $mails)) {
               $notificationMail->addCC($validate_email, $validate_name);
               $mail .= " " . $validate_email . ",";
               $mails[] = $validate_email;
            }
         }


         $option = new PluginActivityOption();
         $option->getFromDB(1);
         $mail .= $option->getField('used_mail_for_holidays');

         $options = [
            'to'           => $option->getField('used_mail_for_holidays'),
            'toname'       => $option->getField('used_mail_for_holidays'),
            'from'         => $CFG_GLPI["from_email"],
            'fromname'     => $CFG_GLPI["from_email_name"],
            'replyto'      => $validate_email,
            'replytoname'  => $validate_name,
            'subject'      => stripslashes($subject),
            'content_html' => htmlspecialchars_decode(stripcslashes($body)),
            'content_text' => $body
         ];

         $options['attachment'][] = ['name'     => $input['filename'],
                                          'filepath' => $input['filepath']];

         $send = $notificationMail->sendNotification($options);

         $mail = rtrim($mail, ",");
         // Historisation
         $opt[0] = 0;
         $opt[1] = __('Mail sent', 'activity');
         $opt[2] = sprintf(__('A mail has been sent to %s', 'activity'), $mail);

         if ($send) {
            Session::addMessageAfterRedirect(sprintf(__('A mail has been sent to %s', 'activity'), $mail));
            Log::history($input["id"], PluginActivityHoliday::getType(), $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
         } else {
            $opt[0] = 0;
            $opt[1] = __('Failed Mail send', 'activity');
            $opt[2] = sprintf(__('Fail to send a mail to %s', 'activity'), $mail);
            Log::history($input["id"], PluginActivityHoliday::getType(), $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
         }
      }
      return $send;
   }
}

