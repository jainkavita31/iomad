<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Library functions for local_quickquiz.
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Whether ProctorLink access rule is available.
 *
 * @return bool
 */
function local_quickquiz_proctoring_available(): bool {
    global $CFG;
    return file_exists($CFG->dirroot . '/mod/quiz/accessrule/quizproctoring/rule.php');
}

/**
 * Site administrator (may create quizzes in any course without enrolment).
 *
 * @return bool
 */
function local_quickquiz_user_is_site_admin(): bool {
    return is_siteadmin()
        || has_capability('moodle/site:config', context_system::instance());
}

/**
 * Whether the user may create a quick quiz in a course.
 *
 * @param int $courseid
 * @return bool
 */
function local_quickquiz_can_create_in_course(int $courseid): bool {
    if ($courseid < 1) {
        return false;
    }
    if (local_quickquiz_user_is_site_admin()) {
        return true;
    }
    $context = context_course::instance($courseid);
    return has_capability('local/quickquiz:create', $context)
        || has_capability('mod/quiz:add', $context)
        || has_capability('moodle/course:manageactivities', $context);
}

/**
 * Courses where the current user may use the quick quiz creator.
 *
 * Site administrators are not usually enrolled in courses; for them (and when
 * $restrictcourseids is set) courses are loaded from the database instead of enrolment.
 *
 * @param int[]|null $restrictcourseids Optional list of course ids to limit results (e.g. company courses).
 * @return stdClass[] courseid => course record
 */
function local_quickquiz_get_eligible_courses(?array $restrictcourseids = null): array {
    global $DB, $USER;

    $eligible = [];
    $restrictcourseids = $restrictcourseids !== null
        ? array_values(array_filter(array_map('intval', $restrictcourseids)))
        : null;

    if (local_quickquiz_user_is_site_admin()) {
        $sql = "SELECT id, shortname, fullname
                  FROM {course}
                 WHERE id > :siteid";
        $params = ['siteid' => SITEID];
        if ($restrictcourseids !== null && $restrictcourseids !== []) {
            [$insql, $inparams] = $DB->get_in_or_equal($restrictcourseids, SQL_PARAMS_NAMED, 'qc');
            $sql .= " AND id $insql";
            $params = array_merge($params, $inparams);
        }
        $sql .= ' ORDER BY fullname';
        foreach ($DB->get_records_sql($sql, $params) as $course) {
            $eligible[$course->id] = $course;
        }
        return $eligible;
    }

    $courses = enrol_get_users_courses($USER->id, true, 'id,shortname,fullname');
    foreach ($courses as $course) {
        $cid = (int) $course->id;
        if ($restrictcourseids !== null && $restrictcourseids !== [] && !in_array($cid, $restrictcourseids, true)) {
            continue;
        }
        if (local_quickquiz_can_create_in_course($cid)) {
            $eligible[$cid] = $course;
        }
    }

    // Role assignments without enrolment (e.g. manager in company course).
    if ($restrictcourseids !== null && $restrictcourseids !== []) {
        foreach ($restrictcourseids as $cid) {
            if (isset($eligible[$cid])) {
                continue;
            }
            if (!local_quickquiz_can_create_in_course($cid)) {
                continue;
            }
            $course = $DB->get_record('course', ['id' => $cid], 'id,shortname,fullname', IGNORE_MISSING);
            if ($course) {
                $eligible[$cid] = $course;
            }
        }
    }

    uasort($eligible, static function ($a, $b) {
        return strcasecmp($a->fullname, $b->fullname);
    });
    return $eligible;
}

/**
 * Company course ids for an IOMAD organisation.
 *
 * @param int $companyid
 * @return int[]
 */
function local_quickquiz_company_course_ids(int $companyid): array {
    global $DB;
    if ($companyid < 1) {
        return [];
    }
    return array_map('intval', $DB->get_fieldset_sql(
        "SELECT cc.courseid
           FROM {company_course} cc
          WHERE cc.companyid = :companyid
       ORDER BY cc.courseid",
        ['companyid' => $companyid]
    ));
}

/**
 * Default grade category id for a course (Uncategorised).
 *
 * @param int $courseid
 * @return int
 */
function local_quickquiz_default_gradecategory_id(int $courseid): int {
    require_once(__DIR__ . '/../../lib/gradelib.php');
    $category = grade_category::fetch_course_category($courseid);
    return $category ? (int) $category->id : 0;
}

/**
 * Default quiz activity fields (Moodle quiz module) for add_moduleinfo.
 *
 * @return stdClass
 */
function local_quickquiz_quiz_defaults(): stdClass {
    global $CFG;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    $defaults = [
        'timeopen' => 0,
        'timeclose' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 2,
        'questiondecimalpoints' => -1,
        'attemptduring' => 1,
        'correctnessduring' => 1,
        'maxmarksduring' => 1,
        'marksduring' => 1,
        'specificfeedbackduring' => 1,
        'generalfeedbackduring' => 1,
        'rightanswerduring' => 1,
        'overallfeedbackduring' => 0,
        'attemptimmediately' => 1,
        'correctnessimmediately' => 1,
        'maxmarksimmediately' => 1,
        'marksimmediately' => 1,
        'specificfeedbackimmediately' => 1,
        'generalfeedbackimmediately' => 1,
        'rightanswerimmediately' => 1,
        'overallfeedbackimmediately' => 1,
        'attemptopen' => 1,
        'correctnessopen' => 1,
        'maxmarksopen' => 1,
        'marksopen' => 1,
        'specificfeedbackopen' => 1,
        'generalfeedbackopen' => 1,
        'rightansweropen' => 1,
        'overallfeedbackopen' => 1,
        'attemptclosed' => 1,
        'correctnessclosed' => 1,
        'maxmarksclosed' => 1,
        'marksclosed' => 1,
        'specificfeedbackclosed' => 1,
        'generalfeedbackclosed' => 1,
        'rightanswerclosed' => 1,
        'overallfeedbackclosed' => 1,
        'questionsperpage' => 1,
        'shuffleanswers' => 1,
        'sumgrades' => 0,
        'timelimit' => 0,
        'overduehandling' => 'autosubmit',
        'graceperiod' => 86400,
        'quizpassword' => '',
        'subnet' => '',
        'browsersecurity' => '-',
        'delay1' => 0,
        'delay2' => 0,
        'showuserpicture' => 0,
        'showblocks' => 0,
        'navmethod' => QUIZ_NAVMETHOD_FREE,
        'intro' => '',
        'introformat' => FORMAT_HTML,
    ];

    return (object) $defaults;
}

/**
 * Build moduleinfo object for add_moduleinfo from form data.
 *
 * @param stdClass $course
 * @param stdClass $data Form data.
 * @return stdClass
 */
function local_quickquiz_prepare_moduleinfo(stdClass $course, stdClass $data): stdClass {
    global $DB;

    $moduleinfo = local_quickquiz_quiz_defaults();
    $moduleinfo->modulename = 'quiz';
    $moduleinfo->course = $course->id;
    $moduleinfo->section = (int) ($data->section ?? 0);
    $moduleinfo->visible = 1;
    $moduleinfo->visibleoncoursepage = 1;
    $moduleinfo->name = $data->name;
    $moduleinfo->grade = (float) $data->grade;
    $moduleinfo->gradecat = isset($data->gradecat) ? (int) $data->gradecat : local_quickquiz_default_gradecategory_id($course->id);
    if (isset($data->gradepass) && $data->gradepass !== '' && $data->gradepass !== null) {
        $moduleinfo->gradepass = (float) $data->gradepass;
    } else {
        $moduleinfo->gradepass = 0;
    }
    $moduleinfo->attempts = isset($data->attempts) ? (int) $data->attempts : 0;
    $moduleinfo->grademethod = isset($data->grademethod) ? (int) $data->grademethod : QUIZ_GRADEHIGHEST;
    $moduleinfo->timeopen = !empty($data->timeopen) ? (int) $data->timeopen : 0;
    $moduleinfo->timeclose = !empty($data->timeclose) ? (int) $data->timeclose : 0;
    $moduleinfo->timelimit = !empty($data->timelimit) ? (int) $data->timelimit : 0;
    $moduleinfo->quizpassword = !empty($data->quizpassword) ? (string) $data->quizpassword : '';

    if (local_quickquiz_proctoring_available()) {
        $proctordefaults = local_quickquiz_proctoring_defaults();
        $moduleinfo->enableproctoring = !empty($data->enableproctoring) ? 1 : 0;
        $proctorfields = [
            'enableteacherproctor',
            'storeallimages',
            'enableuploadidentity',
            'enableprofilematch',
            'enablestudentvideo',
            'enableeyecheckreal',
            'enableeyecheck',
            'enablerecordaudio',
            'enableobjectdetect',
            'time_interval',
            'warning_threshold',
            'warning_email_threshold',
            'warning_email_trigger_role',
            'proctoringvideo_link',
        ];
        foreach ($proctorfields as $field) {
            if ($field === 'warning_email_trigger_role') {
                continue;
            }
            if (property_exists($data, $field)) {
                $moduleinfo->$field = $data->$field;
            } else if (property_exists($proctordefaults, $field)) {
                $moduleinfo->$field = $proctordefaults->$field;
            }
        }
        // Must be > 0 before add_moduleinfo() or ProctorLink save_settings() hits broken role lookup.
        $moduleinfo->warning_email_trigger_role = local_quickquiz_resolve_warning_email_trigger_role($course, $data);
        if (!isset($moduleinfo->proctoringvideo_link)) {
            $moduleinfo->proctoringvideo_link = '';
        }
    }

    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);

    return $moduleinfo;
}

/**
 * Proctor image interval options (seconds => label).
 *
 * @return array<int|string, string>
 */
function local_quickquiz_time_interval_options(): array {
    $options = [];
    $intervals = [15, 20, 30, 60, 120, 180, 240, 300];
    foreach ($intervals as $seconds) {
        $localkey = 'interval_' . $seconds . 's';
        if (get_string_manager()->string_exists($localkey, 'local_quickquiz')) {
            $options[$seconds] = get_string($localkey, 'local_quickquiz');
        } else if (local_quickquiz_proctoring_available()) {
            $map = [
                15 => 'fiftenseconds',
                20 => 'twentyseconds',
                30 => 'thirtyseconds',
                60 => 'oneminute',
                120 => 'twominutes',
                180 => 'threeminutes',
                240 => 'fourminutes',
                300 => 'fiveminutes',
            ];
            $options[$seconds] = get_string($map[$seconds], 'quizaccess_quizproctoring');
        } else {
            $options[$seconds] = $seconds . 's';
        }
    }
    return $options;
}

/**
 * Write default plugin config values when missing (avoids upgrade "New settings" loop).
 *
 * @return void
 */
function local_quickquiz_seed_default_config(): void {
    $defaults = [
        'defaultgrade' => 100,
        'defaulttimelimit' => 0,
        'defaulttimeinterval' => 30,
        'defaultwarningthreshold' => 0,
        'defaultenableteacherproctor' => 0,
        'defaultstoreallimages' => 0,
        'defaultenableuploadidentity' => 0,
        'defaultenableprofilematch' => 0,
        'defaultenablestudentvideo' => 0,
        'defaultenableeyecheckreal' => 0,
        'defaultenablerecordaudio' => 0,
        'defaultenableobjectdetect' => 0,
    ];

    foreach ($defaults as $name => $value) {
        if (get_config('local_quickquiz', $name) === false) {
            set_config($name, $value, 'local_quickquiz');
        }
    }
}

/**
 * Warning threshold options for forms and admin settings.
 *
 * @return array<int, string>
 */
function local_quickquiz_warning_threshold_options(): array {
    $thresholds = [];
    for ($i = 0; $i <= 50; $i += 5) {
        $thresholds[$i] = ($i === 0) ? get_string('settings_unlimited', 'local_quickquiz') : (string) $i;
    }
    return $thresholds;
}

/**
 * Read an integer plugin config value with fallback.
 *
 * @param string $name Config name without plugin prefix.
 * @param int $fallback
 * @return int
 */
function local_quickquiz_config_int(string $name, int $fallback): int {
    $value = get_config('local_quickquiz', $name);
    if ($value === false || $value === null || $value === '') {
        return $fallback;
    }
    return (int) $value;
}

/**
 * Default ProctorLink values for new quick quizzes (from this plugin's settings only).
 *
 * @return stdClass
 */
function local_quickquiz_proctoring_defaults(): stdClass {
    return (object) [
        // Quick create always starts with proctoring off; teachers opt in per quiz.
        'enableproctoring' => 0,
        'enableteacherproctor' => local_quickquiz_config_int('defaultenableteacherproctor', 0),
        'storeallimages' => local_quickquiz_config_int('defaultstoreallimages', 0),
        'enableuploadidentity' => local_quickquiz_config_int('defaultenableuploadidentity', 0),
        'enableprofilematch' => local_quickquiz_config_int('defaultenableprofilematch', 0),
        'enablestudentvideo' => local_quickquiz_config_int('defaultenablestudentvideo', 0),
        'enableeyecheckreal' => local_quickquiz_config_int('defaultenableeyecheckreal', 0),
        'enableeyecheck' => 0,
        'enablerecordaudio' => local_quickquiz_config_int('defaultenablerecordaudio', 0),
        'enableobjectdetect' => local_quickquiz_config_int('defaultenableobjectdetect', 0),
        'time_interval' => local_quickquiz_config_int('defaulttimeinterval', 30),
        'warning_threshold' => local_quickquiz_config_int('defaultwarningthreshold', 0),
        'warning_email_threshold' => 0,
        'proctoringvideo_link' => '',
    ];
}

/**
 * Resolve a valid warning email trigger role id (> 0) without calling ProctorLink helpers.
 *
 * ProctorLink save_settings() calls get_first_trigger_role_id() when this is 0, which can
 * fail on some builds. Quick quiz creator always supplies a role id instead.
 *
 * @param stdClass $course
 * @param stdClass|null $data Optional form data.
 * @return int
 */
function local_quickquiz_resolve_warning_email_trigger_role(stdClass $course, ?stdClass $data = null): int {
    global $DB;

    if ($data !== null && !empty($data->warning_email_trigger_role)) {
        return (int) $data->warning_email_trigger_role;
    }

    $context = context_course::instance($course->id, IGNORE_MISSING);
    if ($context) {
        $roles = get_assignable_roles($context);
        if (!empty($roles)) {
            return (int) array_key_first($roles);
        }
    }

    $roleid = $DB->get_field_sql(
        "SELECT id
           FROM {role}
          WHERE archetype IS NULL OR archetype <> :guest
       ORDER BY sortorder ASC",
        ['guest' => 'guest'],
        IGNORE_MISSING
    );
    if ($roleid) {
        return (int) $roleid;
    }

    $studentid = $DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
    return $studentid ? (int) $studentid : 0;
}

/**
 * Add ProctorLink form fields (same options as mod_quiz; defaults from local_quickquiz settings).
 *
 * @param MoodleQuickForm $mform
 * @param stdClass $course Course record for role choices.
 * @return void
 */
function local_quickquiz_add_proctoring_form_fields(MoodleQuickForm $mform, stdClass $course): void {
    global $DB;

    $defaults = local_quickquiz_proctoring_defaults();

    $mform->addElement('selectyesno', 'enableproctoring', get_string('enableproctoring', 'quizaccess_quizproctoring'));
    $mform->addHelpButton('enableproctoring', 'enableproctoring', 'quizaccess_quizproctoring');
    $mform->setDefault('enableproctoring', 0);

    $mform->addElement(
        'selectyesno',
        'enableteacherproctor',
        get_string('enableteacherproctor', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enableteacherproctor', 'enableteacherproctor', 'quizaccess_quizproctoring');
    $mform->setDefault('enableteacherproctor', $defaults->enableteacherproctor);
    $mform->hideIf('enableteacherproctor', 'enableproctoring', 'eq', 0);

    $mform->addElement('selectyesno', 'storeallimages', get_string('storeallimages', 'quizaccess_quizproctoring'));
    $mform->addHelpButton('storeallimages', 'storeallimages', 'quizaccess_quizproctoring');
    $mform->setDefault('storeallimages', $defaults->storeallimages);
    $mform->hideIf('storeallimages', 'enableproctoring', 'eq', 0);

    $mform->addElement(
        'selectyesno',
        'enableuploadidentity',
        get_string('enableuploadidentity', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enableuploadidentity', 'enableuploadidentity', 'quizaccess_quizproctoring');
    $mform->setDefault('enableuploadidentity', $defaults->enableuploadidentity);
    $mform->hideIf('enableuploadidentity', 'enableproctoring', 'eq', 0);

    $mform->addElement(
        'selectyesno',
        'enableprofilematch',
        get_string('enableprofilematch', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enableprofilematch', 'enableprofilematch', 'quizaccess_quizproctoring');
    $mform->setDefault('enableprofilematch', $defaults->enableprofilematch);
    $mform->hideIf('enableprofilematch', 'enableproctoring', 'eq', 0);

    $mform->addElement(
        'selectyesno',
        'enablestudentvideo',
        get_string('enablestudentvideo', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enablestudentvideo', 'enablestudentvideo', 'quizaccess_quizproctoring');
    $mform->setDefault('enablestudentvideo', $defaults->enablestudentvideo);
    $mform->hideIf('enablestudentvideo', 'enableproctoring', 'eq', 0);

    $mform->addElement(
        'selectyesno',
        'enableeyecheckreal',
        get_string('enableeyecheckreal', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enableeyecheckreal', 'enableeyecheckreal', 'quizaccess_quizproctoring');
    $mform->setDefault('enableeyecheckreal', $defaults->enableeyecheckreal);
    $mform->hideIf('enableeyecheckreal', 'enableproctoring', 'eq', 0);

    $mform->addElement('textarea', 'eyecheckrealnote', '');
    $mform->setDefault('eyecheckrealnote', get_string('eyecheckrealnote', 'quizaccess_quizproctoring'));
    $mform->freeze('eyecheckrealnote');
    $mform->hideIf('eyecheckrealnote', 'enableproctoring', 'eq', 0);
    $mform->hideIf('eyecheckrealnote', 'enableeyecheckreal', 'eq', 0);

    $mform->addElement(
        'selectyesno',
        'enablerecordaudio',
        get_string('enablerecordaudio', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enablerecordaudio', 'enablerecordaudio', 'quizaccess_quizproctoring');
    $mform->setDefault('enablerecordaudio', $defaults->enablerecordaudio);
    $mform->hideIf('enablerecordaudio', 'enableproctoring', 'eq', 0);

    $mform->addElement(
        'selectyesno',
        'enableobjectdetect',
        get_string('enableobjectdetect', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('enableobjectdetect', 'enableobjectdetect', 'quizaccess_quizproctoring');
    $mform->setDefault('enableobjectdetect', $defaults->enableobjectdetect);
    $mform->hideIf('enableobjectdetect', 'enableproctoring', 'eq', 0);

    $mform->addElement('hidden', 'enableeyecheck', 0);
    $mform->setType('enableeyecheck', PARAM_INT);
    $mform->setDefault('enableeyecheck', $defaults->enableeyecheck);

    $intervaloptions = local_quickquiz_time_interval_options();
    $mform->addElement(
        'select',
        'time_interval',
        get_string('proctoringtimeinterval', 'quizaccess_quizproctoring'),
        $intervaloptions
    );
    $mform->addHelpButton('time_interval', 'proctoringtimeinterval', 'quizaccess_quizproctoring');
    $intervaldefault = $defaults->time_interval;
    if (!array_key_exists($intervaldefault, $intervaloptions)) {
        $intervaldefault = 30;
    }
    $mform->setDefault('time_interval', $intervaldefault);
    $mform->hideIf('time_interval', 'enableproctoring', 'eq', 0);

    $mform->addElement(
        'select',
        'warning_threshold',
        get_string('warning_threshold', 'quizaccess_quizproctoring'),
        local_quickquiz_warning_threshold_options()
    );
    $mform->addHelpButton('warning_threshold', 'warning_threshold', 'quizaccess_quizproctoring');
    $mform->setDefault('warning_threshold', $defaults->warning_threshold);
    $mform->hideIf('warning_threshold', 'enableproctoring', 'eq', 0);

    $emailthresholds = [0 => get_string('disabled', 'quizaccess_quizproctoring')];
    for ($i = 5; $i <= 30; $i += 5) {
        $emailthresholds[$i] = (string) $i;
    }
    $mform->addElement(
        'select',
        'warning_email_threshold',
        get_string('warning_email_threshold', 'quizaccess_quizproctoring'),
        $emailthresholds
    );
    $mform->addHelpButton('warning_email_threshold', 'warning_email_threshold', 'quizaccess_quizproctoring');
    $mform->setDefault('warning_email_threshold', $defaults->warning_email_threshold);
    $mform->hideIf('warning_email_threshold', 'enableproctoring', 'eq', 0);
    $mform->hideIf('warning_email_threshold', 'warning_threshold', 'neq', 0);

    $rolechoices = [];
    $context = context_course::instance($course->id);
    $roles = get_assignable_roles($context);
    foreach ($roles as $roleid => $rolename) {
        $rolechoices[$roleid] = $rolename;
    }
    $defaultroleid = local_quickquiz_resolve_warning_email_trigger_role($course, null);
    if ($defaultroleid > 0 && empty($rolechoices)) {
        $rolename = $DB->get_field('role', 'name', ['id' => $defaultroleid], IGNORE_MISSING);
        if ($rolename) {
            $rolechoices[$defaultroleid] = $rolename;
        }
    }
    $mform->addElement(
        'select',
        'warning_email_trigger_role',
        get_string('warning_email_trigger_role', 'quizaccess_quizproctoring'),
        $rolechoices
    );
    $mform->addHelpButton('warning_email_trigger_role', 'warning_email_trigger_role', 'quizaccess_quizproctoring');
    $mform->setDefault('warning_email_trigger_role', $defaultroleid);
    $mform->hideIf('warning_email_trigger_role', 'enableproctoring', 'eq', 0);
    $mform->hideIf('warning_email_trigger_role', 'warning_threshold', 'neq', 0);

    $mform->addElement(
        'text',
        'proctoringvideo_link',
        get_string('proctoring_videolink', 'quizaccess_quizproctoring')
    );
    $mform->addHelpButton('proctoringvideo_link', 'proctoringlink', 'quizaccess_quizproctoring');
    $mform->setType('proctoringvideo_link', PARAM_URL);
    $mform->setDefault('proctoringvideo_link', $defaults->proctoringvideo_link);
    $mform->hideIf('proctoringvideo_link', 'enableproctoring', 'eq', 0);
}

/**
 * Apply ProctorLink settings after quiz instance exists.
 *
 * @param int $quizid Quiz instance id.
 * @param stdClass $course Course record.
 * @param stdClass $data Form data.
 * @return void
 */
function local_quickquiz_apply_proctoring_settings(int $quizid, stdClass $course, stdClass $data): void {
    global $CFG;

    if (!local_quickquiz_proctoring_available()) {
        return;
    }

    require_once($CFG->dirroot . '/mod/quiz/accessrule/quizproctoring/rule.php');

    $quiz = local_quickquiz_proctoring_defaults();
    $quiz->id = $quizid;
    $quiz->enableproctoring = !empty($data->enableproctoring) ? 1 : 0;

    $fields = [
        'enableteacherproctor',
        'storeallimages',
        'enableuploadidentity',
        'enableprofilematch',
        'enablestudentvideo',
        'enableeyecheckreal',
        'enableeyecheck',
        'enablerecordaudio',
        'enableobjectdetect',
        'time_interval',
        'warning_threshold',
        'warning_email_threshold',
        'proctoringvideo_link',
    ];
    foreach ($fields as $field) {
        if (property_exists($data, $field)) {
            $quiz->$field = $data->$field;
        }
    }
    $quiz->warning_email_trigger_role = local_quickquiz_resolve_warning_email_trigger_role($course, $data);
    if (!isset($quiz->proctoringvideo_link)) {
        $quiz->proctoringvideo_link = '';
    }

    quizaccess_quizproctoring::save_settings($quiz);
}

/**
 * Create a quiz via add_moduleinfo and optional proctoring setup.
 *
 * @param stdClass $course
 * @param stdClass $data Form data.
 * @return stdClass course module record fields: id (cmid), instance (quizid), name
 * @throws moodle_exception
 */
function local_quickquiz_create_quiz(stdClass $course, stdClass $data): stdClass {
    global $CFG;

    require_once($CFG->dirroot . '/course/modlib.php');

    $moduleinfo = local_quickquiz_prepare_moduleinfo($course, $data);
    $moduleinfo = add_moduleinfo($moduleinfo, $course);

    if (!empty($data->enableproctoring) && local_quickquiz_proctoring_available()) {
        local_quickquiz_apply_proctoring_settings((int) $moduleinfo->instance, $course, $data);
    }

    rebuild_course_cache($course->id, true);

    $result = new stdClass();
    $result->id = (int) $moduleinfo->coursemodule;
    $result->instance = (int) $moduleinfo->instance;
    $result->name = $data->name;
    return $result;
}

/**
 * Add course navigation node.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @return void
 */
function local_quickquiz_extend_navigation_course($navigation, $course) {
    $courseid = (int) $course->id;
    if (!local_quickquiz_can_create_in_course($courseid)) {
        return;
    }

    $url = new moodle_url('/local/quickquiz/index.php', ['courseid' => $courseid]);
    $navigation->add(
        get_string('createquiz', 'local_quickquiz'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_quickquiz_create'
    );
}
