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

namespace GlpiPlugin\Activity;

use CommonDBTM;
use GLPIMailer;
use Log;
use Session;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use User;

class Notification extends CommonDBTM
{
    /**
     * @param $options   array
     **/
    public function sendNotification($options = [])
    {

        $transport = Transport::fromDsn(GLPIMailer::buildDsn(true));

        $mmail = new GLPIMailer($transport);
        $mail = $mmail->getEmail();

        // No caller fills a 'messageid' key: sendComm() builds its options array
        // without it and send() only merges what it is handed. Reading it
        // unconditionally raised an "Undefined array key" warning and passed null to
        // addTextHeader(), whose Symfony Mime signature expects a string, so PHP 8
        // aborted on a TypeError before the transport was even opened: the holiday
        // notification never left, and sendComm() never got the return value it
        // historises. Only set the header when one is actually supplied.
        if (!empty($options['messageid'])) {
            $mail->getHeaders()->addTextHeader("Message-Id", (string) $options['messageid']);
        }

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
            // Keep the string extractable (the placeholder stays inside __(), the value
            // outside) and escape it: addMessageAfterRedirect() messages are rendered as
            // HTML by GLPI 11, and the rest of the plugin already wraps them in
            // htmlescape() - see Holiday::prepareInputForAdd().
            $alert = htmlescape(sprintf(__('Failed to send email to "%s"', 'activity'), $options['to']));
            Session::addMessageAfterRedirect(
                $alert,
                false,
                ERROR,
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
    public static function send($mailing_options, $additional_options)
    {

        $mail = new self();
        $mail->sendNotification(array_merge($mailing_options, $additional_options));
    }

    public static function sendComm($input)
    {
        global $_FILES, $CFG_GLPI;
        $send = false;

        // Subject
        $subject = $input['mail_subject'];
        // Body
        $body = $input['mail_body'];


        if ($subject == "") {
            Session::addMessageAfterRedirect(__('Please fill a subject', 'activity'), false, ERROR);

        } elseif ($body == "") {
            Session::addMessageAfterRedirect(__('Please fill a mail body', 'activity'), false, ERROR);

        } else {
            // Envoi du mail
            $notificationMail = new self();
            $mail     = "";
            $user = new User();
            // Both are read unconditionally below, but were only assigned inside the
            // branch: a validator whose account has since been removed produced an
            // undefined Reply-To rather than none at all.
            $validate_email = '';
            $validate_name  = '';
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
                // Security: the body is built by Holiday::getBodyMail(), which escapes
                // the values it injects into the HTML template. Decoding the entities
                // back here undid exactly that protection and handed the mail client
                // whatever markup a display name carried.
                'content_html' => $body,
                'content_text' => strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $body)),
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
                // $mail comes from the plugin configuration (glpi_plugin_activity_options), and
                // GLPI 11 renders redirect messages with |raw - so the value has to be escaped
                // at the output point. The placeholder stays inside __() and the value outside
                // so the string remains extractable, as on line 88 of this file.
                $alert = htmlescape(sprintf(__('A mail has been sent to %s', 'activity'), $mail));
                Session::addMessageAfterRedirect($alert);
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
