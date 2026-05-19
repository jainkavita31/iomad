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
     * Restore $CFG->custommenuitems after navigation has been built.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     * @return void
     */
    public static function before_standard_top_of_body_html_generation(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $CFG;

        if (isset($CFG->dbunmodifiedcustommenuitems)) {
            $CFG->custommenuitems = $CFG->dbunmodifiedcustommenuitems;
            unset($CFG->dbunmodifiedcustommenuitems);
        }
    }
}
