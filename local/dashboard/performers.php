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
 * Full candidate ranking (top performers) for the exam dashboard.
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
$companyname = $r->companyname;
$timerange = $r->timerange;
$quizid = $r->quizid;
$timerangeoptions = $r->timerangeoptions;
$quizoptions = $r->quizoptions;
$fromtime = $r->fromtime;
$baseparams = $r->baseparams;
$quizsql = $r->quizsql;

$pageurl = new moodle_url('/local/dashboard/performers.php', local_dashboard_filter_url_params($r));
$PAGE->set_url($pageurl);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('performerspagetitle', 'local_dashboard'));
$PAGE->set_heading(get_string('performerspagetitle', 'local_dashboard'));
$PAGE->requires->css(new moodle_url('/local/dashboard/styles.css'));
local_dashboard_require_datatables();

$topperformers = local_dashboard_fetch_ranked_scores_rows($quizsql, $baseparams);

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
echo html_writer::tag('h2', get_string('performerspagetitle', 'local_dashboard'), ['class' => 'ld-page-title']);
echo html_writer::div(
    $companyname . ' · ' . get_string('detailfiltercontext', 'local_dashboard', (object) [
        'timerange' => $timerangeoptions[$timerange] ?? '',
        'quiz' => $quizoptions[$quizid] ?? '',
    ]),
    'ld-page-updated'
);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/dashboard/performers.php'), 'class' => 'ld-filters']);
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
echo html_writer::link(new moodle_url('/local/dashboard/performers.php'), get_string('resetfilters', 'local_dashboard'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::div(
    get_string('performersrowcount', 'local_dashboard', count($topperformers)),
    'ld-detail-meta ld-detail-summary'
);

$performertable = new html_table();
$performertable->head = [
    get_string('rank', 'local_dashboard'),
    get_string('candidate', 'local_dashboard'),
    get_string('score', 'local_dashboard'),
    get_string('session', 'local_dashboard'),
];
$performertable->attributes['class'] = 'generaltable ld-table ld-detail-table';
$performertable->attributes['id'] = 'ld-performers-datatable';
$performertable->data = [];
$rank = 1;
foreach ($topperformers as $row) {
    $alertcount = (int) $row->alertcount;
    $statuspill = $alertcount > 0
        ? html_writer::span(get_string('statusalertcount', 'local_dashboard', $alertcount), 'ld-pill ld-pill-stat-pending')
        : html_writer::span(get_string('statusclean', 'local_dashboard'), 'ld-pill ld-pill-stat-clean');
    $name = fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname]);
    $candidcell = html_writer::div($name, 'ld-candidate-name') .
        html_writer::div(
            local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quizname),
            'ld-candidate-quiz'
        );
    $performertable->data[] = [
        html_writer::span((string) $rank++, 'ld-rank-box'),
        $candidcell,
        html_writer::span(format_float((float) $row->bestscore, 1) . '%', 'ld-score-cell'),
        $statuspill,
    ];
}
if (empty($performertable->data)) {
    $performertable->data[] = [get_string('nofiltereddata', 'local_dashboard'), '', '', ''];
}
echo html_writer::table($performertable);

local_dashboard_init_datatable('#ld-performers-datatable', 50, [
    // Keep server rank order (score, then alerts, then attempts).
    'order' => [[0, 'asc']],
    'columndefs' => [
        ['orderable' => false, 'targets' => [0, 1, 3]],
    ],
]);

echo html_writer::end_div();
echo $OUTPUT->footer();
