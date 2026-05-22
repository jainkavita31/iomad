<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Hook callbacks for local_dashboard.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks.
 */
class hook_callbacks {

    /**
     * Add Exam Dashboard to Boost primary navigation for capable users only.
     *
     * @param \core\hook\navigation\primary_extend $hook
     * @return void
     */
    public static function primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (!local_dashboard_user_can_view()) {
            return;
        }

        $hook->get_primaryview()->add(
            get_string('pluginname', 'local_dashboard'),
            local_dashboard_index_url(),
            \navigation_node::TYPE_CUSTOM,
            null,
            'local_dashboard',
            new \pix_icon('i/report', '')
        );
    }
}
