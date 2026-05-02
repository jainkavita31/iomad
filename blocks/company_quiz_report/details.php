<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Detailed report for company quizzes/proctoring.
 *
 * @package   block_company_quiz_report
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$systemcontext = context_system::instance();
$companyoptions = [];
if (has_capability('moodle/site:config', $systemcontext)) {
    $companyoptions = $DB->get_records_sql_menu(
        "SELECT id, name
           FROM {company}
       ORDER BY name",
        []
    );
} else {
    $companyoptions = $DB->get_records_sql_menu(
        "SELECT DISTINCT c.id, c.name
           FROM {company} c
           JOIN {company_users} cu ON cu.companyid = c.id
          WHERE cu.userid = :userid
            AND cu.suspended = 0
       ORDER BY c.name",
        ['userid' => $USER->id]
    );
}

$requestedcompanyid = optional_param('companyid', 0, PARAM_INT);
$selectedcompanyid = 0;
if ($requestedcompanyid > 0 && array_key_exists($requestedcompanyid, $companyoptions)) {
    $selectedcompanyid = $requestedcompanyid;
} else {
    $defaultcompanyid = 0;
    if (!empty($SESSION->currenteditingcompany)) {
        $defaultcompanyid = (int) $SESSION->currenteditingcompany;
    } else {
        $defaultcompanyid = (int) $DB->get_field('company_users', 'companyid', ['userid' => $USER->id], IGNORE_MULTIPLE);
    }
    if ($defaultcompanyid > 0 && array_key_exists($defaultcompanyid, $companyoptions)) {
        $selectedcompanyid = $defaultcompanyid;
    } else if (!empty($companyoptions)) {
        $selectedcompanyid = (int) array_key_first($companyoptions);
    }
}

if (!$selectedcompanyid) {
    $PAGE->set_url('/blocks/company_quiz_report/details.php');
    $PAGE->set_context($systemcontext);
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('detailedreporttitle', 'block_company_quiz_report'));
    $PAGE->set_heading(get_string('detailedreporttitle', 'block_company_quiz_report'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nocompanyavailable', 'block_company_quiz_report'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$companycontext = \core\context\company::instance($selectedcompanyid);
require_capability('block/company_quiz_report:view', $companycontext);

$companyname = $companyoptions[$selectedcompanyid] ?? ('ID ' . $selectedcompanyid);

$PAGE->set_url('/blocks/company_quiz_report/details.php', ['companyid' => $selectedcompanyid]);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$PAGE->requires->css(new moodle_url('/blocks/company_quiz_report/styles.css'));
$PAGE->set_title(get_string('detailedreporttitle', 'block_company_quiz_report'));
$PAGE->set_heading(get_string('detailedreportheading', 'block_company_quiz_report', format_string($companyname)));
$PAGE->navbar->add(get_string('pluginname', 'block_company_quiz_report'));
$PAGE->navbar->add(get_string('detailedreporttitle', 'block_company_quiz_report'));

echo $OUTPUT->header();
echo html_writer::start_div('company-quiz-report-details-wrap');

if (count($companyoptions) > 1) {
    $selector = new single_select(
        new moodle_url('/blocks/company_quiz_report/details.php'),
        'companyid',
        $companyoptions,
        $selectedcompanyid
    );
    $selector->label = get_string('selectcompany', 'block_company_quiz_report');
    echo html_writer::div($OUTPUT->render($selector), 'company-quiz-report-details-toolbar');
}

$manager = $DB->get_manager();
if (!$manager->table_exists('quizaccess_quizproctoring') || !$manager->table_exists('quizaccess_main_proctor')) {
    echo $OUTPUT->notification(get_string('proctorpluginmissing', 'block_company_quiz_report'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$quizmoduleid = (int) $DB->get_field('modules', 'id', ['name' => 'quiz']);

$sql = "SELECT q.id AS id,
               c.id AS courseid,
               c.fullname AS coursename,
               q.id AS quizid,
               q.name AS quizname,
               cm.id AS cmid,
               (SELECT COUNT(DISTINCT qmpu.userid)
                  FROM {quizaccess_main_proctor} qmpu
                 WHERE qmpu.quizid = q.id
                   AND qmpu.deleted = 0
                   AND qmpu.image_status = :imagestatususers
                   AND qmpu.attemptid IS NOT NULL
                   AND qmpu.attemptid > 0) AS userscount,
               (SELECT COUNT(DISTINCT qmpa.attemptid)
                  FROM {quizaccess_main_proctor} qmpa
                 WHERE qmpa.quizid = q.id
                   AND qmpa.deleted = 0
                   AND qmpa.image_status = :imagestatusattempts
                   AND qmpa.attemptid IS NOT NULL
                   AND qmpa.attemptid > 0) AS attemptscount,
               (SELECT COUNT(DISTINCT qmp.attemptid)
                  FROM {quizaccess_main_proctor} qmp
                 WHERE qmp.quizid = q.id
                   AND qmp.deleted = 0
                   AND qmp.image_status = :imagestatus
                   AND qmp.isautosubmit = :isautosubmit
                   AND qmp.attemptid IS NOT NULL
                   AND qmp.attemptid > 0) AS proctorfailedcount,
               (SELECT COUNT(*)
                  FROM {quizaccess_proctor_data} pd
                 WHERE pd.quizid = q.id
                   AND pd.deleted = 0
                   AND pd.status != '') AS warningtriggeredcount
          FROM {company_course} cc
          JOIN {course} c ON c.id = cc.courseid
          JOIN {quiz} q ON q.course = c.id
          JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
          LEFT JOIN {course_modules} cm ON cm.instance = q.id
                                      AND cm.course = c.id
                                      AND cm.module = :quizmoduleid
                                      AND cm.deletioninprogress = 0
         WHERE cc.companyid = :companyid
           AND qp.enableproctoring = :enabled
      ORDER BY c.fullname, q.name";

$records = $DB->get_records_sql($sql, [
    'imagestatus' => 'M',
    'imagestatususers' => 'M',
    'imagestatusattempts' => 'M',
    'isautosubmit' => 1,
    'quizmoduleid' => $quizmoduleid,
    'companyid' => $selectedcompanyid,
    'enabled' => 1,
]);

if (empty($records)) {
    echo $OUTPUT->notification(get_string('nodetaileddata', 'block_company_quiz_report'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('tablecourse', 'block_company_quiz_report'),
    get_string('tablequiz', 'block_company_quiz_report'),
    get_string('tableuserscount', 'block_company_quiz_report'),
    get_string('tableattempts', 'block_company_quiz_report'),
    get_string('tableproctorfailed', 'block_company_quiz_report'),
    get_string('tablewarningtriggered', 'block_company_quiz_report'),
];
$table->attributes['class'] = 'generaltable company-quiz-report-details';
$table->colclasses = [
    'cqr-col-course',
    'cqr-col-quiz',
    'cqr-col-num',
    'cqr-col-num',
    'cqr-col-num',
    'cqr-col-num',
];
$table->data = [];

foreach ($records as $record) {
    $courselink = html_writer::link(
        new moodle_url('/course/view.php', ['id' => $record->courseid]),
        format_string($record->coursename)
    );

    if (!empty($record->cmid)) {
        $quizname = format_string($record->quizname);
        $quizlink = html_writer::link(
            new moodle_url('/mod/quiz/view.php', ['id' => $record->cmid]),
            $quizname
        );
    } else {
        $quizlink = format_string($record->quizname);
    }

    $table->data[] = [
        $courselink,
        $quizlink,
        (int) $record->userscount,
        (int) $record->attemptscount,
        (int) $record->proctorfailedcount,
        (int) $record->warningtriggeredcount,
    ];
}

echo html_writer::start_div('company-quiz-report-table-wrap');
echo html_writer::table($table);
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
