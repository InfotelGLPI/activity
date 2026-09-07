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
use CommonGLPI;
use DbUtils;
use Document;
use Document_Item;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Option extends CommonDBTM
{
    public static $rightname = "plugin_activity";

    public static function getTypeName($nb = 0)
    {
        return __('Setup');
    }

    public static function getIcon()
    {
        return 'ti ti-settings';
    }

    public static function getFormURL($full = true)
    {
        return PLUGIN_ACTIVITY_WEBDIR . '/front/config.form.php' . ($full ? '?' : '');
    }

    public static function canView(): bool
    {
        return \Session::haveRight('plugin_activity', READ);
    }

    public static function canCreate(): bool
    {
        return \Session::haveRight('plugin_activity', UPDATE);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && $item->getType() === __CLASS__) {
            return [
                1 => self::createTabEntry(__('Plugin setup', 'activity'), 0, $item->getType(), 'ti ti-adjustments'),
                2 => self::createTabEntry(__('Internal helpdesk', 'activity'), 0, $item->getType(), 'ti ti-headset'),
            ];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === __CLASS__) {
            if ($tabnum === 1) {
                $item->showOptionsForm();
            } elseif ($tabnum === 2) {
                $config = new Config();
                $config->showForm(1);
            }
        }
        return true;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        return $ong;
    }

    public function showForm($ID, $options = [])
    {
        $this->getFromDB(1);
        $this->showOptionsForm();
        return true;
    }

    public function showOptionsForm()
    {
        global $CFG_GLPI;

        $this->getFromDB(1);

        if (!$this->canView()) {
            return false;
        }

        $holiday = new Holiday();

        ob_start();
        $rand_timerep = Dropdown::showYesNo(
            'use_timerepartition',
            $this->fields['use_timerepartition'],
            -1,
            ['on_change' => 'activity_changetimerepartition();'],
        );
        $use_timerepartition_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_mandaydisplay', $this->fields['use_mandaydisplay']);
        $use_mandaydisplay_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_type_as_name', $this->fields['use_type_as_name']);
        $use_type_as_name_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_pairs', $this->fields['use_pairs']);
        $use_pairs_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_integerschedules', $this->fields['use_integerschedules']);
        $use_integerschedules_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_weekend', $this->fields['use_weekend']);
        $use_weekend_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_groupmanager', $this->fields['use_groupmanager']);
        $use_groupmanager_html = ob_get_clean();

        ob_start();
        echo $holiday->getValueToSelect(
            'validation_percent',
            'default_validation_percent',
            $this->fields['default_validation_percent'],
        );
        $default_validation_percent_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('is_cra_default', $this->fields['is_cra_default']);
        $is_cra_default_html = ob_get_clean();

        ob_start();
        $rand_project = Dropdown::showYesNo(
            'use_project',
            $this->fields['use_project'],
            -1,
            ['on_change' => 'activity_change_useproject();'],
        );
        $use_project_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('is_cra_default_project', $this->fields['is_cra_default_project']);
        $is_cra_default_project_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_hour_on_cra', $this->fields['use_hour_on_cra']);
        $use_hour_on_cra_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_planning_activity_hours', $this->fields['use_planning_activity_hours']);
        $use_planning_activity_hours_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('use_planningeventsubcategories', $this->fields['use_planningeventsubcategories']);
        $use_planningeventsubcategories_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('show_planningevents_entity', $this->fields['show_planningevents_entity']);
        $show_planningevents_entity_html = ob_get_clean();

        ob_start();
        Dropdown::showYesNo('show_planningevents_project', $this->fields['show_planningevents_project']);
        $show_planningevents_project_html = ob_get_clean();

        // Logo upload widget
        ob_start();
        Html::file(['multiple' => false, 'onlyimages' => true]);
        $logo_upload_html = ob_get_clean();

        // Preview URL if a logo is already stored as a Document
        $logo_preview_url = '';
        $logo_id = (int) ($this->fields['cra_logo_id'] ?? 0);
        if ($logo_id > 0) {
            $logo_preview_url = $CFG_GLPI['root_doc'] . '/front/document.send.php?docid=' . $logo_id;
        }

        TemplateRenderer::getInstance()->display('@activity/option_form.html.twig', [
            'form_url'                           => Toolbox::getItemTypeFormURL(Option::class),
            'fields'                             => $this->fields,
            'rand_timerep'                       => $rand_timerep,
            'rand_project'                       => $rand_project,
            'use_timerepartition_html'           => $use_timerepartition_html,
            'use_mandaydisplay_html'             => $use_mandaydisplay_html,
            'use_type_as_name_html'              => $use_type_as_name_html,
            'use_pairs_html'                     => $use_pairs_html,
            'use_integerschedules_html'          => $use_integerschedules_html,
            'use_weekend_html'                   => $use_weekend_html,
            'use_groupmanager_html'              => $use_groupmanager_html,
            'default_validation_percent_html'    => $default_validation_percent_html,
            'is_cra_default_html'                => $is_cra_default_html,
            'use_project_html'                   => $use_project_html,
            'is_cra_default_project_html'        => $is_cra_default_project_html,
            'use_hour_on_cra_html'               => $use_hour_on_cra_html,
            'use_planning_activity_hours_html'   => $use_planning_activity_hours_html,
            'use_planningeventsubcategories_html' => $use_planningeventsubcategories_html,
            'show_planningevents_entity_html'    => $show_planningevents_entity_html,
            'show_planningevents_project_html'   => $show_planningevents_project_html,
            'project_typename'                   => \Project::getTypeName(0),
            'logo_upload_html'                   => $logo_upload_html,
            'logo_preview_url'                   => $logo_preview_url,
            'canedit'                            => $this->canCreate(),
        ]);
    }

    /**
     * getConfigFromDB : get all configs in the database
     *
     * @global mixed $DB
     * @param mixed $ID : configs_id
     * @return boolean
     */
    public static function getConfigFromDB()
    {

        $dbu = new DbUtils();
        $dataConfig = $dbu->getAllDataFromTable("glpi_plugin_activity_options");

        return $dataConfig;
    }

    /**
     * get use project
     *
     * @return mixed
     */
    public function getUseProject()
    {
        return $this->fields['use_project'];
    }

    /**
     * get is cra default project
     *
     * @return mixed
     */
    public function getIsCraDefaultProject()
    {
        return $this->fields['is_cra_default_project'];
    }

    public function getUseSubcategory()
    {
        return $this->fields['use_planningeventsubcategories'];
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['_filename']) && count($input['_filename']) > 0) {
            // Security: the name comes straight from the POST and is later concatenated
            // onto GLPI_TMP_DIR, so any path separator is rejected outright rather than
            // normalised away — a caller has no legitimate reason to send one.
            $posted = (string) $input['_filename'][0];
            if ($posted === '' || strpbrk($posted, "/\\") !== false) {
                Session::addMessageAfterRedirect(
                    __('Invalid filename'),
                    false,
                    ERROR,
                );
                return false;
            }
            $file = basename($posted);

            // Security: the extension says nothing about the content. The uploaded file
            // becomes a GLPI Document and is then read by CraPDF when rendering the
            // report, so the real MIME type is what must be checked.
            $tmpfile = GLPI_TMP_DIR . '/' . $file;
            $ext     = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime    = false;
            if (is_file($tmpfile)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $mime = finfo_file($finfo, $tmpfile);
                    finfo_close($finfo);
                }
            }

            // Security: fail closed. A rejected upload aborts the whole update instead
            // of silently dropping the file, so the stored logo is never lost — and
            // never replaced — on the strength of a name alone.
            if (!in_array($ext, ['jpg', 'jpeg'], true) || $mime !== 'image/jpeg') {
                Session::addMessageAfterRedirect(
                    __('The format of the image must be JPG or JPEG', 'activity'),
                    false,
                    ERROR,
                );
                return false;
            }

            $input['_filename'][0] = $file;
        }
        return $input;
    }

    public function post_updateItem($history = 1)
    {
        if (isset($this->input['_filename']) && count($this->input['_filename']) > 0) {
            $previous_logo_id = (int) ($this->fields['cra_logo_id'] ?? 0);
            $docId            = $this->addLogoFile($this->input);
            if ($docId > 0) {
                $this->fields['cra_logo_id'] = $docId;
                $this->updateInDB(['cra_logo_id']);

                // Replace existing logo: the old Document is dropped only once the new
                // one is in place, so a failed import cannot leave the CRA without a
                // logo.
                if ($previous_logo_id > 0 && $previous_logo_id !== $docId) {
                    $doc = new Document();
                    if ($doc->getFromDB($previous_logo_id)) {
                        $doc->delete(['id' => $previous_logo_id], true);
                    }
                }
            }
        }
    }

    private function addLogoFile(array $input): int
    {
        if (empty($input['_filename'][0])) {
            return 0;
        }
        // The name was normalised and its content validated by prepareInputForUpdate();
        // basename() is repeated here so the method stays safe on its own.
        $file = basename((string) $input['_filename'][0]);
        $filename = GLPI_TMP_DIR . '/' . $file;

        $doc = new Document();
        $entities_id = $_SESSION['glpiactive_entity'] ?? 0;

        if ($doc->getFromDBbyContent($entities_id, $filename)) {
            return $doc->fields['is_blacklisted'] ? 0 : (int) $doc->fields['id'];
        }

        $docId = $doc->add([
            'name'                    => __('CRA logo', 'activity'),
            'entities_id'             => $entities_id,
            'is_recursive'            => 1,
            '_only_if_upload_succeed' => 1,
            '_filename'               => [$file],
        ]);

        if ($docId > 0) {
            $docItem = new Document_Item();
            $docItem->add([
                'documents_id' => $docId,
                'itemtype'     => $this->getType(),
                'items_id'     => $this->getID(),
                '_do_notif'    => 0,
            ]);
        }

        return (int) $docId;
    }

    // Returns the absolute filesystem path to the CRA logo, or null if not set.
    public static function getCraLogoPath(): ?string
    {
        $opt = new self();
        $opt->getFromDB(1);
        $logo_id = (int) ($opt->fields['cra_logo_id'] ?? 0);
        if ($logo_id > 0) {
            $doc = new Document();
            if ($doc->getFromDB($logo_id) && !empty($doc->fields['filepath'])) {
                $path = GLPI_DOC_DIR . '/' . $doc->fields['filepath'];
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        return null;
    }
}
