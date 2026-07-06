<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Quick quiz creator — course picker and create form.
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/form/create_form.php');

use local_quickquiz\form\create_form;

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$companyid = optional_param('companyid', 0, PARAM_INT);

$restrictids = null;
if ($companyid > 0) {
    $restrictids = local_quickquiz_company_course_ids($companyid);
}

// Direct link with course (e.g. from exam dashboard): allow site admins without enrolment.
if ($courseid > 0 && local_quickquiz_can_create_in_course($courseid)) {
    $eligible = [$courseid => get_course($courseid, false)];
} else {
    $eligible = local_quickquiz_get_eligible_courses($restrictids);
}

if (empty($eligible)) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/local/quickquiz/index.php');
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('createquiz', 'local_quickquiz'));
    $PAGE->set_heading(get_string('createquiz', 'local_quickquiz'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nocourses', 'local_quickquiz'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if ($courseid && !isset($eligible[$courseid])) {
    throw new moodle_exception('cannotaccesscourse');
}

if (!$courseid) {
    $PAGE->set_context(context_system::instance());
    $pageparams = [];
    if ($companyid > 0) {
        $pageparams['companyid'] = $companyid;
    }
    $PAGE->set_url(new moodle_url('/local/quickquiz/index.php', $pageparams));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('selectcourse', 'local_quickquiz'));
    $PAGE->set_heading(get_string('createquiz', 'local_quickquiz'));

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('selectcourse', 'local_quickquiz'), 3);
    echo html_writer::tag('p', get_string('selectcoursehelp', 'local_quickquiz'));

    $options = [];
    foreach ($eligible as $course) {
        $options[$course->id] = format_string($course->fullname);
    }
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/quickquiz/index.php')]);
    if ($companyid > 0) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'companyid', 'value' => $companyid]);
    }
    echo html_writer::label(get_string('course', 'local_quickquiz'), 'courseid');
    echo html_writer::select($options, 'courseid', '', ['' => get_string('choose')]);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('continue', 'local_quickquiz'), 'class' => 'btn btn-primary']);
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);

if (!local_quickquiz_can_create_in_course($courseid)) {
    throw new require_capability_exception($context, 'local/quickquiz:create', 'nopermissions', '');
}

$PAGE->set_url('/local/quickquiz/index.php', array_filter([
    'courseid' => $courseid,
    'companyid' => $companyid > 0 ? $companyid : null,
]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
if (local_quickquiz_proctoring_available()) {
    $PAGE->requires->css('/mod/quiz/accessrule/quizproctoring/styles.css');
}
$PAGE->set_title(get_string('createquiz', 'local_quickquiz'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('createquiz', 'local_quickquiz'));

$hasproctoring = local_quickquiz_proctoring_available();
$form = new create_form(null, [
    'course' => $course,
    'hasproctoring' => $hasproctoring,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

if ($data = $form->get_data()) {
    require_sesskey();
    $cm = local_quickquiz_create_quiz($course, $data);
    $quizurl = new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
    redirect(
        $quizurl,
        get_string('createsuccess', 'local_quickquiz', $data->name),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('createquizheading', 'local_quickquiz'), 3);
$form->display();
echo $OUTPUT->footer();
