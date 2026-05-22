<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Admin settings — all defaults for the quick create form live here (not ProctorLink globals).
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    require_once($CFG->dirroot . '/local/quickquiz/lib.php');
    // Pre-seed config so upgrade does not stop on "New settings - Quick quiz creator".
    local_quickquiz_seed_default_config();

    $settings = new admin_settingpage('local_quickquiz', get_string('pluginname', 'local_quickquiz'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_quickquiz/quizheading',
        get_string('settings_quizheading', 'local_quickquiz'),
        get_string('settings_quizheading_desc', 'local_quickquiz')
    ));

    $settings->add(new admin_setting_configtext(
        'local_quickquiz/defaultgrade',
        get_string('settings_defaultgrade', 'local_quickquiz'),
        get_string('settings_defaultgrade_desc', 'local_quickquiz'),
        100,
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_quickquiz/defaulttimelimit',
        get_string('settings_defaulttimelimit', 'local_quickquiz'),
        get_string('settings_defaulttimelimit_desc', 'local_quickquiz'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'local_quickquiz/proctorheading',
        get_string('settings_proctorheading', 'local_quickquiz'),
        get_string('settings_proctorheading_desc', 'local_quickquiz')
    ));

    $settings->add(new admin_setting_configselect(
        'local_quickquiz/defaulttimeinterval',
        get_string('settings_defaulttimeinterval', 'local_quickquiz'),
        get_string('settings_defaulttimeinterval_desc', 'local_quickquiz'),
        local_quickquiz_time_interval_options(),
        30,
        PARAM_INT
    ));

    $thresholdopts = [];
    for ($i = 0; $i <= 50; $i += 5) {
        $thresholdopts[$i] = ($i === 0) ? get_string('settings_unlimited', 'local_quickquiz') : (string) $i;
    }
    $settings->add(new admin_setting_configselect(
        'local_quickquiz/defaultwarningthreshold',
        get_string('settings_defaultwarningthreshold', 'local_quickquiz'),
        get_string('settings_defaultwarningthreshold_desc', 'local_quickquiz'),
        $thresholdopts,
        0,
        PARAM_INT
    ));

    $yesno = [0 => get_string('no'), 1 => get_string('yes')];
    $proctorcheckboxes = [
        'defaultenableteacherproctor' => ['settings_defaultenableteacherproctor', 0],
        'defaultstoreallimages' => ['settings_defaultstoreallimages', 0],
        'defaultenableuploadidentity' => ['settings_defaultenableuploadidentity', 0],
        'defaultenableprofilematch' => ['settings_defaultenableprofilematch', 0],
        'defaultenablestudentvideo' => ['settings_defaultenablestudentvideo', 0],
        'defaultenableeyecheckreal' => ['settings_defaultenableeyecheckreal', 0],
        'defaultenablerecordaudio' => ['settings_defaultenablerecordaudio', 0],
        'defaultenableobjectdetect' => ['settings_defaultenableobjectdetect', 0],
    ];
    foreach ($proctorcheckboxes as $name => [$langkey, $default]) {
        $settings->add(new admin_setting_configselect(
            "local_quickquiz/{$name}",
            get_string($langkey, 'local_quickquiz'),
            '',
            $yesno,
            $default,
            PARAM_INT
        ));
    }
}
