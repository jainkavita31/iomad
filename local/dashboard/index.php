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
require_once(__DIR__ . '/classes/local/index_snapshot.php');

use local_dashboard\local\index_snapshot;

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

$indexfromcache = false;
$tableexists = $DB->get_manager()->table_exists('local_dashboard_index_cache');
$assessmentscompleted = 0;
$assessmentsinprogress = 0;
$newassessmenturl = null;

if ($quizid === 0 && $tableexists) {
    $cachedpack = index_snapshot::load($companyid, $timerange, 0);
    if ($cachedpack && isset($cachedpack['payload']->totalsessions)) {
        $payload = $cachedpack['payload'];
        foreach (index_snapshot::PAYLOAD_KEYS as $prop) {
            ${$prop} = property_exists($payload, $prop) ? $payload->$prop : 0;
        }
        $indexfromcache = true;
    }
}

if ($indexfromcache && $quizid === 0 && local_dashboard_review_log_table_ready()
        && local_dashboard_consume_pending_index_review_merge((int) $companyid)) {
    index_snapshot::apply_review_metrics_to_payload($payload, $r);
    foreach (index_snapshot::PAYLOAD_KEYS as $prop) {
        ${$prop} = property_exists($payload, $prop) ? $payload->$prop : 0;
    }
    index_snapshot::store($companyid, $timerange, 0, $payload);
}

if (!$indexfromcache) {
    $payload = index_snapshot::compute_data($r);
    foreach (index_snapshot::PAYLOAD_KEYS as $prop) {
        ${$prop} = property_exists($payload, $prop) ? $payload->$prop : 0;
    }
    if ($quizid === 0 && $tableexists) {
        index_snapshot::store($companyid, $timerange, 0, $payload);
    }
}

// Backward compatibility: old cached snapshots may not include alertcount in top performers rows.
$needsperformeralertrefresh = false;
if (!empty($topperformers) && is_array($topperformers)) {
    foreach ($topperformers as $prow) {
        if (!is_object($prow) || !property_exists($prow, 'alertcount')) {
            $needsperformeralertrefresh = true;
            break;
        }
    }
}
if ($needsperformeralertrefresh) {
    $topperformers = local_dashboard_fetch_ranked_scores_rows($quizsql, $baseparams, 0, 10);
    if ($quizid === 0 && $tableexists && isset($payload) && is_object($payload)) {
        $payload->topperformers = $topperformers;
        index_snapshot::store($companyid, $timerange, 0, $payload);
    }
}

// Backward compatibility: cached payloads may still use autocleared instead of zerorisk.
if (!isset($zerorisk) && isset($autocleared)) {
    $zerorisk = $autocleared;
}
if (!isset($zerorisk)) {
    $zerorisk = 0;
}

$statuscounts = local_dashboard_normalize_statuscounts($statuscounts ?? null);

// CTA: quick quiz creator when local_quickquiz is installed, else core modedit.
$newassessmenturl = local_dashboard_new_assessment_url($companyid, $quizid);

$pageurl = new moodle_url('/local/dashboard/index.php', [
    'timerange' => $timerange,
    'quizid' => $quizid,
    'companyid' => $companyid,
]);
$PAGE->set_url($pageurl);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$PAGE->set_title($companyname);
$PAGE->set_heading('');
$PAGE->requires->css(new moodle_url('/local/dashboard/styles.css'));

echo $OUTPUT->header();
echo html_writer::start_div('local-dashboard ld-main-page');

if (optional_param('dashboardsynced', 0, PARAM_INT)) {
    echo $OUTPUT->notification(get_string('indexsyncok', 'local_dashboard'), 'success');
}

$syncurl = new moodle_url('/local/dashboard/sync.php', array_merge(local_dashboard_filter_url_params($r), ['sesskey' => sesskey()]));
echo html_writer::start_div('ld-cache-sync-row');
echo html_writer::span(get_string('indexsynctopdesc', 'local_dashboard'), 'ld-cache-hint');
echo html_writer::link($syncurl, get_string('syncnow', 'local_dashboard'), ['class' => 'btn btn-secondary ld-sync-now']);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/dashboard/index.php'), 'class' => 'ld-filters']);
echo local_dashboard_filter_company_controls_html($r);
echo html_writer::start_div('ld-filter-item');
echo html_writer::tag('label', get_string('filtertimerange', 'local_dashboard'), ['for' => 'id_timerange']);
echo html_writer::select($timerangeoptions, 'timerange', $timerange, false, [
    'id' => 'id_timerange',
    'aria-label' => get_string('filtertimerange', 'local_dashboard'),
]);
echo html_writer::end_div();
echo html_writer::start_div('ld-filter-item');
echo html_writer::tag('label', get_string('filterassessment', 'local_dashboard'), ['for' => 'id_quizid']);
echo html_writer::select($quizoptions, 'quizid', $quizid, false, [
    'id' => 'id_quizid',
    'aria-label' => get_string('filterassessment', 'local_dashboard'),
]);
echo html_writer::end_div();
echo html_writer::start_div('ld-filter-actions');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('applyfilters', 'local_dashboard'), 'class' => 'btn btn-primary']);
echo html_writer::link(new moodle_url('/local/dashboard/index.php'), get_string('resetfilters', 'local_dashboard'), ['class' => 'btn btn-secondary']);
if ($newassessmenturl) {
    echo html_writer::link(
        $newassessmenturl,
        get_string('newassessment', 'local_dashboard'),
        ['class' => 'btn btn-primary ld-new-assessment-btn']
    );
}
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
        'sub' => get_string('kpi_assessmentssplit', 'local_dashboard', (object) [
            'completed' => number_format((int) $assessmentscompleted),
            'inprogress' => number_format((int) $assessmentsinprogress),
        ]),
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
    ['zerorisk', $zerorisk, 'ld-pipeline-card--cleared'],
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
        get_string('viewallqueue', 'local_dashboard', count($priorityqueue)),
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
    get_string('queuereview', 'local_dashboard'),
];
$queuetable->attributes['class'] = 'generaltable ld-table';
$queuetable->data = [];
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
        fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname]),
        local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quizname),
        '<span class="ld-td-alerts">' . $alerts . '</span>',
        html_writer::span(get_string($severitykey, 'local_dashboard'), $sevpill),
        $reviewlink,
    ];
}
if (empty($queuetable->data)) {
    $queuetable->data[] = [get_string('nofiltereddata', 'local_dashboard'), '', '', '', ''];
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
$assessmenthealthwidget = local_dashboard_assessment_health_widget_cards(
    is_array($assessmentstats) ? $assessmentstats : []
);
$assessmenthealthcards = $assessmenthealthwidget['cards'];
echo html_writer::start_div('ld-assessment-cards');
foreach ($assessmenthealthcards as $row) {
    echo html_writer::start_div('ld-assessment-card');
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
    $warnattrs = [
        'style' => 'width:' . format_float($row->orangepct, 2) . '%',
        'title' => get_string('progresslowmedium', 'local_dashboard'),
        'aria-label' => get_string('progresslowmedium', 'local_dashboard'),
    ];
    echo html_writer::div('', 'ld-assessment-progress-warn', $warnattrs);
    echo html_writer::div('', 'ld-assessment-progress-risk', ['style' => 'width:' . format_float($row->highriskpct, 2) . '%']);
    echo html_writer::end_div();
    echo html_writer::start_div('ld-assessment-footer');
    echo html_writer::div(
        get_string('pctcleared', 'local_dashboard', format_float($row->clearedpct, 1)),
        'ld-assessment-foot-left'
    );
    echo html_writer::div(
        $row->orangepct > 0.5
            ? get_string('pctlowmedium', 'local_dashboard', format_float($row->orangepct, 1))
            : '',
        'ld-assessment-foot-mid'
    );
    echo html_writer::div(
        get_string('pcthighrisk', 'local_dashboard', format_float($row->highriskpct, 1)),
        'ld-assessment-foot-right'
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
}
if (empty($assessmenthealthcards)) {
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
    'nofacedetected' => 'ld-integrity-dot--amber',
    'eyesnotfocused' => 'ld-integrity-dot--gold',
    'multiplepeople' => 'ld-integrity-dot--blue',
    'objectsdetected' => 'ld-integrity-dot--violet',
    'otheralerts' => 'ld-integrity-dot--navy',
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
