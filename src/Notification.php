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

namespace GlpiPlugin\Activity;

use CommonDBTM;
use GLPIMailer;
use Log;
use Session;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use User;

class Notification extends CommonDBTM {

   /**
    * @var boolean activate the history for the plugin
    */
   public $dohistory = true;


    /**
     * @param $options   array
     **/
    public function sendNotification($options = [])
    {

        $transport = Transport::fromDsn(GLPIMailer::buildDsn(true));

        $mmail = new GLPIMailer($transport);
        $mail = $mmail->getEmail();

        $mail->getHeaders()->addTextHeader("Message-Id", $options['messageid']);

        $mail->from(new Address($options['from'], $options["fromname"]));

        if ($options['replyto']) {
            $mail->addReplyTo(new Address($options['replyto'], $options['replytoname']));
        }

        $mail->to(new Address($options['to'], $options['toname']));

        $mail->subject($options['subject']);

        $mail->html($options['content_html']);

        // Attach pdf to mail
        if (!empty($options['attachment'])) {
            foreach ($options['attachment'] as $attachment) {
                $mail->attachFromPath($attachment['filepath'], $attachment['name']);
            }
        }

        if (!$mmail->send()) {
            Session::addMessageAfterRedirect(
                __('Failed to send email to ' . $options['to']),
                false,
                ERROR
            );
            return false;
        } else {
//            if ((count($mail->to)) > 0) {
//                foreach ($mail->to as $to) {
//                    //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
//                    Toolbox::logInFile("mail", sprintf(
//                        __('%1$s: %2$s'),
//                        sprintf(__('An email was sent to %s'), $to[0]),
//                        $options['subject'] . "\n"
//                    ));
//                }
//            }
//            if ((count($mail->cc)) > 0) {
//                foreach ($mail->cc as $to) {
//                    //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
//                    Toolbox::logInFile("mail", sprintf(
//                        __('%1$s: %2$s'),
//                        sprintf(__('An email was sent to %s'), $to[0]),
//                        $options['subject'] . "\n"
//                    ));
//                }
//            }
//            if ((count($mail->bcc)) > 0) {
//                foreach ($mail->bcc as $to) {
//                    //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
//                    Toolbox::logInFile("mail", sprintf(
//                        __('%1$s: %2$s'),
//                        sprintf(__('An email was sent to %s'), $to[0]),
//                        $options['subject'] . "\n"
//                    ));
//                }
//            }
            return true;
        }
    }
   /**
    * @param $mailing_options
    **/
   static function send($mailing_options, $additional_options) {

      $mail = new self();
      $mail->sendNotification(array_merge($mailing_options, $additional_options));
   }

   static function sendComm($input) {
      global $_FILES, $CFG_GLPI;
      $send = false;

      // Subject
      $subject = $input['mail_subject'];
      // Body
      $body = $input['mail_body'];


      if ($subject == "") {
         Session::addMessageAfterRedirect(__('Please fill a subject', 'activity'), ERROR);

      } else if ($body == "") {
         Session::addMessageAfterRedirect(__('Please fill a mail body', 'activity'), ERROR);

      } else {
         // Envoi du mail
         $notificationMail = new self();
         $mail     = "";
         $user = new User();
         if ($user->getFromDB($input['validate_id'])) {
            $validate_email = $user->getDefaultEmail();
            $validate_name = getUserName($input['validate_id']);
         }


         $option = new Option();
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
            Log::history($input["id"], Holiday::getType(), $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
         } else {
            $opt[0] = 0;
            $opt[1] = __('Failed Mail send', 'activity');
            $opt[2] = sprintf(__('Fail to send a mail to %s', 'activity'), $mail);
            Log::history($input["id"], Holiday::getType(), $opt, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
         }
      }
      return $send;
   }
}

