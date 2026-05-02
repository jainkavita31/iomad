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
 * Exam dashboard page.
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

$attemptfrom = " FROM {quiz_attempts} qa
                 JOIN {quiz} q ON q.id = qa.quiz
                 JOIN {company_course} cc ON cc.courseid = q.course
                 JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id ";
$attemptwhere = " WHERE cc.companyid = :companyid
                    AND qp.enableproctoring = 1
                    AND qa.preview = 0
                    AND qa.timestart >= :fromtime
                    $quizsql ";

$totalcandidates = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT qa.userid) " . $attemptfrom . $attemptwhere,
    $baseparams
);
$assessmentsconducted = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT q.id) " . $attemptfrom . $attemptwhere,
    $baseparams
);
$totalsessions = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT qa.id) " . $attemptfrom . $attemptwhere,
    $baseparams
);

$totalalerts = (int) $DB->count_records_sql(
    "SELECT COUNT(pd.id)
       FROM {quizaccess_proctor_data} pd
       JOIN {quiz_attempts} qa ON qa.id = pd.attemptid
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        AND pd.deleted = 0
        AND pd.status != ''
        $quizsql",
    $baseparams
);

$flaggedsessions = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT qa.id)
       FROM {quiz_attempts} qa
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        $quizsql
        AND EXISTS (
            SELECT 1
              FROM {quizaccess_proctor_data} pd
             WHERE pd.attemptid = qa.id
               AND pd.quizid = q.id
               AND pd.deleted = 0
               AND pd.status != ''
        )",
    $baseparams
);

$reviewbacklog = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT qmp.attemptid)
       FROM {quizaccess_main_proctor} qmp
       JOIN {quiz_attempts} qa ON qa.id = qmp.attemptid
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        AND qmp.deleted = 0
        AND qmp.image_status = 'M'
        AND qmp.isautosubmit = 1
        $quizsql",
    $baseparams
);

$avgscorerecord = $DB->get_record_sql(
    "SELECT AVG((qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0)) AS avgscore
       FROM {quiz_attempts} qa
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timefinish > 0
        AND qa.timestart >= :fromtime
        $quizsql",
    $baseparams
);
$averagescore = !empty($avgscorerecord->avgscore) ? (float) $avgscorerecord->avgscore : 0.0;

$statuscounts = [
    'tabswitch' => 0,
    'facemismatch' => 0,
    'absencedetected' => 0,
    'multiplepeople' => 0,
    'otheranomalies' => 0,
];
$statusrecords = $DB->get_records_sql(
    "SELECT pd.status, COUNT(pd.id) AS cnt
       FROM {quizaccess_proctor_data} pd
       JOIN {quiz_attempts} qa ON qa.id = pd.attemptid
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        AND pd.deleted = 0
        AND pd.status != ''
        $quizsql
   GROUP BY pd.status",
    $baseparams
);
foreach ($statusrecords as $statusrecord) {
    $status = strtolower((string) $statusrecord->status);
    $count = (int) $statusrecord->cnt;
    if (in_array($status, ['minimizedetected', 'appchange', 'tabswitch'], true)) {
        $statuscounts['tabswitch'] += $count;
    } else if (in_array($status, ['nomatchfound', 'facemismatch', 'profilemismatch'], true)) {
        $statuscounts['facemismatch'] += $count;
    } else if (in_array($status, ['nofacedetected', 'eyesnotopened', 'nocameradetected', 'nocameradisabled'], true)) {
        $statuscounts['absencedetected'] += $count;
    } else if ($status === 'multifacesdetected') {
        $statuscounts['multiplepeople'] += $count;
    } else {
        $statuscounts['otheranomalies'] += $count;
    }
}

$attemptriskrows = $DB->get_records_sql(
    "SELECT qmp.attemptid,
            MAX(qmp.isautosubmit) AS isautosubmit,
            SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) AS warningcount
       FROM {quizaccess_main_proctor} qmp
       JOIN {quiz_attempts} qa ON qa.id = qmp.attemptid
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
       LEFT JOIN {quizaccess_proctor_data} pd ON pd.attemptid = qmp.attemptid
                                          AND pd.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        AND qmp.deleted = 0
        AND qmp.image_status = 'M'
        $quizsql
   GROUP BY qmp.attemptid",
    $baseparams
);

$highriskpending = 0;
$mediumriskpending = 0;
$lowriskpending = 0;
foreach ($attemptriskrows as $attemptrow) {
    $warningcount = (int) $attemptrow->warningcount;
    $isautosubmit = (int) $attemptrow->isautosubmit;
    if ($isautosubmit || $warningcount >= 6) {
        $highriskpending++;
    } else if ($warningcount >= 3) {
        $mediumriskpending++;
    } else if ($warningcount >= 1) {
        $lowriskpending++;
    }
}
$autocleared = max($totalsessions - ($lowriskpending + $mediumriskpending + $highriskpending), 0);

$assessmentstats = local_dashboard_fetch_assessment_stats($companyid, $fromtime, $quizsql, $baseparams);

$scorestats = $DB->get_record_sql(
    "SELECT
            SUM(CASE WHEN scorepct >= 0 AND scorepct <= 40 THEN 1 ELSE 0 END) AS c0_40,
            SUM(CASE WHEN scorepct > 40 AND scorepct <= 50 THEN 1 ELSE 0 END) AS c41_50,
            SUM(CASE WHEN scorepct > 50 AND scorepct <= 60 THEN 1 ELSE 0 END) AS c51_60,
            SUM(CASE WHEN scorepct > 60 AND scorepct <= 75 THEN 1 ELSE 0 END) AS c61_75,
            SUM(CASE WHEN scorepct > 75 AND scorepct <= 90 THEN 1 ELSE 0 END) AS c76_90,
            SUM(CASE WHEN scorepct > 90 THEN 1 ELSE 0 END) AS c91_100,
            AVG(scorepct) AS avgscore,
            SUM(CASE WHEN scorepct >= 50 THEN 1 ELSE 0 END) AS passcount,
            COUNT(*) AS totalcount
       FROM (
            SELECT (qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0) AS scorepct
              FROM {quiz_attempts} qa
              JOIN {quiz} q ON q.id = qa.quiz
              JOIN {company_course} cc ON cc.courseid = q.course
              JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
             WHERE cc.companyid = :companyid
               AND qp.enableproctoring = 1
               AND qa.preview = 0
               AND qa.timefinish > 0
               AND qa.timestart >= :fromtime
               $quizsql
       ) scored",
    $baseparams
);

$scorevalues = $DB->get_fieldset_sql(
    "SELECT (qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0) AS scorepct
       FROM {quiz_attempts} qa
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timefinish > 0
        AND qa.timestart >= :fromtime
        $quizsql
   ORDER BY scorepct",
    $baseparams
);
$medianscore = 0.0;
if (!empty($scorevalues)) {
    $count = count($scorevalues);
    $mid = intdiv($count, 2);
    if ($count % 2) {
        $medianscore = (float) $scorevalues[$mid];
    } else {
        $medianscore = ((float) $scorevalues[$mid - 1] + (float) $scorevalues[$mid]) / 2;
    }
}
$passrate = !empty($scorestats->totalcount) ? (((float) $scorestats->passcount / (float) $scorestats->totalcount) * 100.0) : 0.0;

$topperformers = $DB->get_records_sql(
    "SELECT qa.userid,
            u.firstname,
            u.lastname,
            q.id AS quizid,
            q.name AS quizname,
            MAX((qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0)) AS bestscore,
            MAX(CASE WHEN qmp.isautosubmit = 1 THEN 1 ELSE 0 END) AS failedflag
       FROM {quiz_attempts} qa
       JOIN {user} u ON u.id = qa.userid
       JOIN {quiz} q ON q.id = qa.quiz
       JOIN {company_course} cc ON cc.courseid = q.course
       JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
       LEFT JOIN {quizaccess_main_proctor} qmp ON qmp.attemptid = qa.id
                                           AND qmp.deleted = 0
                                           AND qmp.image_status = 'M'
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timefinish > 0
        AND qa.timestart >= :fromtime
        $quizsql
   GROUP BY qa.userid, u.firstname, u.lastname, q.id, q.name
   ORDER BY bestscore DESC",
    $baseparams,
    0,
    10
);

$queueparams = $baseparams;
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
      WHERE cc.companyid = :companyid
        AND qp.enableproctoring = 1
        AND qa.preview = 0
        AND qa.timestart >= :fromtime
        AND qmp.deleted = 0
        AND qmp.image_status = 'M'
        $quizsql
   GROUP BY qmp.attemptid, u.id, u.firstname, u.lastname, q.id, q.name
   ORDER BY alertcount DESC, isautosubmit DESC",
    $queueparams,
    0,
    10
);

$pageurl = new moodle_url('/local/dashboard/index.php', [
    'timerange' => $timerange,
    'quizid' => $quizid,
    'companyid' => $companyid,
]);
$PAGE->set_url($pageurl);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('heading', 'local_dashboard'));
$PAGE->set_heading(get_string('heading', 'local_dashboard'));
$PAGE->requires->css(new moodle_url('/local/dashboard/styles.css'));

echo $OUTPUT->header();
echo html_writer::start_div('local-dashboard');

echo html_writer::start_div('ld-page-intro');
echo html_writer::tag('h2', get_string('heading', 'local_dashboard'), ['class' => 'ld-page-title']);
echo html_writer::div(
    get_string('lastupdated', 'local_dashboard', userdate(time(), get_string('strftimedatetimeshort', 'langconfig'))),
    'ld-page-updated'
);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/dashboard/index.php'), 'class' => 'ld-filters']);
echo html_writer::start_div('ld-filter-item');
$companylabel = get_string('selectcompany', 'local_dashboard');
if (str_starts_with($companylabel, '[[')) {
    $companylabel = 'Company';
}
echo html_writer::tag('label', $companylabel, ['for' => 'id_companyid']);
echo html_writer::select($companyoptions, 'companyid', $companyid, false, ['id' => 'id_companyid']);
echo html_writer::end_div();
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
echo html_writer::link(new moodle_url('/local/dashboard/index.php'), get_string('resetfilters', 'local_dashboard'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

$flaggedsessionspct = $totalsessions > 0 ? format_float(($flaggedsessions / $totalsessions) * 100.0, 1) : '0';
$kpis = [
    [
        'label' => 'totalcandidates',
        'value' => number_format($totalcandidates),
        'accent' => 'blue',
        'sub' => get_string('kpi_candidates_sub', 'local_dashboard'),
    ],
    [
        'label' => 'assessmentsconducted',
        'value' => number_format($assessmentsconducted),
        'accent' => 'navy',
        'sub' => get_string('kpi_assessmentssub', 'local_dashboard', $assessmentsconducted),
    ],
    [
        'label' => 'flaggedsessions',
        'value' => number_format($flaggedsessions),
        'accent' => 'red',
        'sub' => get_string('kpi_flaggedsub', 'local_dashboard', $flaggedsessionspct),
    ],
    [
        'label' => 'reviewbacklog',
        'value' => number_format($reviewbacklog),
        'accent' => 'orange',
        'sub' => get_string('kpi_backlogsub', 'local_dashboard', $highriskpending),
    ],
    [
        'label' => 'averagescore',
        'value' => format_float($averagescore, 1) . '%',
        'accent' => 'green',
        'sub' => get_string('kpi_scores_sub', 'local_dashboard', format_float($passrate, 1)),
    ],
];
echo html_writer::start_div('ld-kpi-grid');
foreach ($kpis as $kpi) {
    echo html_writer::start_div('ld-kpi ld-kpi-' . $kpi['accent']);
    echo html_writer::div(get_string($kpi['label'], 'local_dashboard'), 'ld-kpi-label');
    echo html_writer::div($kpi['value'], 'ld-kpi-value');
    echo html_writer::div($kpi['sub'], 'ld-kpi-sub');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo local_dashboard_section_heading('h3', 'reviewpipeline', 'pipeline');
echo html_writer::start_div('ld-pipeline-grid');
$pipelinecards = [
    ['totalsessions', $totalsessions, 'ld-pipeline-card--total'],
    ['autocleared', $autocleared, 'ld-pipeline-card--cleared'],
    ['lowriskpending', $lowriskpending, 'ld-pipeline-card--low'],
    ['mediumriskpending', $mediumriskpending, 'ld-pipeline-card--medium'],
    ['highriskpending', $highriskpending, 'ld-pipeline-card--high'],
];
foreach ($pipelinecards as [$label, $value, $cardclass]) {
    echo html_writer::start_div('ld-pipeline-card ' . $cardclass);
    echo html_writer::div(number_format($value), 'ld-pipeline-value');
    echo html_writer::div(get_string($label, 'local_dashboard'), 'ld-pipeline-label');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('ld-two-col');
echo html_writer::start_div('ld-panel');
$queueheadingurl = new moodle_url('/local/dashboard/queue.php', local_dashboard_filter_url_params($r));
$queueactionhtml = html_writer::div(
    html_writer::link(
        $queueheadingurl,
        get_string('viewallqueue', 'local_dashboard', $reviewbacklog),
        ['class' => 'ld-heading-action']
    ),
    'ld-panel-heading-action'
);
echo local_dashboard_section_heading('h4', 'priorityqueue', 'queue', [
    'subtitle' => get_string('priorityqueuesub', 'local_dashboard'),
    'action_html' => $queueactionhtml,
]);
$queuetable = new html_table();
$queuetable->head = [
    get_string('queuecandidate', 'local_dashboard'),
    get_string('queueassessment', 'local_dashboard'),
    get_string('queuealerts', 'local_dashboard'),
    get_string('queueseverity', 'local_dashboard'),
    get_string('queuestatus', 'local_dashboard'),
    get_string('queuereview', 'local_dashboard'),
];
$queuetable->attributes['class'] = 'generaltable ld-table';
$queuetable->data = [];
foreach ($priorityqueue as $row) {
    $alerts = (int) $row->alertcount;
    $severitykey = 'severitylow';
    $statuskey = 'statusclean';
    if ((int) $row->isautosubmit === 1 || $alerts >= 6) {
        $severitykey = 'severitycritical';
        $statuskey = 'statuspending';
    } else if ($alerts >= 3) {
        $severitykey = 'severityhigh';
        $statuskey = 'statuspending';
    } else if ($alerts >= 1) {
        $severitykey = 'severitymedium';
        $statuskey = 'statuspending';
    }
    $reviewlink = local_dashboard_proctor_reviewattempts_link_html((int) $row->userid, (int) $row->quizid);
    $sevpill = 'ld-pill ld-pill-sev-' . preg_replace('/^severity/', '', $severitykey);
    $statpill = 'ld-pill ld-pill-stat-' . preg_replace('/^status/', '', $statuskey);
    $queuetable->data[] = [
        fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname]),
        local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quizname),
        '<span class="ld-td-alerts">' . $alerts . '</span>',
        html_writer::span(get_string($severitykey, 'local_dashboard'), $sevpill),
        html_writer::span(get_string($statuskey, 'local_dashboard'), $statpill),
        $reviewlink,
    ];
}
if (empty($queuetable->data)) {
    $queuetable->data[] = [get_string('nofiltereddata', 'local_dashboard'), '', '', '', '', ''];
}
echo html_writer::table($queuetable);
echo html_writer::end_div();

echo html_writer::start_div('ld-panel', ['id' => 'ld-top-performers']);
$rankingurl = new moodle_url('/local/dashboard/performers.php', local_dashboard_filter_url_params($r));
$performeractionhtml = html_writer::div(
    html_writer::link($rankingurl, get_string('fullrankinglink', 'local_dashboard'), ['class' => 'ld-heading-action']),
    'ld-panel-heading-action'
);
echo local_dashboard_section_heading('h4', 'topperformers', 'performers', [
    'subtitle' => get_string('topperformersdesc', 'local_dashboard'),
    'action_html' => $performeractionhtml,
]);
$performertable = new html_table();
$performertable->head = [
    get_string('rank', 'local_dashboard'),
    get_string('candidate', 'local_dashboard'),
    get_string('score', 'local_dashboard'),
    get_string('session', 'local_dashboard'),
];
$performertable->attributes['class'] = 'generaltable ld-table';
$performertable->data = [];
$rank = 1;
foreach ($topperformers as $row) {
    $clean = ((int) $row->failedflag !== 1);
    $statuspill = $clean
        ? html_writer::span(get_string('statusclean', 'local_dashboard'), 'ld-pill ld-pill-stat-clean')
        : html_writer::span(get_string('statuspending', 'local_dashboard'), 'ld-pill ld-pill-stat-pending');
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
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('ld-panel ld-assessment-overview');
$healthparams = local_dashboard_filter_url_params($r);
$healthparams['quizid'] = 0;
$healthheadingurl = new moodle_url('/local/dashboard/assessments.php', $healthparams);
$healthactionhtml = html_writer::div(
    html_writer::link($healthheadingurl, get_string('viewallassessments', 'local_dashboard'), ['class' => 'ld-heading-action']),
    'ld-panel-heading-action'
);
echo local_dashboard_section_heading('h4', 'assessmenthealth', 'health', [
    'subtitle' => get_string('assessmenthealthdesc', 'local_dashboard'),
    'action_html' => $healthactionhtml,
]);
echo html_writer::start_div('ld-assessment-cards');
foreach ($assessmentstats as $row) {
    $cardextra = !empty($row->unusual) ? ' ld-assessment-card--unusual' : '';
    echo html_writer::start_div('ld-assessment-card' . $cardextra);
    if (!empty($row->unusual)) {
        echo html_writer::span(get_string('unusualactivity', 'local_dashboard'), 'ld-assessment-badge');
    }
    echo html_writer::div(
        local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quiznameraw),
        'ld-assessment-title'
    );
    echo html_writer::div($row->course, 'ld-assessment-sub');
    echo html_writer::start_div('ld-assessment-stats');
    echo html_writer::div(
        number_format($row->users) . '<span>' . get_string('statcandidates', 'local_dashboard') . '</span>',
        'ld-assessment-stat'
    );
    echo html_writer::div(
        format_float($row->completedpct, 1) . '%<span>' . get_string('statcompleted', 'local_dashboard') . '</span>',
        'ld-assessment-stat'
    );
    echo html_writer::div(
        number_format($row->flaggedunion) . '<span>' . get_string('statflagged', 'local_dashboard') . '</span>',
        'ld-assessment-stat ld-assessment-stat-flagged'
    );
    echo html_writer::end_div();
    echo html_writer::start_div('ld-assessment-progress');
    echo html_writer::div('', 'ld-assessment-progress-clean', ['style' => 'width:' . format_float($row->clearedpct, 2) . '%']);
    echo html_writer::div('', 'ld-assessment-progress-warn', ['style' => 'width:' . format_float($row->orangepct, 2) . '%']);
    echo html_writer::div('', 'ld-assessment-progress-risk', ['style' => 'width:' . format_float($row->highriskpct, 2) . '%']);
    echo html_writer::end_div();
    echo html_writer::start_div('ld-assessment-footer');
    echo html_writer::div(
        get_string('pctcleared', 'local_dashboard', format_float($row->clearedpct, 1)),
        'ld-assessment-foot-left'
    );
    echo html_writer::div(
        $row->orangepct > 0.5 ? format_float($row->orangepct, 1) . '%' : '',
        'ld-assessment-foot-mid'
    );
    echo html_writer::div(
        get_string('pcthighrisk', 'local_dashboard', format_float($row->highriskpct, 1)),
        'ld-assessment-foot-right'
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
}
if (empty($assessmentstats)) {
    echo html_writer::div(get_string('nofiltereddata', 'local_dashboard'));
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('ld-three-col');
echo html_writer::start_div('ld-panel');
echo local_dashboard_section_heading('h4', 'scoredistribution', 'distribution');
$distmap = [
    'distribution0_40' => (int) ($scorestats->c0_40 ?? 0),
    'distribution41_50' => (int) ($scorestats->c41_50 ?? 0),
    'distribution51_60' => (int) ($scorestats->c51_60 ?? 0),
    'distribution61_75' => (int) ($scorestats->c61_75 ?? 0),
    'distribution76_90' => (int) ($scorestats->c76_90 ?? 0),
    'distribution91_100' => (int) ($scorestats->c91_100 ?? 0),
];
$disttiers = ['tier-1', 'tier-2', 'tier-3', 'tier-4', 'tier-5', 'tier-6'];
$totalscored = max(1, (int) ($scorestats->totalcount ?? 0));
$maxdist = max($distmap ? array_values($distmap) : [1]);
$i = 0;
echo html_writer::start_div('ld-dist-chart');
foreach ($distmap as $labelkey => $count) {
    $pctlabel = format_float(($count / $totalscored) * 100.0, 1) . '%';
    $barh = $maxdist > 0 ? (($count / $maxdist) * 100.0) : 0;
    $tier = $disttiers[$i] ?? 'tier-4';
    $i++;
    echo html_writer::start_div('ld-dist-col ' . $tier);
    echo html_writer::div($pctlabel, 'ld-dist-pct');
    echo html_writer::start_div('ld-dist-bartrack');
    echo html_writer::div('', 'ld-dist-barfill', ['style' => 'height:' . format_float($barh, 2) . '%']);
    echo html_writer::end_div();
    echo html_writer::div(get_string($labelkey, 'local_dashboard'), 'ld-dist-range');
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::start_div('ld-score-stats');
echo html_writer::div(
    format_float((float) ($scorestats->avgscore ?? 0), 1) . '<span>' . get_string('avgscorelabel', 'local_dashboard') . '</span>',
    'ld-score-stat'
);
echo html_writer::div(
    format_float($passrate, 1) . '%<span>' . get_string('passratelabel', 'local_dashboard') . '</span>',
    'ld-score-stat ld-score-stat-pass'
);
echo html_writer::div(
    format_float($medianscore, 0) . '<span>' . get_string('medianlabel', 'local_dashboard') . '</span>',
    'ld-score-stat'
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('ld-panel');
echo local_dashboard_section_heading('h4', 'integrityanalytics', 'integrity');
$maxintegrity = max($statuscounts) ?: 1;
$integritydots = [
    'tabswitch' => 'ld-integrity-dot--red',
    'facemismatch' => 'ld-integrity-dot--orange',
    'absencedetected' => 'ld-integrity-dot--amber',
    'multiplepeople' => 'ld-integrity-dot--blue',
    'otheranomalies' => 'ld-integrity-dot--navy',
];
echo html_writer::start_div('ld-integrity-rows');
foreach ($statuscounts as $key => $value) {
    $dotclass = $integritydots[$key] ?? 'ld-integrity-dot--navy';
    $barw = ($value / $maxintegrity) * 100.0;
    echo html_writer::start_div('ld-integrity-row');
    echo html_writer::span('', 'ld-integrity-dot ' . $dotclass, ['aria-hidden' => 'true']);
    echo html_writer::span(get_string($key, 'local_dashboard'), 'ld-integrity-label');
    echo html_writer::start_div('ld-integrity-bartrack');
    echo html_writer::div('', 'ld-integrity-barfill', ['style' => 'width:' . format_float($barw, 2) . '%']);
    echo html_writer::end_div();
    echo html_writer::span(number_format($value), 'ld-integrity-count');
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::start_div('ld-integrity-footer');
echo html_writer::div(
    '<strong>' . number_format($totalalerts) . '</strong> <span class="ld-integrity-foot-lbl">' .
    get_string('totalalerts', 'local_dashboard') . '</span>',
    'ld-integrity-foot-block'
);
$avgalertspercandidate = $totalcandidates > 0 ? ($totalalerts / $totalcandidates) : 0;
echo html_writer::div(
    '<strong>' . format_float($avgalertspercandidate, 2) . '</strong> <span class="ld-integrity-foot-lbl">' .
    get_string('avgalertspercandidate', 'local_dashboard') . '</span>',
    'ld-integrity-foot-block'
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('ld-panel');
echo local_dashboard_section_heading('h4', 'recommendedactions', 'actions');
$risktitle = $highriskpending > 0
    ? get_string('reviewhighriskactioncount', 'local_dashboard', $highriskpending)
    : get_string('reviewhighriskaction', 'local_dashboard');
echo html_writer::start_div('ld-actions ld-actions-ref');
$actionlinkattrs = [
    'target' => '_blank',
    'rel' => 'noopener noreferrer',
];
echo html_writer::start_tag('a', array_merge([
    'href' => local_dashboard_action_view_url($r, 'highrisk')->out(false),
    'class' => 'ld-action-row ld-action-row--risk ld-action-row-link',
], $actionlinkattrs));
echo local_dashboard_action_icon_svg('shield');
echo html_writer::div(
    html_writer::span($risktitle, 'ld-action-title') .
    html_writer::span(get_string('reviewhighriskdesc', 'local_dashboard'), 'ld-action-desc'),
    'ld-action-body'
);
echo html_writer::span('›', 'ld-action-chevron', ['aria-hidden' => 'true']);
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array_merge([
    'href' => local_dashboard_action_view_url($r, 'spike')->out(false),
    'class' => 'ld-action-row ld-action-row--warn ld-action-row-link',
], $actionlinkattrs));
echo local_dashboard_action_icon_svg('alert');
echo html_writer::div(
    html_writer::span(get_string('investigateaction', 'local_dashboard'), 'ld-action-title') .
    html_writer::span(get_string('investigatedesc', 'local_dashboard'), 'ld-action-desc'),
    'ld-action-body'
);
echo html_writer::span('›', 'ld-action-chevron', ['aria-hidden' => 'true']);
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array_merge([
    'href' => local_dashboard_action_view_url($r, 'lowrisk')->out(false),
    'class' => 'ld-action-row ld-action-row--ok ld-action-row-link',
], $actionlinkattrs));
echo local_dashboard_action_icon_svg('check');
echo html_writer::div(
    html_writer::span(get_string('approveaction', 'local_dashboard'), 'ld-action-title') .
    html_writer::span(get_string('approvedesc', 'local_dashboard'), 'ld-action-desc'),
    'ld-action-body'
);
echo html_writer::span('›', 'ld-action-chevron', ['aria-hidden' => 'true']);
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array_merge([
    'href' => local_dashboard_action_view_url($r, 'scores')->out(false),
    'class' => 'ld-action-row ld-action-row--doc ld-action-row-link',
], $actionlinkattrs));
echo local_dashboard_action_icon_svg('file');
echo html_writer::div(
    html_writer::span(get_string('exportrankedreport', 'local_dashboard'), 'ld-action-title') .
    html_writer::span(get_string('exportrankedreportdesc', 'local_dashboard'), 'ld-action-desc'),
    'ld-action-body'
);
echo html_writer::span('›', 'ld-action-chevron', ['aria-hidden' => 'true']);
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array_merge([
    'href' => local_dashboard_action_view_url($r, 'activity')->out(false),
    'class' => 'ld-action-row ld-action-row--chart ld-action-row-link',
], $actionlinkattrs));
echo local_dashboard_action_icon_svg('chart');
echo html_writer::div(
    html_writer::span(get_string('downloadactivityreport', 'local_dashboard'), 'ld-action-title') .
    html_writer::span(get_string('downloadactivityreportdesc', 'local_dashboard'), 'ld-action-desc'),
    'ld-action-body'
);
echo html_writer::span('›', 'ld-action-chevron', ['aria-hidden' => 'true']);
echo html_writer::end_tag('a');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
