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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginActivityNotificationMail extends \PHPMailer\PHPMailer\PHPMailer {

   //! mailing type (new,attrib,followup,finish)
   var $mailtype = NULL;
   /** Job class variable - job to be mailed
    * @see Job
    */
   var $job = NULL;
   /** User class variable - user who make changes
    * @see User
    */
   var $user = NULL;
   /// Is the followupadded private ?
   var $followupisprivate = NULL;

   /// Set default variables for all new objects
   var $WordWrap = 80;
   /// Defaut charset
   var $CharSet = "utf-8";


   /**
    * Constructor
    **/
   function __construct() {
      global $CFG_GLPI;

      // Comes from config
      $this->SetLanguage("en", Config::getLibraryDir("PHPMailer") . "/language/");

      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $this->Mailer = "smtp";
         $this->Host   = $CFG_GLPI['smtp_host'].':'.$CFG_GLPI['smtp_port'];

         if ($CFG_GLPI['smtp_username'] != '') {
            $this->SMTPAuth = true;
            $this->Username = $CFG_GLPI['smtp_username'];
            $this->Password = Toolbox::sodiumDecrypt($CFG_GLPI['smtp_passwd']);
         }

         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPSSL) {
            $this->SMTPSecure = "ssl";
         }

         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPTLS) {
            $this->SMTPSecure = "tls";
         }

         if (!$CFG_GLPI['smtp_check_certificate']) {
            $this->SMTPOptions = ['ssl' => ['verify_peer'       => false,
                                            'verify_peer_name'  => false,
                                            'allow_self_signed' => true]];
         }
         if ($CFG_GLPI['smtp_sender'] != '') {
            $this->Sender = $CFG_GLPI['smtp_sender'];
         }
      }

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         $this->SMTPDebug = SMTP::DEBUG_CONNECTION;
         $this->Debugoutput = function ($message, $level) {
            Toolbox::logInFile(
               'mail-debug',
               "$level - $message"
            );
         };
      }

      $this->AddCustomHeader("Auto-Submitted: auto-generated");
      // For exchange
      $this->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");
   }


   /**
    * Determine if email is valid
    *
    * @param $address         email to check
    * @param $options   array of options used (by default 'checkdns'=>false)
    *     - checkdns :check dns entry
    *
    * @return boolean
    * from http://www.linuxjournal.com/article/9585
    **/
   static function isUserAddressValid($address, $options = ['checkdns' => false]) {

      $checkdns = $options['checkdns'];
      $isValid  = true;
      $atIndex  = strrpos($address, "@");

      if (is_bool($atIndex) && !$atIndex) {
         $isValid = false;

      } else {
         $domain    = substr($address, $atIndex + 1);
         $local     = substr($address, 0, $atIndex);
         $localLen  = strlen($local);
         $domainLen = strlen($domain);

         if (($localLen < 1) || ($localLen > 64)) {
            // local part length exceeded
            $isValid = false;
         } else if (($domainLen < 1) || ($domainLen > 255)) {
            // domain part length exceeded
            $isValid = false;
         } else if (($local[0] == '.') || ($local[$localLen - 1] == '.')) {
            // local part starts or ends with '.'
            $isValid = false;
         } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
         } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
         } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
         } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                str_replace("\\\\", "", $local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
               $isValid = false;
            }
         }

         if ($checkdns) {
            if ($isValid
                && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
               // domain not found in DNS
               $isValid = false;
            }

         } else if (!preg_match('/\\./', $domain) || !preg_match("/[a-zA-Z0-9]$/", $domain)) {
            // domain has no dots or do not end by alphenum char
            $isValid = false;
         }
      }
      return $isValid;
   }


   /**
    * @param $options   array
    **/
   function sendNotification($options = []) {

      $this->SetFrom($options['from'], $options['fromname'], false);

      if ($options['replyto']) {
         $this->AddReplyTo($options['replyto'], $options['replytoname']);
      }
      $this->Subject = Html::entity_decode_deep($options['subject']);

      if (empty($options['content_html'])) {
         $this->isHTML(false);
         $this->Body = $options['content_text'];
      } else {
         $this->isHTML(true);
         $this->Body    = Html::entity_decode_deep(str_replace(['\r', '\n', '\r\n'], '<br>', $options['content_html']));
         $this->AltBody = $options['content_text'];
      }

      $this->AddAddress($options['to'], $options['toname']);

      if (!empty($options['messageid'])) {
         $this->MessageID = "<" . $options['messageid'] . ">";
      }

      // Attach pdf to mail
      if (!empty($options['attachment'])) {
         foreach ($options['attachment'] as $attachment) {
            $this->AddAttachment($attachment['filepath'], $attachment['name']);
         }
      }

      $messageerror = __('Error in sending the email', 'activity');

      if (!$this->Send()) {
         Session::addMessageAfterRedirect($messageerror . "<br>" . $this->ErrorInfo, true);
         $this->ClearAddresses();
         return false;
      } else {
         if ((count($this->to)) > 0) {
            foreach ($this->to as $to) {
               //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
               Toolbox::logInFile("mail", sprintf(__('%1$s: %2$s'),
                                                  sprintf(__('An email was sent to %s'), $to[0]),
                                                  $options['subject'] . "\n"));
            }
         }
         if ((count($this->cc)) > 0) {
            foreach ($this->cc as $to) {
               //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
               Toolbox::logInFile("mail", sprintf(__('%1$s: %2$s'),
                                                  sprintf(__('An email was sent to %s'), $to[0]),
                                                  $options['subject'] . "\n"));
            }
         }
         if ((count($this->bcc)) > 0) {
            foreach ($this->bcc as $to) {
               //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
               Toolbox::logInFile("mail", sprintf(__('%1$s: %2$s'),
                                                  sprintf(__('An email was sent to %s'), $to[0]),
                                                  $options['subject'] . "\n"));
            }
         }
      }
      $this->ClearAddresses();
      return true;
   }
}
