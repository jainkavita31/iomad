<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade script for local_quickquiz.
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_quickquiz_upgrade($oldversion) {
    global $CFG;

    require_once($CFG->dirroot . '/local/quickquiz/lib.php');

    if ($oldversion < 2026052008) {
        local_quickquiz_seed_default_config();
        upgrade_plugin_savepoint(true, 2026052008, 'local', 'quickquiz');
    }

    if ($oldversion < 2026052009) {
        unset_config('defaultproctoring', 'local_quickquiz');
        upgrade_plugin_savepoint(true, 2026052009, 'local', 'quickquiz');
    }

    return true;
}
