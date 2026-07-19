<?php

/*
 -------------------------------------------------------------------------
 activity plugin for GLPI
 Copyright (C) 2019-2026 by the activity Development Team.

 https://github.com/InfotelGLPI/activity
 -------------------------------------------------------------------------

 LICENSE

 This file is part of activity.

 activity is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 activity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with activity. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Activity\Preference;

Session::checkLoginUser();

$pref = new Preference();

if (isset($_POST["add"])) {
   // Force the owner to the session user: $_POST['users_id'] is attacker-controlled,
   // so a Preference row can only ever be created for oneself (mirrors the
   // self-scoping already done in ajax/preferenceactions.php).
   if ($pref->canCreate()) {
      $input             = $_POST;
      $input['users_id'] = Session::getLoginUserID();
      $pref->add($input);
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   // Only delete the row when it belongs to the session user: canCreate() is the
   // global plugin_activity right and does not tie the operation to the owner, so
   // an arbitrary id must be ownership-checked before delete.
   if ($pref->canCreate()
       && $pref->getFromDB((int) $_POST["id"])
       && (int) $pref->fields['users_id'] === Session::getLoginUserID()) {
      $pref->delete(['id' => (int) $pref->fields['id']]);
   }
   Html::back();

} else {

   Html::redirect(Toolbox::getItemTypeSearchURL("Preference"));

}
