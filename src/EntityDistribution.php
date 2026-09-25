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

use CommonGLPI;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Manageentities\TechLead;
use Html;
use Plugin;
use Session;
use Ticket;
use User;

/**
 * Distribution, per technician, of the CRA ticket task time over the clients (entities)
 */
class EntityDistribution extends CommonGLPI
{
    public static $rightname = 'plugin_activity_statistics';

    public static function getTypeName($nb = 0)
    {
        return __('Distribution by client', 'activity');
    }

    public static function getIcon()
    {
        return 'ti ti-chart-pie';
    }

    /**
     * Years offered in the selector, same span as the CRA year dropdown
     *
     * @return int[]
     */
    public static function getAvailableYears(): array
    {
        $current = (int) date('Y');
        return range($current - 3, $current + 3);
    }

    /**
     * Resolve the technician filter the current user is allowed to use
     *
     * @param mixed $users_id posted value, 0 meaning every technician
     *
     * @return int
     */
    public static function resolveUser($users_id): int
    {
        // Without plugin_activity_all_users, a user only ever sees their own distribution
        if (!Session::haveRight('plugin_activity_all_users', 1)) {
            return (int) Session::getLoginUserID();
        }

        $users_id = (int) $users_id;
        // Same predicate as the CRA: the user selector is bounded to the active entities
        if ($users_id > 0 && !Holiday::isUserInSessionEntities($users_id)) {
            return 0;
        }

        return max($users_id, 0);
    }

    /**
     * Ticket task time put on the CRA during the year, grouped by technician and entity
     *
     * @param int $year
     * @param int $users_id 0 for every technician
     *
     * @return array
     */
    public static function getData(int $year, int $users_id = 0): array
    {
        global $DB;

        $task_table   = \TicketTask::getTable();
        $ticket_table = Ticket::getTable();
        $entity_table = Entity::getTable();
        $cra_table    = TicketTask::getTable();

        $criteria = [
            'SELECT' => [
                "$task_table.users_id_tech",
                "$ticket_table.entities_id",
                "$entity_table.completename AS entity_name",
                new QueryExpression("SUM(" . $DB->quoteName("$task_table.actiontime") . ") AS total"),
                new QueryExpression("COUNT(DISTINCT " . $DB->quoteName("$task_table.id") . ") AS nb_tasks"),
                new QueryExpression("COUNT(DISTINCT " . $DB->quoteName("$ticket_table.id") . ") AS nb_tickets"),
            ],
            'FROM' => $task_table,
            'INNER JOIN' => [
                $ticket_table => [
                    'FKEY' => [
                        $task_table   => 'tickets_id',
                        $ticket_table => 'id',
                    ],
                ],
                $entity_table => [
                    'FKEY' => [
                        $ticket_table => 'entities_id',
                        $entity_table => 'id',
                    ],
                ],
                $cra_table => [
                    'FKEY' => [
                        $task_table => 'id',
                        $cra_table  => 'tickettasks_id',
                    ],
                ],
            ],
            'WHERE' => [
                "$cra_table.is_oncra"        => 1,
                "$ticket_table.is_deleted"   => 0,
                "$task_table.actiontime"     => ['>', 0],
                "$task_table.users_id_tech"  => $users_id > 0 ? $users_id : ['>', 0],
                PlanningExternalEvent::getTicketTaskDateCriterias(
                    sprintf('%04d-01-01 00:00:00', $year),
                    sprintf('%04d-01-01 00:00:00', $year + 1),
                ),
                getEntitiesRestrictCriteria($ticket_table, '', $_SESSION['glpiactiveentities'], false),
            ],
            'GROUPBY' => [
                "$task_table.users_id_tech",
                "$ticket_table.entities_id",
                "$entity_table.completename",
            ],
        ];

        $techs = [];
        foreach ($DB->request($criteria) as $row) {
            $tech_id = (int) $row['users_id_tech'];
            if (!isset($techs[$tech_id])) {
                $techs[$tech_id] = [
                    'id'       => $tech_id,
                    'name'     => getUserName($tech_id),
                    'total'    => 0,
                    'entities' => [],
                ];
            }
            $techs[$tech_id]['total'] += (int) $row['total'];
            $techs[$tech_id]['entities'][] = [
                'id'         => (int) $row['entities_id'],
                'name'       => $row['entity_name'],
                'total'      => (int) $row['total'],
                'nb_tasks'   => (int) $row['nb_tasks'],
                'nb_tickets' => (int) $row['nb_tickets'],
            ];
        }

        foreach ($techs as &$tech) {
            usort($tech['entities'], static fn($a, $b) => $b['total'] <=> $a['total']);
            foreach ($tech['entities'] as &$entity) {
                $entity['percent']        = $tech['total'] > 0 ? round($entity['total'] * 100 / $tech['total'], 1) : 0;
                $entity['total_readable'] = Html::timestampToString($entity['total'], false);
            }
            unset($entity);
            $tech['total_readable'] = Html::timestampToString($tech['total'], false);
        }
        unset($tech);

        uasort($techs, static fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return array_values($techs);
    }

    /**
     * Display the selection form and the distribution
     *
     * @param array $input
     *
     * @return void
     */
    public static function showDistribution(array $input): void
    {
        Session::checkRight(self::$rightname, READ);

        $years = self::getAvailableYears();
        $year  = (int) ($input['year'] ?? date('Y'));
        if (!in_array($year, $years, true)) {
            $year = (int) date('Y');
        }
        $users_id = self::resolveUser($input['users_id'] ?? 0);

        $can_see_all_users = (bool) Session::haveRight('plugin_activity_all_users', 1);
        $user_dropdown     = '';
        if ($can_see_all_users) {
            $user_dropdown = User::dropdown([
                'name'                => 'users_id',
                'value'               => $users_id,
                'right'               => 'interface',
                'entity'              => $_SESSION['glpiactiveentities'],
                'display_emptychoice' => true,
                'emptylabel'          => __('All technicians', 'activity'),
                'display'             => false,
            ]);
        }

        $techs = self::getData($year, $users_id);

        TemplateRenderer::getInstance()->display('@activity/entity_distribution.html.twig', [
            'title'             => self::getTypeName(),
            'icon'              => self::getIcon(),
            'form_action'       => PLUGIN_ACTIVITY_WEBDIR . '/front/entitydistribution.php',
            'years'             => $years,
            'year'              => $year,
            'can_see_all_users' => $can_see_all_users,
            'user_dropdown'     => $user_dropdown,
            'techs'             => $techs,
            'users_id'          => $users_id,
            'show_techleads'    => self::useTechLeads(),
            'can_set_techleads' => self::useTechLeads() && TechLead::canCreate(),
            'techleads'         => self::useTechLeads() ? self::getTechLeads($techs) : [],
        ]);
    }

    /**
     * Whether the tech leads of the clients can be shown and defined: they are stored by
     * the manageentities plugin
     *
     * @return bool
     */
    public static function useTechLeads(): bool
    {
        return Plugin::isPluginActive('manageentities') && class_exists(TechLead::class);
    }

    /**
     * Define a technician as tech lead of the clients listed for them in the distribution
     *
     * @param array $input posted year and technician
     *
     * @return void
     */
    public static function setTechLeads(array $input): void
    {
        if (!self::useTechLeads()) {
            return;
        }

        $year = (int) ($input['year'] ?? 0);
        if (!in_array($year, self::getAvailableYears(), true)) {
            return;
        }
        // Same filter as the display: without the all users right, only oneself
        $techs_id = self::resolveUser($input['techs_id'] ?? 0);
        if ($techs_id <= 0) {
            return;
        }

        // The clients are recomputed from the distribution rather than trusted from the request
        $added = 0;
        foreach (self::getData($year, $techs_id) as $tech) {
            foreach ($tech['entities'] as $entity) {
                $techlead = new TechLead();
                if ($techlead->getFromDBByCrit(['users_id' => $techs_id, 'entities_id' => $entity['id']])) {
                    continue;
                }
                $link = ['users_id' => $techs_id, 'entities_id' => $entity['id']];
                // can(-1, CREATE, $input) checks the right of manageentities on the target client
                if ($techlead->can(-1, CREATE, $link) && $techlead->add($link)) {
                    $added++;
                }
            }
        }

        Session::addMessageAfterRedirect(
            sprintf(__('%1$s client(s) added for %2$s', 'activity'), $added, getUserName($techs_id)),
        );
    }

    /**
     * Tech leads of the clients listed in the distribution, main one first
     *
     * @param array $techs
     *
     * @return array<int, array<int, array{users_id: int, name: string, is_default: bool}>>
     */
    public static function getTechLeads(array $techs): array
    {
        $entities = [];
        foreach ($techs as $tech) {
            foreach ($tech['entities'] as $entity) {
                $entities[$entity['id']] = $entity['id'];
            }
        }
        if ($entities === []) {
            return [];
        }

        $techleads = [];
        foreach (TechLead::getTechLeadsByEntity(array_values($entities)) as $entities_id => $leads) {
            foreach ($leads as $lead) {
                $techleads[$entities_id][] = [
                    'users_id'   => $lead['users_id'],
                    'name'       => getUserName($lead['users_id']),
                    'is_default' => $lead['is_default'],
                ];
            }
        }

        return $techleads;
    }
}
