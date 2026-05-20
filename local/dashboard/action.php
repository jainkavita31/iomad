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
 * Recommended-action detail views (high-risk queue, spike analysis, etc.).
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

$view = optional_param('view', 'highrisk', PARAM_ALPHA);
$allowedviews = ['highrisk', 'spike', 'lowrisk', 'scores', 'activity'];
if (!in_array($view, $allowedviews, true)) {
    $view = 'highrisk';
}

$exportfmt = optional_param('export', '', PARAM_ALPHA);
if ($exportfmt === 'csv') {
    require_sesskey();
    local_dashboard_download_action_csv($view, $r);
}

if ($exportfmt === 'pdf') {
    require_sesskey();
    $pack = local_dashboard_action_export_pack($view, $r);
    if ($pack !== null) {
        local_dashboard_download_table_pdf(
            $pack['filename'],
            $pack['title'],
            $pack['subtitle'],
            $pack['headers'],
            $pack['rows']
        );
    }
}

$pageurl = new moodle_url('/local/dashboard/action.php', array_merge(
    local_dashboard_filter_url_params($r),
    ['view' => $view]
));
$PAGE->set_url($pageurl);
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('report');
$titlekey = 'actionpage_title_' . $view;
$PAGE->set_title(get_string($titlekey, 'local_dashboard'));
$PAGE->set_heading(get_string($titlekey, 'local_dashboard'));
$PAGE->requires->css(new moodle_url('/local/dashboard/styles.css'));
local_dashboard_require_datatables();

$ldpend = local_dashboard_review_log_pending_only_sql_parts();

$queuefrom = "
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
        $quizsql ";

$queueselect = "SELECT qmp.attemptid,
            u.id AS userid,
            u.firstname,
            u.lastname,
            q.id AS quizid,
            q.name AS quizname,
            SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) AS alertcount,
            MAX(qmp.isautosubmit) AS isautosubmit ";

$queuegroup = " GROUP BY qmp.attemptid, u.id, u.firstname, u.lastname, q.id, q.name ";

/**
 * @param stdClass $row
 * @return array table row cells (HTML strings)
 */
$queuerowcells = function (stdClass $row) use ($companyid): array {
    [$severitykey] = local_dashboard_queue_row_status_keys($row);
    $alerts = (int) $row->alertcount;
    $reviewlink = local_dashboard_proctor_reviewattempts_link_html(
        (int) $row->userid,
        (int) $row->quizid,
        [],
        (int) $companyid,
        (int) $row->attemptid
    );
    $sevpill = local_dashboard_queue_severity_pill_classes($severitykey);
    return [
        fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname]),
        local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quizname),
        '<span class="ld-td-alerts">' . $alerts . '</span>',
        html_writer::span(get_string($severitykey, 'local_dashboard'), $sevpill),
        $reviewlink,
    ];
};

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
echo html_writer::tag('h2', get_string($titlekey, 'local_dashboard'), ['class' => 'ld-page-title']);
$introkey = 'actionpage_intro_' . $view;
echo html_writer::div(
    $companyname . ' · ' . get_string('detailfiltercontext', 'local_dashboard', (object) [
        'timerange' => $timerangeoptions[$timerange] ?? '',
        'quiz' => $quizoptions[$quizid] ?? '',
    ]),
    'ld-page-updated'
);
echo html_writer::div(get_string($introkey, 'local_dashboard'), 'ld-action-detail-intro');
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/dashboard/action.php'), 'class' => 'ld-filters']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]);
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
echo html_writer::link(
    new moodle_url('/local/dashboard/action.php', ['view' => $view]),
    get_string('resetfilters', 'local_dashboard'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();
echo html_writer::end_tag('form');

if ($view === 'highrisk') {
    $rows = $DB->get_records_sql(
        $queueselect . $queuefrom . $queuegroup .
        " HAVING SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) > 0
            AND (MAX(qmp.isautosubmit) = 1
            OR SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) >= 6)
        ORDER BY alertcount DESC, isautosubmit DESC",
        $baseparams
    );
    echo local_dashboard_action_export_row_html($r, $view, count($rows), 'actionpage_rowcount');
    $table = new html_table();
    $table->head = [
        get_string('rank', 'local_dashboard'),
        get_string('queuecandidate', 'local_dashboard'),
        get_string('queueassessment', 'local_dashboard'),
        get_string('queuealerts', 'local_dashboard'),
        get_string('queueseverity', 'local_dashboard'),
        get_string('queuereview', 'local_dashboard'),
    ];
    $table->attributes['class'] = 'generaltable ld-table ld-detail-table';
    $table->attributes['id'] = 'ld-action-datatable';
    $table->data = [];
    $rank = 1;
    foreach ($rows as $row) {
        $table->data[] = array_merge(
            [html_writer::span((string) $rank++, 'ld-rank-box')],
            $queuerowcells($row)
        );
    }
    if (empty($table->data)) {
        $table->data[] = array_merge(
            [get_string('nofiltereddata', 'local_dashboard')],
            array_fill(0, count($table->head) - 1, '')
        );
    }
    echo html_writer::table($table);
    local_dashboard_init_datatable('#ld-action-datatable', 50, [
        'order' => [[3, 'desc']],
        'columndefs' => [
            ['orderable' => false, 'targets' => [0, 5]],
        ],
    ]);

} else if ($view === 'lowrisk') {
    $rows = $DB->get_records_sql(
        $queueselect . $queuefrom . $queuegroup .
        " HAVING SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) > 0
            AND MAX(qmp.isautosubmit) = 0
            AND SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) >= 1
            AND SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) <= 2
        ORDER BY alertcount DESC",
        $baseparams
    );
    echo local_dashboard_action_export_row_html($r, $view, count($rows), 'actionpage_rowcount');
    $table = new html_table();
    $table->head = [
        get_string('rank', 'local_dashboard'),
        get_string('queuecandidate', 'local_dashboard'),
        get_string('queueassessment', 'local_dashboard'),
        get_string('queuealerts', 'local_dashboard'),
        get_string('queueseverity', 'local_dashboard'),
        get_string('queuereview', 'local_dashboard'),
    ];
    $table->attributes['class'] = 'generaltable ld-table ld-detail-table';
    $table->attributes['id'] = 'ld-action-datatable';
    $table->data = [];
    $rank = 1;
    foreach ($rows as $row) {
        $table->data[] = array_merge(
            [html_writer::span((string) $rank++, 'ld-rank-box')],
            $queuerowcells($row)
        );
    }
    if (empty($table->data)) {
        $table->data[] = array_merge(
            [get_string('nofiltereddata', 'local_dashboard')],
            array_fill(0, count($table->head) - 1, '')
        );
    }
    echo html_writer::table($table);
    local_dashboard_init_datatable('#ld-action-datatable', 50, [
        'order' => [[3, 'desc']],
        'columndefs' => [
            ['orderable' => false, 'targets' => [0, 5]],
        ],
    ]);

} else if ($view === 'scores') {
    $rows = local_dashboard_fetch_ranked_scores_rows($quizsql, $baseparams);
    echo local_dashboard_action_export_row_html($r, $view, count($rows), 'performersrowcount');
    $table = new html_table();
    $table->head = local_dashboard_scores_table_head();
    $table->attributes['class'] = 'generaltable ld-table ld-detail-table ld-scores-detail-table';
    $table->attributes['id'] = 'ld-action-datatable';
    $table->data = [];
    $rank = 1;
    foreach ($rows as $row) {
        $table->data[] = local_dashboard_scores_table_row_cells($row, $r, $rank++);
    }
    if (empty($table->data)) {
        $table->data[] = array_merge(
            [get_string('nofiltereddata', 'local_dashboard')],
            array_fill(0, count($table->head) - 1, '')
        );
    }
    echo html_writer::table($table);
    local_dashboard_init_datatable('#ld-action-datatable', 50, [
        'order' => [[4, 'desc']],
        'columndefs' => [
            ['orderable' => false, 'targets' => [0, 1, 8, 9]],
        ],
    ]);

} else if ($view === 'activity' || $view === 'spike') {
    $stats = local_dashboard_fetch_assessment_stats($companyid, $fromtime, $quizsql, $baseparams);
    usort($stats, static function ($a, $b) {
        return ((int) $b->alerts) <=> ((int) $a->alerts);
    });
    if ($view === 'spike') {
        $stats = array_values(array_filter($stats, static function ($row) {
            return (int) $row->alerts >= 3;
        }));
    }
    echo local_dashboard_action_export_row_html($r, $view, count($stats), 'actionpage_rowcount');
    $table = new html_table();
    $table->head = [
        get_string('rank', 'local_dashboard'),
        get_string('tableassessment', 'local_dashboard'),
        get_string('tablecourse', 'local_dashboard'),
        get_string('statcandidates', 'local_dashboard'),
        get_string('tableattempts', 'local_dashboard'),
        get_string('tablealerts', 'local_dashboard'),
        get_string('statflagged', 'local_dashboard'),
    ];
    $table->head[] = get_string('proctorreport', 'local_dashboard');
    $table->attributes['class'] = 'generaltable ld-table ld-detail-table';
    $table->attributes['id'] = 'ld-action-datatable';
    $table->data = [];
    $rank = 1;
    foreach ($stats as $row) {
        // Rows use quiznameraw + quizid from fetch_assessment_stats.
        $quizcell = local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quiznameraw);
        $rowcells = [
            html_writer::span((string) $rank++, 'ld-rank-box'),
            $quizcell,
            $row->course,
            number_format($row->users),
            number_format($row->attempts),
            number_format($row->alerts),
            number_format($row->flaggedunion),
        ];
        $rowcells[] = local_dashboard_proctoring_report_link_html((int) $row->quizid);
        $table->data[] = $rowcells;
    }
    if (empty($table->data)) {
        $table->data[] = array_merge(
            [get_string('nofiltereddata', 'local_dashboard')],
            array_fill(0, count($table->head) - 1, '')
        );
    }
    echo html_writer::table($table);
    $activitydtopts = [
        'order' => [[5, 'desc']],
        'columndefs' => [
            ['orderable' => false, 'targets' => [0]],
        ],
    ];
    $activitydtopts['columndefs'][0]['targets'][] = 7;
    local_dashboard_init_datatable('#ld-action-datatable', 50, $activitydtopts);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
