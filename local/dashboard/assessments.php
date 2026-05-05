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
 * Full assessment health list for the exam dashboard.
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
$fromtime = $r->fromtime;

// Always list every proctored assessment for the company on this page.
$baseparamsall = [
    'companyid' => $companyid,
    'fromtime' => $fromtime,
];
$quizsqlall = '';

$pageurl = new moodle_url('/local/dashboard/assessments.php', [
    'companyid' => $companyid,
    'timerange' => $timerange,
    'quizid' => 0,
]);
$PAGE->set_url($pageurl);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('assessmentspagetitle', 'local_dashboard'));
$PAGE->set_heading(get_string('assessmentspagetitle', 'local_dashboard'));
$PAGE->requires->css(new moodle_url('/local/dashboard/styles.css'));

$assessmentstats = local_dashboard_fetch_assessment_stats($companyid, $fromtime, $quizsqlall, $baseparamsall);

echo $OUTPUT->header();
echo html_writer::start_div('local-dashboard ld-detail-page');

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/dashboard/index.php', [
            'companyid' => $companyid,
            'timerange' => $timerange,
            'quizid' => $quizid,
        ]),
        get_string('backtodashboard', 'local_dashboard'),
        ['class' => 'ld-back-link']
    ),
    'ld-detail-nav'
);

echo html_writer::start_div('ld-page-intro ld-detail-intro');
echo html_writer::tag('h2', get_string('assessmentspagetitle', 'local_dashboard'), ['class' => 'ld-page-title']);
echo html_writer::div(
    $companyname . ' · ' . ($timerangeoptions[$timerange] ?? '') . ' · ' . get_string('assessmentslistall', 'local_dashboard'),
    'ld-page-updated'
);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/dashboard/assessments.php'), 'class' => 'ld-filters']);
echo local_dashboard_filter_company_controls_html($r);
echo html_writer::start_div('ld-filter-item');
echo html_writer::tag('label', get_string('filtertimerange', 'local_dashboard'), ['for' => 'id_timerange']);
echo html_writer::select($timerangeoptions, 'timerange', $timerange, false, ['id' => 'id_timerange']);
echo html_writer::end_div();
echo html_writer::start_div('ld-filter-actions');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('applyfilters', 'local_dashboard'), 'class' => 'btn btn-primary']);
echo html_writer::link(new moodle_url('/local/dashboard/assessments.php'), get_string('resetfilters', 'local_dashboard'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::div(
    get_string('assessmentrowcount', 'local_dashboard', count($assessmentstats)),
    'ld-detail-meta ld-detail-summary'
);
echo html_writer::div(get_string('assessmentsdetailtableintro', 'local_dashboard'), 'ld-assessments-table-intro');

usort($assessmentstats, static function ($a, $b) {
    $c = ((int) $b->alerts) <=> ((int) $a->alerts);
    if ($c !== 0) {
        return $c;
    }
    return ((int) $b->flaggedunion) <=> ((int) $a->flaggedunion);
});

$table = new html_table();
$table->head = [
    get_string('tableassessment', 'local_dashboard'),
    get_string('tablecourse', 'local_dashboard'),
    get_string('statcandidates', 'local_dashboard'),
    get_string('tableattempts', 'local_dashboard'),
    get_string('tablecompletedpct', 'local_dashboard'),
    get_string('statflagged', 'local_dashboard'),
    get_string('tablealerts', 'local_dashboard'),
    get_string('tablefailed', 'local_dashboard'),
    get_string('tablescore', 'local_dashboard'),
    get_string('tablepctcleared', 'local_dashboard'),
    get_string('tablepctwarning', 'local_dashboard'),
    get_string('tablepcthighriskcol', 'local_dashboard'),
];
$table->attributes['class'] = 'generaltable ld-table ld-detail-table ld-assessments-detail-table';
$table->data = [];
foreach ($assessmentstats as $row) {
    $quizcell = local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quiznameraw);
    if (!empty($row->unusual)) {
        $quizcell .= ' ' . html_writer::span(
            get_string('unusualactivity', 'local_dashboard'),
            'ld-pill ld-pill-stat-pending'
        );
    }
    $table->data[] = [
        $quizcell,
        $row->course,
        number_format($row->users),
        number_format($row->attempts),
        format_float($row->completedpct, 1) . '%',
        number_format($row->flaggedunion),
        number_format($row->alerts),
        number_format($row->failed),
        format_float($row->score, 1) . '%',
        format_float($row->clearedpct, 1) . '%',
        format_float($row->orangepct, 1) . '%',
        format_float($row->highriskpct, 1) . '%',
    ];
}
if (empty($table->data)) {
    $table->data[] = array_merge(
        [get_string('nofiltereddata', 'local_dashboard')],
        array_fill(0, count($table->head) - 1, '')
    );
}

echo html_writer::start_div('ld-assessments-table-wrap');
echo html_writer::table($table);
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
