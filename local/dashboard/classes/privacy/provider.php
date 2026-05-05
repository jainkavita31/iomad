<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Privacy API for local_dashboard.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Declares proctor review log storage and handles export/delete.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_dashboard_proctor_review_log', [
            'reviewer_userid' => 'privacy:metadata:reviewer_userid',
            'candidate_userid' => 'privacy:metadata:candidate_userid',
            'quizid' => 'privacy:metadata:quizid',
            'cmid' => 'privacy:metadata:cmid',
            'timecreated' => 'privacy:metadata:timecreated',
        ]);
        $collection->add_database_table('local_dashboard_index_cache', [
            'companyid' => 'privacy:metadata:indexcachecompany',
            'timerange' => 'privacy:metadata:indexcachetimerange',
            'quizid' => 'privacy:metadata:indexcachequiz',
            'timemodified' => 'privacy:metadata:indexcachetimemodified',
            'payload' => 'privacy:metadata:indexcache',
        ]);
        return $collection;
    }

    /**
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_proctor_review_log')) {
            return $contextlist;
        }
        $sql = "SELECT 1
                  FROM {local_dashboard_proctor_review_log}
                 WHERE reviewer_userid = :u1 OR candidate_userid = :u2";
        if ($DB->record_exists_sql($sql, ['u1' => $userid, 'u2' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_proctor_review_log')) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            $records = $DB->get_records_select(
                'local_dashboard_proctor_review_log',
                'reviewer_userid = :u OR candidate_userid = :u2',
                ['u' => $userid, 'u2' => $userid],
                'timecreated ASC'
            );
            if (!$records) {
                continue;
            }
            $export = new \stdClass();
            $export->entries = array_values($records);
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_dashboard')],
                $export
            );
        }
    }

    /**
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        if (!$DB->get_manager()->table_exists('local_dashboard_proctor_review_log')) {
            return;
        }
        $DB->delete_records('local_dashboard_proctor_review_log');
        if ($DB->get_manager()->table_exists('local_dashboard_index_cache')) {
            $DB->delete_records('local_dashboard_index_cache');
        }
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_proctor_review_log')) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            $DB->delete_records_select(
                'local_dashboard_proctor_review_log',
                'reviewer_userid = :u1 OR candidate_userid = :u2',
                ['u1' => $userid, 'u2' => $userid]
            );
        }
    }
}
