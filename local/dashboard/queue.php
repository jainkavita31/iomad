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
 * Full priority review queue for the exam dashboard.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$r = local_dashboard_bootstrap_report();
if ($r === null) {
    exit;
}

$companyid = $r->companyid;
$companycontext = $r->companycontext;
$companyoptions = $r->companyoptions;
$companyname = $r->companyname;
$timerange = $r->timerange;
$quizid = $r->quizid;
$timerangeoptions = $r->timerangeoptions;
$quizoptions = $r->quizoptions;
$fromtime = $r->fromtime;
$baseparams = $r->baseparams;
$quizsql = $r->quizsql;

$pageurl = new moodle_url('/local/dashboard/queue.php', local_dashboard_filter_url_params($r));
$PAGE->set_url($pageurl);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('queuepagetitle', 'local_dashboard'));
$PAGE->set_heading(get_string('queuepagetitle', 'local_dashboard'));
$PAGE->requires->css(new moodle_url('/local/dashboard/styles.css'));
local_dashboard_require_datatables();

$queueparams = $baseparams;
$ldpend = local_dashboard_review_log_pending_only_sql_parts();
$priorityqueue = $DB->get_records_sql(
    "SELECT qmp.attemptid,
            u.id AS userid,
            u.firstname,
            u.lastname,
            q.id AS quizid,
            q.name AS quizname,
            SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) AS alertcount,
            MAX(qmp.isautosubmit) AS isautosubmit
       FROM {quizaccess_main_proctor} qmp
       JOIN {quiz_attempts} qa ON qa.id = qmp.attemptid
       JOIN {user} u ON u.id = qa.userid
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
       LEFT JOIN {quizaccess_proctor_data} pd ON pd.attemptid = qmp.attemptid
                                          AND pd.quizid = q.id
            {$ldpend['join']}
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        AND qmp.deleted = 0
        AND qmp.image_status = 'M'
        {$ldpend['where']}
        $quizsql
   GROUP BY qmp.attemptid, u.id, u.firstname, u.lastname, q.id, q.name
  HAVING SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) > 0
   ORDER BY alertcount DESC, isautosubmit DESC",
    $queueparams
);

echo $OUTPUT->header();
echo html_writer::start_div('local-dashboard ld-detail-page');

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/dashboard/index.php', local_dashboard_filter_url_params($r)),
        get_string('backtodashboard', 'local_dashboard'),
        ['class' => 'ld-back-link']
    ),
    'ld-detail-nav'
);

echo html_writer::start_div('ld-page-intro ld-detail-intro');
echo html_writer::tag('h2', get_string('queuepagetitle', 'local_dashboard'), ['class' => 'ld-page-title']);
echo html_writer::div(
    $companyname . ' · ' . get_string('detailfiltercontext', 'local_dashboard', (object) [
        'timerange' => $timerangeoptions[$timerange] ?? '',
        'quiz' => $quizoptions[$quizid] ?? '',
    ]),
    'ld-page-updated'
);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/dashboard/queue.php'), 'class' => 'ld-filters']);
echo local_dashboard_filter_company_controls_html($r);
echo html_writer::start_div('ld-filter-item');
echo html_writer::tag('label', get_string('filtertimerange', 'local_dashboard'), ['for' => 'id_timerange']);
echo html_writer::select($timerangeoptions, 'timerange', $timerange, false, ['id' => 'id_timerange']);
echo html_writer::end_div();
echo html_writer::start_div('ld-filter-item');
echo html_writer::tag('label', get_string('filterassessment', 'local_dashboard'), ['for' => 'id_quizid']);
echo html_writer::select($quizoptions, 'quizid', $quizid, false, ['id' => 'id_quizid']);
echo html_writer::end_div();
echo html_writer::start_div('ld-filter-actions');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('applyfilters', 'local_dashboard'), 'class' => 'btn btn-primary']);
echo html_writer::link(new moodle_url('/local/dashboard/queue.php'), get_string('resetfilters', 'local_dashboard'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::start_div('ld-detail-summary');
echo html_writer::div(
    get_string('queuerowcount', 'local_dashboard', count($priorityqueue)),
    'ld-detail-meta'
);
echo html_writer::end_div();

$queuetable = new html_table();
$queuetable->head = [
    get_string('rank', 'local_dashboard'),
    get_string('queuecandidate', 'local_dashboard'),
    get_string('queueassessment', 'local_dashboard'),
    get_string('queuealerts', 'local_dashboard'),
    get_string('queueseverity', 'local_dashboard'),
    get_string('queuereview', 'local_dashboard'),
];
$queuetable->attributes['class'] = 'generaltable ld-table ld-detail-table';
$queuetable->attributes['id'] = 'ld-queue-datatable';
$queuetable->data = [];
$rank = 1;
foreach ($priorityqueue as $row) {
    [$severitykey, $statuskey] = local_dashboard_queue_row_status_keys($row);
    $alerts = (int) $row->alertcount;
    $reviewlink = local_dashboard_proctor_reviewattempts_link_html(
        (int) $row->userid,
        (int) $row->quizid,
        [],
        (int) $companyid,
        (int) $row->attemptid
    );
    $sevpill = local_dashboard_queue_severity_pill_classes($severitykey);
    $queuetable->data[] = [
        html_writer::span((string) $rank++, 'ld-rank-box'),
        fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname]),
        local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quizname),
        '<span class="ld-td-alerts">' . $alerts . '</span>',
        html_writer::span(get_string($severitykey, 'local_dashboard'), $sevpill),
        $reviewlink,
    ];
}
if (empty($queuetable->data)) {
    $queuetable->data[] = [get_string('nofiltereddata', 'local_dashboard'), '', '', '', '', ''];
}
echo html_writer::table($queuetable);

local_dashboard_init_datatable('#ld-queue-datatable', 50, [
    'order' => [[3, 'desc']],
    'columndefs' => [
        ['orderable' => false, 'targets' => [0, 5]],
    ],
]);

echo html_writer::end_div();
echo $OUTPUT->footer();
