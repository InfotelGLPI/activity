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

// The iCal branch authenticates with a personal token instead of the session, so it
// cannot require a session right; every other branch still does, which keeps them
// fail-closed now that the route is declared stateless in setup.php.
if (!isset($_GET['genical'])) {
    Session::checkRight("plugin_activity", READ);
}

if (!isset($_GET["uID"])) {
    $_GET["uID"] = 0;
}

if (!isset($_GET["gID"])) {
    $_GET["gID"] = 0;
}

if (!isset($_GET["limititemtype"])) {
    $_GET["limititemtype"] = "";
}

// Normal call via $_GET
if (isset($_GET['checkavailability'])) {
    // \Planning::checkAvailability() is a core screen: it reads the planning of the user,
    // the group or the object named in the query string, none of which belongs to this
    // plugin. The core controller front/planning.php gates that very call with
    // checkRight("planning", READ), while here only plugin_activity READ was required, so
    // a profile holding the plugin right but deliberately denied the planning right could
    // still browse the core slots through this route. The plugin check at the top of the
    // file is kept: this branch needs both rights.
    Session::checkRight("planning", READ);

    Html::popHeader(__('Availability'));

    \Planning::checkAvailability($_GET);
    Html::popFooter();

} elseif (isset($_GET['genical'])) {
    if (isset($_GET['token'])) {
        // GLPI 11 removed User::getFromDBByToken() and centralised the token flow in
        // Session::authWithToken(), which builds an Auth and goes through Session::init().
        // The previous pairing - getFromDBByToken() + loadMinimalSession() - was therefore
        // a fatal call to an undefined method; and even before that, loadMinimalSession()
        // is a no-op whenever $_SESSION['glpiID'] is set, so rights used to be evaluated
        // on the token bearer while generateIcal() ran in the caller's own entity and
        // profile context. Mirrors core front/planning.php.
        $user = Session::authWithToken(
            $_GET['token'],
            'personal_token',
            isset($_GET['entities_id']) ? (int) $_GET['entities_id'] : null,
            isset($_GET['is_recursive']) ? (bool) $_GET['is_recursive'] : null,
        );
        if ($user) {
            // Security: Session::authWithToken() above opens a full GLPI session for the
            // token bearer - entities loaded, profile switched - before anything has been
            // authorised. The teardown used to sit inside the `if ($ismine || $canview)`
            // branch, so every refusal (uID/gID not matching the bearer, Planning READALL or
            // READGROUP missing on the target entity) still returned an authenticated
            // session cookie to a caller whose request had just been denied - and an iCal
            // URL carries its token in clear, so it is routinely pasted, bookmarked and
            // logged by proxies. The destruction now covers every exit path of the branch,
            // exceptions included. It breaks no legitimate use: generateIcal() has written
            // its whole response by then, and the route is declared stateless in setup.php.
            try {
                if (isset($_GET['entities_id']) && isset($_GET['is_recursive'])) {
                    // Load entities and profiles, needed to pass canViewItem() in the
                    // populatePlanning() hooks during an iCal export.
                    $_SESSION["glpidefault_entity"] = $user->fields['entities_id'];
                    Session::initEntityProfiles($user->getID());
                    if (isset($_SESSION['glpiprofiles'][$user->fields['profiles_id']])) {
                        Session::changeProfile($user->fields['profiles_id']);
                    } else {
                        Session::changeProfile(key($_SESSION['glpiprofiles']));
                    }
                }

                // $_GET values are strings, so the previous `!== 0` identity test against an
                // integer was true as soon as the parameter was present, gID=0 included, and
                // the 'mine' shortcut has to be read before any cast.
                $gid_is_mine = (($_GET["gID"] ?? '') === 'mine');
                $gid         = $gid_is_mine ? 0 : (int) ($_GET["gID"] ?? 0);
                $uid         = (int) ($_GET["uID"] ?? 0);

                //// check if the request is valid: rights on uID / gID
                // First check mine : user then groups
                $ismine = false;
                if ($user->getID() == $uid) {
                    $ismine = true;
                }
                // Check groups if have right to see
                if (!$ismine && ($gid_is_mine || $gid > 0)) {
                    if ($gid_is_mine) {
                        $ismine = true;
                    } else {
                        // GLPI 11 declares getUserEntitiesForRight($user_ID, $rightname,
                        // $rights, $is_recursive = true): calling it with two arguments
                        // passed the right bit as the right *name* and raised a fatal
                        // ArgumentCountError instead of a clean refusal.
                        $entities = Profile_User::getUserEntitiesForRight(
                            $user->getID(),
                            \Planning::$rightname,
                            \Planning::READGROUP,
                        );
                        $groups   = Group_User::getUserGroups($user->getID());
                        foreach ($groups as $group) {
                            if (($gid == $group['id'])
                                && in_array($group['entities_id'], $entities)) {
                                $ismine = true;
                            }
                        }
                    }
                }

                $canview = false;
                // If not mine check global right
                if (!$ismine) {
                    // First check user
                    $entities = Profile_User::getUserEntitiesForRight(
                        $user->getID(),
                        \Planning::$rightname,
                        \Planning::READALL,
                    );
                    if ($uid) {
                        $userentities = Profile_User::getUserEntities($user->getID());
                        $intersect    = array_intersect($entities, $userentities);
                        if (count($intersect)) {
                            $canview = true;
                        }
                    }
                    // Else check group
                    if (!$canview && $gid) {
                        $group = new Group();
                        if ($group->getFromDB($gid)) {
                            if (in_array($group->getEntityID(), $entities)) {
                                $canview = true;
                            }
                        }
                    }
                }

                if ($ismine || $canview) {
                    \Planning::generateIcal($uid, $gid_is_mine ? 'mine' : $gid, $_GET["limititemtype"]);
                }
            } finally {
                Session::destroy();
            }
        }
    }
} else {
    // The right must be settled before anything is written to the response: checked
    // after Html::header(), the access-denied exception was raised with the page
    // already half-sent, which replaced the core error screen with a truncated DOM.
    Session::checkRight('plugin_activity', CREATE);

    Html::header(__('Planning'), $_SERVER['REQUEST_URI'], "plugins", "activity");

    if (!isset($_GET["date"]) || empty($_GET["date"])) {
        $_GET["date"] = date('Y-m-d', time());
    }
    if (!isset($_GET["type"])) {
        $_GET["type"] = "week";
    }
    $planning = new Planning();
    $planning->display($_GET);

    Html::footer();
}
