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

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use NotificationEvent;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * HolidayValidation class
 */
class HolidayValidation extends CommonDBChild
{
    public static $items_id  = 'plugin_activity_holidays_id';
    public static $itemtype  = Holiday::class;
    public static $rightname = "plugin_activity";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     * */
    public static function getTypeName($nb = 0)
    {
        return _n('Holiday validation', 'Holidays validation', $nb, 'activity');
    }

    /**
     * @param $tickets_id
     *
     * @return bool
     */
    public static function canValidate($hId)
    {
        return countElementsInTable(
            'glpi_plugin_activity_holidayvalidations',
            [
                'plugin_activity_holidays_id' => $hId,
                'users_id_validate'           => Session::getLoginUserID(),
            ],
        ) > 0;
    }

    public function canViewItem(): bool
    {
        // Per-record read gate. Without this override canViewItem() delegates to
        // the parent Holiday (HAVE_SAME_RIGHT_ON_ITEM), so can($id, READ) — used
        // by ajax/viewsubvalidation.php — reduces to a global right and lets any
        // holder read any validation record (horizontal IDOR / HR data leak). A
        // validation may be viewed only by a designated validator of the target
        // holiday, by the holiday requester, or by a profile holding
        // plugin_activity_all_users. Mirrors front/holidayvalidation.form.php.
        if (Session::haveRight('plugin_activity_all_users', 1)) {
            return true;
        }
        $holidays_id = (int) ($this->fields['plugin_activity_holidays_id'] ?? 0);
        if ($holidays_id <= 0) {
            return false;
        }
        if (self::canValidate($holidays_id)) {
            return true;
        }
        $holiday = new Holiday();
        return $holiday->getFromDB($holidays_id)
            && (int) $holiday->fields['users_id'] === Session::getLoginUserID();
    }

    public static function getIcon()
    {
        return "ti ti-calendar-event";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == Holiday::class) {
            return self::createTabEntry(HolidayValidation::getTypeName(1));
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == Holiday::class) {
            $validation = new HolidayValidation();
            $validation->showSummary($item);
        }
        return true;
    }

    public function prepareInputForAdd($input)
    {
        //      $input['comment_validation'] = '';
        $input['submission_date'] = date('Y-m-d H:i');

        return parent::prepareInputForAdd($input);
    }

    public function post_addItem()
    {

        $holiday = new Holiday();
        if ($holiday->getFromDB($this->fields['plugin_activity_holidays_id'])) {
            // Set global validation to waiting
            if ($holiday->fields['global_validation'] == CommonValidation::NONE) {
                $input['id']                = $this->fields['plugin_activity_holidays_id'];
                $input['global_validation'] = CommonValidation::WAITING;
                $holiday->update($input);
            }
        }
    }

    public function prepareInputForUpdate($input)
    {
        $input['validation_date'] = date('Y-m-d H:i:s');

        if (isset($input['refuse_holiday']) && $input['refuse_holiday'] == 1) {
            $input['status'] = CommonValidation::REFUSED;
        }

        if (isset($input['accept_holiday']) && $input['accept_holiday'] == 1) {
            $input['status'] = CommonValidation::ACCEPTED;
        }

        if ($input['status'] == CommonValidation::REFUSED && $input['comment_validation'] == "") {
            Session::addMessageAfterRedirect(__('If approval is denied, specify a reason.'), false, ERROR);
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }


    public function post_updateItem($history = 1)
    {
        global $CFG_GLPI;

        $holiday = new Holiday();
        $holiday->getFromDB($this->fields['plugin_activity_holidays_id']);

        $condition = ["plugin_activity_holidays_id" => $this->fields['plugin_activity_holidays_id']];
        $dbu       = new DbUtils();
        $datas     = $dbu->getAllDataFromTable($this->getTable(), $condition);

        // Check if all holidaysValidation are validated or not
        //Set global validation to accepted to define one
        if (($holiday->fields['global_validation'] == CommonValidation::WAITING)
          && in_array("status", $this->updates)) {
            $input['id']                = $this->fields['plugin_activity_holidays_id'];
            $input['global_validation'] = self::computeValidationStatus($holiday);
            $holiday->update($input);
        }

        /*$isValidated = array(
          'allValidated' => 0,
          'allRefused'   => 0,
          'allWaiting'   => 0
      );
      $finalValidated = 0;
      if (sizeof($datas) > 0){
          foreach ($datas as $data) {
             if ($data['status'] == CommonValidation::ACCEPTED) {
                $isValidated['allValidated'] ++;
             } else if ($data['status'] == CommonValidation::REFUSED) {
                $isValidated['allRefused'] ++;
             } else {
                $isValidated['allWaiting'] ++;
             }

          }
      }

      if ($isValidated['allWaiting'] > 0 ){
          $finalValidated = CommonValidation::WAITING;
      }else if ( $isValidated['allValidated'] > 0 && $isValidated['allRefused'] == 0){
          $finalValidated = CommonValidation::ACCEPTED;
      }else if ( $isValidated['allValidated'] == 0 && $isValidated['allRefused'] > 0){
          $finalValidated = CommonValidation::REFUSED;
      }else{
          $finalValidated = CommonValidation::WAITING;
      }




      if ($holiday->fields['status'] != $finalValidated ){
          $holiday->fields['status'] = $finalValidated;
          $holiday->update($holiday->fields);
        }*/

        $donotif  = $CFG_GLPI["notifications_mailing"];
        $mailsend = false;
        if (isset($this->input['_disablenotif'])) {
            $donotif = false;
        }

        // If holiday validated, send mail to the applicant
        if ($holiday->fields['global_validation'] == CommonValidation::ACCEPTED
          || $holiday->fields['global_validation'] == CommonValidation::REFUSED) {
            if (count($this->updates) && in_array('status', $this->updates) && $donotif) {
                if ($CFG_GLPI["notifications_mailing"]) {
                    $options = ['plugin_activity_holidayvaldiations_id' => $this->fields["id"]];

                    $mailsend = NotificationEvent::raiseEvent('answervalidation', $holiday, $options);
                }
            }

            if ($mailsend) {
                $user = new User();
                $user->getFromDB($holiday->fields["users_id"]);
                $email = $user->getDefaultEmail();
                if (!empty($email)) {
                    //TRANS: %s is the user name
                    Session::addMessageAfterRedirect(sprintf(__('Mail sent to %s', 'activity'), $user->getDefaultEmail()));
                } else {
                    Session::addMessageAfterRedirect(
                        sprintf(
                            __('The selected user (%s) has no valid email address. The request has been created, without email confirmation.'),
                            $user->getName(),
                        ),
                        false,
                        ERROR,
                    );
                }
            }
        }
    }


    public static function computeValidationStatus($item)
    {

        $validation_status = CommonValidation::WAITING;

        $accepted = 0;
        $rejected = 0;

        // Percent of validation
        $validation_percent = $item->fields['validation_percent'];

        $statuses    = [CommonValidation::ACCEPTED => 0,
            CommonValidation::WAITING  => 0,
            CommonValidation::REFUSED  => 0];
        $restrict    = ["plugin_activity_holidays_id" => $item->getID()];
        $dbu         = new DbUtils();
        $validations = $dbu->getAllDataFromTable(static::getTable(), $restrict);

        if ($total = count($validations)) {
            foreach ($validations as $validation) {
                $statuses[$validation['status']]++;
            }
        }

        if ($validation_percent > 0) {
            if (($statuses[CommonValidation::ACCEPTED] * 100 / $total) >= $validation_percent) {
                $validation_status = CommonValidation::ACCEPTED;
            } elseif (($statuses[CommonValidation::REFUSED] * 100 / $total) >= $validation_percent) {
                $validation_status = CommonValidation::REFUSED;
            }
        } else {
            if ($statuses[CommonValidation::ACCEPTED]) {
                $validation_status = CommonValidation::ACCEPTED;
            } elseif ($statuses[CommonValidation::REFUSED]) {
                $validation_status = CommonValidation::REFUSED;
            }
        }

        return $validation_status;
    }

    /**
     * Get the validation statistics
     *
     * @param $tID holiday id
     *
     * @return  array statistics
     **/
    public static function getValidationStats($tID)
    {

        $tab = CommonValidation::getAllStatusArray();
        $dbu = new DbUtils();
        $nb  = $dbu->countElementsInTable(static::getTable(), [static::$items_id => $tID]);

        $stats = [];
        foreach ($tab as $status => $name) {
            $restrict    = [static::$items_id => $tID, "status" => $status];
            $dbu         = new DbUtils();
            $validations = $dbu->countElementsInTable(static::getTable(), $restrict);
            if ($validations > 0) {
                if (!isset($stats[$status])) {
                    $stats[$status] = 0;
                }
                $stats[$status] = $validations;
            }
        }

        $list = "";
        foreach ($stats as $stat => $val) {
            $list .= $tab[$stat];
            $list .= sprintf(__('%1$s (%2$d%%) '), " ", HTml::formatNumber($val * 100 / $nb));
        }

        return $list;
    }

    public function showSummary($item)
    {
        $canedit = true;
        $dbu     = new DbUtils();
        $hID     = $item->fields['id'];
        $holiday = new Holiday();
        $holiday->getFromDB($hID);

        // Global validation dropdown
        ob_start();
        CommonValidation::dropdownStatus('global_validation', ['value' => $item->fields['global_validation']]);
        $global_validation_dropdown_html = ob_get_clean();

        // Validation percent widget
        $validation_percent_html    = $item->getValueToSelect('validation_percent', 'validation_percent', $item->fields['validation_percent']);
        $validation_percent_display = Dropdown::getValueWithUnit($item->fields['validation_percent'], '%');

        // Validation rows + navigator + per-validator form
        $validations        = [];
        $validator_form_html = '';

        if (isset($holiday->fields['id'])) {
            $datas = $dbu->getAllDataFromTable(
                static::getTable(),
                ['plugin_activity_holidays_id' => $holiday->fields['id']],
            );

            Session::initNavigateListItems(
                HolidayValidation::class,
                sprintf(__('%1$s = %2$s'), $holiday->getTypeName(1), $holiday->fields['name']),
            );

            foreach ($datas as $data) {
                Session::addToNavigateListItems(HolidayValidation::class, $data['id']);

                // Capture the per-validator action form (showForm outputs HTML directly)
                if ($data['users_id_validate'] == Session::getLoginUserID()
                    && $data['status'] == CommonValidation::WAITING) {
                    ob_start();
                    $this->showForm($data['id'], ['parent' => $holiday->fields['id']]);
                    $validator_form_html .= ob_get_clean();
                }

                $validations[] = [
                    'status'          => $data['status'],
                    'status_label'    => CommonValidation::getStatus($data['status']),
                    'submission_date' => Html::convDateTime($data['submission_date']),
                    'validation_date' => Html::convDateTime($data['validation_date']),
                    'validator_name'  => $dbu->getUserName($data['users_id_validate']),
                    'comment_validation' => $data['comment_validation'],
                ];
            }
        }

        TemplateRenderer::getInstance()->display('@activity/holiday_validation_summary.html.twig', [
            'canedit'                        => $canedit,
            'form_url'                       => Toolbox::getItemTypeFormURL(static::$itemtype),
            'typename'                       => self::getTypeName(Session::getPluralNumber()),
            'holiday_id'                     => $hID,
            'global_validation_dropdown_html' => $global_validation_dropdown_html,
            'validation_stats'               => self::getValidationStats($hID),
            'validation_percent_html'        => $validation_percent_html,
            'validation_percent_display'     => $validation_percent_display,
            'validator_form_html'            => $validator_form_html,
            'validations'                    => $validations,
            'STATUS_ACCEPTED'                => CommonValidation::ACCEPTED,
            'STATUS_REFUSED'                 => CommonValidation::REFUSED,
        ]);
    }


    /**
     * Print the validation form
     *
     * @param $ID        integer  ID of the item
     * @param $options   array    options used
     *
     *
     * @return bool
     */
    public function showForm($ID, $options = [])
    {
        $dbu = new DbUtils();

        $options['colspan']   = 1;
        $options['candel']    = false;
        $options['formtitle'] = '';
        $options['form_id']   = 'formvalidation';

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $holiday   = new Holiday();
        $holiday->getFromDB($this->fields['plugin_activity_holidays_id']);
        $is_validator   = ($this->fields['users_id_validate'] == Session::getLoginUserID());
        $status_waiting = ($this->fields['status'] == CommonValidation::WAITING);

        ob_start();
        Html::textarea([
            'name'            => 'comment_validation',
            'value'           => $this->fields['comment_validation'],
            'cols'            => 100,
            'rows'            => 3,
            'enable_richtext' => false,
        ]);
        $comment_textarea_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@activity/holiday_validation_form.html.twig', [
            'id'                   => $this->fields['id'],
            'is_validator'         => $is_validator,
            'status_waiting'       => $status_waiting,
            'status_label'         => CommonValidation::getStatus($this->fields['status']),
            'comment_validation'   => $this->fields['comment_validation'],
            'comment_textarea_html' => $comment_textarea_html,
            'requester_name'       => $dbu->getUserName($holiday->fields['users_id']),
            'validator_name'       => $dbu->getUserName($this->fields['users_id_validate']),
            'validation_date'      => date('Y-m-d H:i:s'),
        ]);

        $options['formfooter'] = '';
        $options['canedit']    = false;
        $this->showFormButtons($options);
        Html::closeForm();
        return true;
    }


    /**
     * @since version 0.84
     *
     * @see CommonDBConnexity::getHistoryChangeWhenUpdateField
     **/
    public function getHistoryChangeWhenUpdateField($field)
    {

        $dbu = new DbUtils();
        if ($field == 'status') {
            $username = $dbu->getUserName($this->fields["users_id_validate"]);
            $result   = ['0', '', ''];
            if ($this->fields["status"] == 'accepted') {
                //TRANS: %s is the username
                $result[2] = sprintf(__('Approval granted by %s'), $username);
            } else {
                //TRANS: %s is the username
                $result[2] = sprintf(__('Update the approval request to %s'), $username);
            }
            return $result;
        }
        return false;
    }


    /**
     * @since version 0.84
     *
     * @see CommonDBChild::getHistoryNameForItem
     **/
    public function getHistoryNameForItem(CommonDBTM $item, $case)
    {

        $dbu      = new DbUtils();
        $username = $dbu->getUserName($this->fields["users_id_validate"]);
        switch ($case) {
            case 'add':
                return sprintf(__('Approval request send to %s', 'activity'), $username);

            case 'delete':
                return sprintf(__('Cancel the approval request to %s', 'activity'), $username);
        }
        return '';
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'users_id_validate':
                $user = new User();
                $user->getFromDB($values[$field]);
                return $user->getLink();
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        $dbu = new DbUtils();
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'users_id_validate':
                $holidayValidation = new HolidayValidation();
                $validators        = $holidayValidation->find();
                $elements          = [Dropdown::EMPTY_VALUE];
                foreach ($validators as $validator) {
                    $elements[$validator['users_id_validate']] = $dbu->getUserName($validator['users_id_validate']);
                }

                return Dropdown::showFromArray($name, $elements, ['display' => false, 'value' => $values[$field]]);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }
}
