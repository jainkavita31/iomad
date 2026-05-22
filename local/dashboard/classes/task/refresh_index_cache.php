<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Rebuilds cached exam dashboard index payloads for all companies.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\task;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../local/index_snapshot.php');

/**
 * Scheduled every 2 hours (see db/tasks.php).
 */
class refresh_index_cache extends \core\task\scheduled_task {

    /**
     * @return \lang_string|string
     */
    public function get_name() {
        return get_string('taskrefreshindexcache', 'local_dashboard');
    }

    public function execute(): void {
        \local_dashboard\local\index_snapshot::refresh_all_companies();
    }
}
