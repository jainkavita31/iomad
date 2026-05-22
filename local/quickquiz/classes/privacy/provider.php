<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Privacy provider.
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickquiz\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Null provider — plugin does not store user data.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
