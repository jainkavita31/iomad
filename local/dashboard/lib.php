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
 * Navigation hooks for local_dashboard.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * SVG root attributes for reference-style stroke icons.
 *
 * @return string
 */
function local_dashboard_icon_svg_attrs(): string {
    return ' xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';
}

/**
 * Small blue stroke icon (matches exam dashboard reference).
 *
 * @param string $variant pipeline|queue|performers|health|distribution|integrity|actions
 * @return string HTML (trusted static SVG).
 */
function local_dashboard_section_icon_svg(string $variant): string {
    $a = local_dashboard_icon_svg_attrs();
    switch ($variant) {
        case 'pipeline':
            $path = '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>';
            break;
        case 'queue':
            $path = '<path d="M12 2l9.5 5.5v11L12 24l-9.5-5.5v-11L12 2z"/>';
            break;
        case 'performers':
            $path = '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>';
            break;
        case 'health':
            $path = '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>';
            break;
        case 'distribution':
            $path = '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>';
            break;
        case 'integrity':
            $path = '<path d="M12 2l9.5 5.5v11L12 24l-9.5-5.5v-11L12 2z"/>';
            break;
        case 'actions':
            $path = '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>';
            break;
        default:
            $path = '<circle cx="12" cy="12" r="3"/>';
            break;
    }
    return '<span class="ld-section-icon-svg" aria-hidden="true"><svg class="ld-ref-icon"' . $a . '>' . $path . '</svg></span>';
}

/**
 * Small filled icon for recommended action rows (reference cards).
 *
 * @param string $kind shield|alert|check|file|chart
 * @return string HTML
 */
function local_dashboard_action_icon_svg(string $kind): string {
    $kind = clean_param($kind, PARAM_ALPHA);
    switch ($kind) {
        case 'shield':
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
            break;
        case 'alert':
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            break;
        case 'check':
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
            break;
        case 'file':
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
            break;
        case 'chart':
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>';
            break;
        default:
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>';
            break;
    }
    return '<div class="ld-action-icon-bg ld-action-icon-bg--' . $kind . '" aria-hidden="true">' . $svg . '</div>';
}

/**
 * Section / panel heading: small blue icon + title + optional subtitle + optional right action (reference layout).
 *
 * @param string $tag h2–h4
 * @param string $langkey Title language string key.
 * @param string $variant Icon variant (see local_dashboard_section_icon_svg).
 * @param array $options Optional keys: subtitle (string), action_html (string raw HTML).
 * @return string HTML fragment.
 */
function local_dashboard_section_heading(string $tag, string $langkey, string $variant, array $options = []): string {
    $icon = local_dashboard_section_icon_svg($variant);
    $title = html_writer::tag($tag, get_string($langkey, 'local_dashboard'), ['class' => 'ld-section-title-text']);
    $sub = '';
    if (!empty($options['subtitle'])) {
        $sub = html_writer::div($options['subtitle'], 'ld-panel-heading-sub');
    }
    // Raw SVG concatenation (icons are static; subtitles are plain text from get_string).
    $titles = '<div class="ld-panel-heading-titles">' . $title . $sub . '</div>';
    $main = '<div class="ld-panel-heading-main">' . $icon . $titles . '</div>';
    $action = $options['action_html'] ?? '';
    $rowclass = 'ld-panel-heading-row' . ($action === '' ? ' ld-panel-heading-row--noaction' : '');
    $row = '<div class="' . $rowclass . '">' . $main . $action . '</div>';
    return '<div class="ld-section-head ld-section-head-ref ld-section-head-' . s($variant) . '">' . $row . '</div>';
}

/**
 * ProctorLink per-user attempt images report (reviewattempts.php).
 *
 * @param int $userid User id.
 * @param int $cmid Course module id of the quiz activity.
 * @param int $quizid Quiz instance id.
 * @return moodle_url
 */
function local_dashboard_proctor_reviewattempts_url(int $userid, int $cmid, int $quizid): moodle_url {
    return new moodle_url('/mod/quiz/accessrule/quizproctoring/reviewattempts.php', [
        'userid' => $userid,
        'cmid' => $cmid,
        'quizid' => $quizid,
    ]);
}

/**
 * “Review →” link to ProctorLink reviewattempts.php, or plain label if CM missing / no access.
 *
 * Link is shown if the user can open the ProctorLink overall report on the quiz, **or** if they can
 * view the exam dashboard for `$dashboardcompanyid` and that company is linked to the quiz course.
 *
 * @param int $userid User id (candidate).
 * @param int $quizid Quiz instance id.
 * @param array $linkattrs Optional extra anchor attributes.
 * @param int|null $dashboardcompanyid Company id from dashboard filters (non-admin); optional.
 * @return string HTML
 */
function local_dashboard_proctor_reviewattempts_link_html(int $userid, int $quizid, array $linkattrs = [], ?int $dashboardcompanyid = null): string {
    global $DB;

    $label = get_string('reviewarrow', 'local_dashboard');
    if ($userid < 1 || $quizid < 1) {
        return $label;
    }
    $quiz = $DB->get_record('quiz', ['id' => $quizid], 'id,course', IGNORE_MISSING);
    if (!$quiz) {
        return $label;
    }
    $cm = get_coursemodule_from_instance('quiz', $quizid, (int) $quiz->course, false, IGNORE_MISSING);
    if (!$cm) {
        return $label;
    }
    $modctx = context_module::instance($cm->id);
    $allowlink = has_capability('quizaccess/quizproctoring:quizproctoringoverallreport', $modctx);
    if (!$allowlink && $dashboardcompanyid !== null && $dashboardcompanyid > 0
            && $DB->record_exists('company_course', [
                'companyid' => $dashboardcompanyid,
                'courseid' => (int) $quiz->course,
            ])) {
        try {
            $companyctx = \core\context\company::instance($dashboardcompanyid);
            $allowlink = has_capability('local/dashboard:view', $companyctx);
        } catch (\Exception $e) {
            $allowlink = false;
        }
    }
    if (!$allowlink) {
        return $label;
    }
    $url = new moodle_url('/local/dashboard/proctor_review_entry.php', [
        'userid' => $userid,
        'quizid' => $quizid,
    ]);
    $attrs = array_merge(['class' => 'ld-review-link'], $linkattrs);
    return html_writer::link($url, $label, $attrs);
}

/**
 * Link to mod/quiz/view.php for this quiz, or formatted name only if no CM / no access.
 *
 * @param int $quizid Quiz instance id.
 * @param string $quizname Raw quiz name from DB.
 * @param array $linkattrs Optional extra attributes for the anchor.
 * @return string HTML
 */
function local_dashboard_quizview_link_html(int $quizid, string $quizname, array $linkattrs = []): string {
    global $DB;

    $showname = format_string($quizname);
    if ($quizid < 1) {
        return $showname;
    }
    $quiz = $DB->get_record('quiz', ['id' => $quizid], 'id,course', IGNORE_MISSING);
    if (!$quiz) {
        return $showname;
    }
    $cm = get_coursemodule_from_instance('quiz', $quizid, (int) $quiz->course, false, IGNORE_MISSING);
    if (!$cm) {
        return $showname;
    }
    $modctx = context_module::instance($cm->id);
    if (!has_capability('mod/quiz:view', $modctx)) {
        return $showname;
    }
    $url = new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
    $attrs = array_merge(['class' => 'ld-quiz-name-link'], $linkattrs);
    return html_writer::link($url, $showname, $attrs);
}

/**
 * Shared setup for dashboard and detail report pages (company, capability, filters).
 * Caller must have called require_login() first.
 *
 * @return stdClass|null Context object fields; null if the script already printed a full-page response and should exit.
 */
function local_dashboard_bootstrap_report(): ?stdClass {
    global $DB, $USER, $SESSION, $OUTPUT, $PAGE;

    $systemcontext = context_system::instance();
    $requestedcompanyid = optional_param('companyid', 0, PARAM_INT);
    $canviewallcompanies = has_capability('moodle/site:config', $systemcontext);

    if ($canviewallcompanies) {
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

    // Only organisations where this user may view the exam dashboard.
    $companyoptionswithview = [];
    foreach ($companyoptions as $cid => $cname) {
        $cid = (int) $cid;
        try {
            $cctx = \core\context\company::instance($cid);
        } catch (\Exception $e) {
            continue;
        }
        if (has_capability('local/dashboard:view', $cctx)) {
            $companyoptionswithview[$cid] = $cname;
        }
    }
    $companyoptions = $companyoptionswithview;

    $companyid = 0;
    if ($requestedcompanyid > 0 && array_key_exists($requestedcompanyid, $companyoptions)) {
        $companyid = $requestedcompanyid;
    } else if (!empty($SESSION->currenteditingcompany) && array_key_exists((int) $SESSION->currenteditingcompany, $companyoptions)) {
        $companyid = (int) $SESSION->currenteditingcompany;
    } else if (!empty($companyoptions)) {
        $companyid = (int) array_key_first($companyoptions);
    }

    if (!$companyid) {
        $PAGE->set_url('/local/dashboard/index.php');
        $PAGE->set_context($systemcontext);
        $PAGE->set_pagelayout('report');
        $PAGE->set_title(get_string('heading', 'local_dashboard'));
        $PAGE->set_heading(get_string('heading', 'local_dashboard'));
        echo $OUTPUT->header();
        $msg = empty($companyoptions)
            ? get_string('nodashboardaccess', 'local_dashboard')
            : get_string('nocompanyavailable', 'local_dashboard');
        echo $OUTPUT->notification($msg, 'warning');
        echo $OUTPUT->footer();
        return null;
    }

    $companycontext = \core\context\company::instance($companyid);
    require_capability('local/dashboard:view', $companycontext);

    $manager = $DB->get_manager();
    if (!$manager->table_exists('quizaccess_quizproctoring') ||
        !$manager->table_exists('quizaccess_main_proctor') ||
        !$manager->table_exists('quizaccess_proctor_data')) {
        $PAGE->set_url('/local/dashboard/index.php');
        $PAGE->set_context($companycontext);
        $PAGE->set_pagelayout('report');
        $PAGE->set_title(get_string('heading', 'local_dashboard'));
        $PAGE->set_heading(get_string('heading', 'local_dashboard'));
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('proctorpluginmissing', 'local_dashboard'), 'warning');
        echo $OUTPUT->footer();
        return null;
    }

    $timerange = optional_param('timerange', 30, PARAM_INT);
    $quizid = optional_param('quizid', 0, PARAM_INT);

    $timerangeoptions = [
        7 => get_string('last7days', 'local_dashboard'),
        30 => get_string('last30days', 'local_dashboard'),
        90 => get_string('last90days', 'local_dashboard'),
        365 => get_string('last365days', 'local_dashboard'),
    ];
    if (!isset($timerangeoptions[$timerange])) {
        $timerange = 30;
    }

    $fromtime = time() - ($timerange * DAYSECS);

    $quizoptions = [0 => get_string('allassessments', 'local_dashboard')];
    $quizzes = $DB->get_records_sql(
        "SELECT q.id, c.fullname AS coursefullname, q.name
           FROM {company_course} cc
           JOIN {course} c ON c.id = cc.courseid
           JOIN {quiz} q ON q.course = c.id
           JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
          WHERE cc.companyid = :companyid
            AND qp.enableproctoring = 1
       ORDER BY c.fullname, q.name",
        ['companyid' => $companyid]
    );
    foreach ($quizzes as $q) {
        $quizoptions[$q->id] = format_string($q->coursefullname . ' / ' . $q->name);
    }
    if (!array_key_exists($quizid, $quizoptions)) {
        $quizid = 0;
    }

    $baseparams = [
        'companyid' => $companyid,
        'fromtime' => $fromtime,
    ];
    $quizsql = '';
    if (!empty($quizid)) {
        $quizsql = ' AND q.id = :quizid ';
        $baseparams['quizid'] = $quizid;
    }

    $companyname = (string) $DB->get_field('company', 'name', ['id' => $companyid], IGNORE_MISSING);
    $companyname = format_string($companyname ?: ('ID ' . $companyid));

    $out = new stdClass();
    $out->companyid = $companyid;
    $out->companycontext = $companycontext;
    $out->companyoptions = $companyoptions;
    $out->companyname = $companyname;
    $out->show_company_selector = $canviewallcompanies;
    $out->timerange = $timerange;
    $out->quizid = $quizid;
    $out->timerangeoptions = $timerangeoptions;
    $out->quizoptions = $quizoptions;
    $out->fromtime = $fromtime;
    $out->baseparams = $baseparams;
    $out->quizsql = $quizsql;
    return $out;
}

/**
 * Query params for dashboard filter links (company, time range, assessment).
 *
 * @param stdClass $r Bootstrap object from local_dashboard_bootstrap_report().
 * @return array
 */
function local_dashboard_filter_url_params(stdClass $r): array {
    return [
        'companyid' => $r->companyid,
        'timerange' => $r->timerange,
        'quizid' => $r->quizid,
    ];
}

/**
 * First filter cell: site admins get a company dropdown; everyone else sees the organisation name only (no "Company" label).
 *
 * @param stdClass $r Bootstrap object from local_dashboard_bootstrap_report() (expects show_company_selector, companyoptions, companyid, companyname).
 * @return string HTML fragment.
 */
function local_dashboard_filter_company_controls_html(stdClass $r): string {
    if (!empty($r->show_company_selector)) {
        $label = get_string('selectcompany', 'local_dashboard');
        $o = html_writer::start_div('ld-filter-item');
        $o .= html_writer::tag('label', $label, ['for' => 'id_companyid']);
        $o .= html_writer::select($r->companyoptions, 'companyid', $r->companyid, false, [
            'id' => 'id_companyid',
            'aria-label' => $label,
        ]);
        $o .= html_writer::end_div();
        return $o;
    }
    $o = html_writer::start_div('ld-filter-item ld-filter-orgname');
    $o .= html_writer::div($r->companyname, 'ld-org-name-display');
    $o .= html_writer::div(get_string('lastupdated', 'local_dashboard', userdate(time())), 'ld-org-updated-display');
    $o .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'companyid', 'value' => (int) $r->companyid]);
    $o .= html_writer::end_div();
    return $o;
}

/**
 * Whether the proctor review log table is present (after install/upgrade).
 *
 * @return bool
 */
function local_dashboard_review_log_table_ready(): bool {
    global $DB;
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    return $ready = $DB->get_manager()->table_exists('local_dashboard_proctor_review_log');
}

/**
 * SQL fragments to attach latest review time per candidate+quiz (for queue status).
 *
 * @return array{join: string, select: string}
 */
function local_dashboard_review_log_sql_parts(): array {
    if (!local_dashboard_review_log_table_ready()) {
        return [
            'join' => '',
            'select' => ', NULL AS reviewedat',
        ];
    }
    return [
        'join' => " LEFT JOIN (
                        SELECT candidate_userid, quizid, MAX(timecreated) AS reviewedat
                          FROM {local_dashboard_proctor_review_log}
                      GROUP BY candidate_userid, quizid
                   ) ld_rev ON ld_rev.candidate_userid = u.id AND ld_rev.quizid = q.id ",
        'select' => ', ld_rev.reviewedat',
    ];
}

/**
 * JOIN + WHERE fragment to limit queries to candidate+quiz pairs not yet in the review log.
 * Matches queue "Reviewed" semantics (log keyed by candidate_userid + quizid).
 *
 * Queries must alias attempts as qa and quiz as q, and use qa.userid as the candidate id.
 *
 * @return array{join: string, where: string}
 */
function local_dashboard_review_log_pending_only_sql_parts(): array {
    if (!local_dashboard_review_log_table_ready()) {
        return ['join' => '', 'where' => ''];
    }
    return [
        'join' => " LEFT JOIN (
                        SELECT candidate_userid AS ruid, quizid AS rqid, MAX(timecreated) AS reviewedat
                          FROM {local_dashboard_proctor_review_log}
                      GROUP BY candidate_userid, quizid
                   ) ld_pend ON ld_pend.ruid = qa.userid AND ld_pend.rqid = q.id ",
        'where' => ' AND ld_pend.reviewedat IS NULL ',
    ];
}

/**
 * Log that the current user opened the ProctorLink review page for a candidate+quiz.
 *
 * @param int $candidateuserid Student user id.
 * @param int $cmid Quiz course-module id.
 * @param int $quizid Quiz instance id.
 * @return void
 */
function local_dashboard_note_proctor_review_access(int $candidateuserid, int $cmid, int $quizid): void {
    global $DB, $USER;

    if (!local_dashboard_review_log_table_ready() || isguestuser() || empty($USER->id)) {
        return;
    }
    $record = (object) [
        'reviewer_userid' => (int) $USER->id,
        'candidate_userid' => $candidateuserid,
        'quizid' => $quizid,
        'cmid' => $cmid,
        'timecreated' => time(),
    ];
    $DB->insert_record('local_dashboard_proctor_review_log', $record);
}

/**
 * Severity + status language keys for a priority-queue row (status uses review log when applicable).
 *
 * Row must include: alertcount, isautosubmit; optional reviewedat (unix int or null).
 *
 * @param stdClass $row
 * @return array{0: string, 1: string} [severitykey, statuskey] full keys e.g. severityhigh, statusreviewed
 */
function local_dashboard_queue_row_status_keys(stdClass $row): array {
    $alerts = (int) $row->alertcount;
    $isautosubmit = (int) $row->isautosubmit;
    $reviewed = !empty($row->reviewedat);

    $severitykey = 'severitylow';
    if ($isautosubmit === 1 || $alerts >= 6) {
        $severitykey = 'severitycritical';
    } else if ($alerts >= 3) {
        $severitykey = 'severityhigh';
    } else if ($alerts >= 1) {
        $severitykey = 'severitymedium';
    }

    $needsreview = ($isautosubmit === 1 || $alerts >= 1);
    if (!$needsreview) {
        return [$severitykey, 'statusclean'];
    }
    if ($reviewed) {
        return [$severitykey, 'statusreviewed'];
    }
    return [$severitykey, 'statuspending'];
}

/**
 * URL for a recommended-action detail page.
 *
 * @param stdClass $r Bootstrap object from local_dashboard_bootstrap_report().
 * @param string $view One of: highrisk, spike, lowrisk, scores, activity.
 * @return moodle_url
 */
function local_dashboard_action_view_url(stdClass $r, string $view): moodle_url {
    $allowed = ['highrisk', 'spike', 'lowrisk', 'scores', 'activity'];
    if (!in_array($view, $allowed, true)) {
        $view = 'highrisk';
    }
    return new moodle_url('/local/dashboard/action.php', array_merge(
        local_dashboard_filter_url_params($r),
        ['view' => $view]
    ));
}

/**
 * Ranked best score per user per quiz (performers and action scores view).
 *
 * @param string $quizsql SQL fragment with optional quiz filter.
 * @param array $baseparams Params including companyid, fromtime, optional quizid.
 * @param int $limitfrom First row offset (used when $limitnum > 0).
 * @param int $limitnum Max rows; 0 = no limit.
 * @return stdClass[] List rows (0-based keys): userid, firstname, lastname, quizid, quizname, coursename, bestscore, failedflag, alertcount.
 */
function local_dashboard_fetch_ranked_scores_rows(
    string $quizsql,
    array $baseparams,
    int $limitfrom = 0,
    int $limitnum = 0
): array {
    global $DB;

    $sql =
        "SELECT qa.userid,
                u.firstname,
                u.lastname,
                q.id AS quizid,
                q.name AS quizname,
                c.fullname AS coursename,
                MAX((qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0)) AS bestscore,
                MAX(CASE WHEN qmp.isautosubmit = 1 THEN 1 ELSE 0 END) AS failedflag,
                COUNT(DISTINCT CASE WHEN pd.deleted = 0 AND pd.status != '' THEN pd.id ELSE NULL END) AS alertcount
           FROM {quiz_attempts} qa
           JOIN {user} u ON u.id = qa.userid
           JOIN {quiz} q ON q.id = qa.quiz
           JOIN {course} c ON c.id = q.course
           JOIN {company_course} cc ON cc.courseid = q.course
           JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
           LEFT JOIN {quizaccess_main_proctor} qmp ON qmp.attemptid = qa.id
                                               AND qmp.deleted = 0
                                               AND qmp.image_status = 'M'
           LEFT JOIN {quizaccess_proctor_data} pd ON pd.attemptid = qa.id
                                                 AND pd.quizid = q.id
          WHERE cc.companyid = :companyid
            AND qp.enableproctoring = 1
            AND qa.preview = 0
            AND qa.timefinish > 0
            AND qa.timestart >= :fromtime
            $quizsql
       GROUP BY qa.userid, u.firstname, u.lastname, q.id, q.name, c.fullname
       ORDER BY bestscore DESC";

    // Do not use get_records_sql(): its first column becomes the array key; userid repeats across
    // quizzes when quizid=0, so rows overwrite and most rankings disappear.
    $rs = $DB->get_recordset_sql($sql, $baseparams, $limitfrom, $limitnum);
    $rows = [];
    foreach ($rs as $row) {
        $rows[] = $row;
    }
    $rs->close();

    return $rows;
}

/**
 * Per-assessment health stats for the company (same logic as the main dashboard).
 *
 * @param int $companyid Company id.
 * @param int $fromtime Unix timestamp lower bound for attempts.
 * @param string $quizsql Fragment e.g. " AND q.id = :quizid " or empty.
 * @param array $baseparams Params including companyid, fromtime, optional quizid.
 * @return stdClass[] List of row objects for cards/tables.
 */
function local_dashboard_fetch_assessment_stats(
    int $companyid,
    int $fromtime,
    string $quizsql,
    array $baseparams
): array {
    global $DB;

    $assessmentrows = $DB->get_records_sql(
        "SELECT q.id AS quizid, q.name AS quizname, c.fullname AS coursename
           FROM {company_course} cc
           JOIN {course} c ON c.id = cc.courseid
           JOIN {quiz} q ON q.course = c.id
           JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
          WHERE cc.companyid = :companyid
            AND qp.enableproctoring = 1
            $quizsql
       ORDER BY c.fullname, q.name",
        array_diff_key($baseparams, ['fromtime' => true])
    );

    $assessmentstats = [];
    foreach ($assessmentrows as $assessmentrow) {
        $params = [
            'quizid' => $assessmentrow->quizid,
            'fromtime' => $fromtime,
        ];

        $users = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.userid)
               FROM {quiz_attempts} qa
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime",
            $params
        );
        $attempts = (int) $DB->count_records_sql(
            "SELECT COUNT(qa.id)
               FROM {quiz_attempts} qa
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime",
            $params
        );
        $alerts = (int) $DB->count_records_sql(
            "SELECT COUNT(pd.id)
               FROM {quizaccess_proctor_data} pd
               JOIN {quiz_attempts} qa ON qa.id = pd.attemptid
              WHERE pd.quizid = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                AND pd.deleted = 0
                AND pd.status != ''",
            $params
        );
        $failed = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qmp.attemptid)
               FROM {quizaccess_main_proctor} qmp
               JOIN {quiz_attempts} qa ON qa.id = qmp.attemptid
              WHERE qmp.quizid = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                AND qmp.deleted = 0
                AND qmp.image_status = 'M'
                AND qmp.isautosubmit = 1",
            $params
        );
        $finished = (int) $DB->count_records_sql(
            "SELECT COUNT(qa.id)
               FROM {quiz_attempts} qa
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timefinish > 0
                AND qa.timestart >= :fromtime",
            $params
        );
        $warnedattempts = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.id)
               FROM {quiz_attempts} qa
               JOIN {quizaccess_proctor_data} pd ON pd.attemptid = qa.id AND pd.quizid = qa.quiz
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                AND pd.deleted = 0
                AND pd.status != ''",
            $params
        );
        $flaggedunion = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT aid) FROM (
                    SELECT qa.id AS aid
                      FROM {quiz_attempts} qa
                      JOIN {quizaccess_main_proctor} qmp ON qmp.attemptid = qa.id AND qmp.quizid = qa.quiz
                     WHERE qa.quiz = :quizid_u1
                       AND qa.preview = 0
                       AND qa.timestart >= :fromtime_u1
                       AND qmp.deleted = 0
                       AND qmp.image_status = 'M'
                       AND qmp.isautosubmit = 1
                     UNION
                    SELECT DISTINCT qa.id AS aid
                      FROM {quiz_attempts} qa
                      JOIN {quizaccess_proctor_data} pd ON pd.attemptid = qa.id AND pd.quizid = qa.quiz
                     WHERE qa.quiz = :quizid_u2
                       AND qa.preview = 0
                       AND qa.timestart >= :fromtime_u2
                       AND pd.deleted = 0
                       AND pd.status != ''
             ) u",
            [
                'quizid_u1' => $assessmentrow->quizid,
                'fromtime_u1' => $fromtime,
                'quizid_u2' => $assessmentrow->quizid,
                'fromtime_u2' => $fromtime,
            ]
        );
        $scoreobj = $DB->get_record_sql(
            "SELECT AVG((qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0)) AS avgscore
               FROM {quiz_attempts} qa
               JOIN {quiz} q ON q.id = qa.quiz
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timefinish > 0
                AND qa.timestart >= :fromtime",
            $params
        );

        $at = max(1, $attempts);
        $highriskpct = min(100.0, ($failed / $at) * 100.0);
        $warnrate = min(100.0, ($warnedattempts / $at) * 100.0);
        $orangepct = max(0.0, min(100.0 - $highriskpct, $warnrate - $highriskpct * 0.35));
        if ($orangepct < 0.5 && $warnedattempts > 0 && $failed === 0) {
            $orangepct = min(18.0, ($warnedattempts / $at) * 100.0);
        }
        $orangepct = min($orangepct, max(0.0, 100.0 - $highriskpct));
        $clearedpct = max(0.0, 100.0 - $highriskpct - $orangepct);
        $completedpct = $at > 0 ? (($finished / $at) * 100.0) : 0.0;
        $unusual = ($flaggedunion > 40 || $highriskpct > 12.0);

        $assessmentstats[] = (object) [
            'course' => format_string($assessmentrow->coursename),
            'quizid' => (int) $assessmentrow->quizid,
            'quiznameraw' => (string) $assessmentrow->quizname,
            'users' => $users,
            'attempts' => $attempts,
            'alerts' => $alerts,
            'failed' => $failed,
            'finished' => $finished,
            'flaggedunion' => $flaggedunion,
            'completedpct' => $completedpct,
            'clearedpct' => $clearedpct,
            'orangepct' => $orangepct,
            'highriskpct' => $highriskpct,
            'unusual' => $unusual,
            'score' => !empty($scoreobj->avgscore) ? (float) $scoreobj->avgscore : 0.0,
        ];
    }

    return $assessmentstats;
}

/**
 * First company id where the current user has {@see local_dashboard:view} (prefers session company when valid).
 *
 * @return int 0 if none.
 */
function local_dashboard_first_company_with_dashboard_view(): int {
    global $DB, $SESSION, $USER;

    $systemcontext = context_system::instance();
    $canall = has_capability('moodle/site:config', $systemcontext);
    if ($canall) {
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
        ) ?: [];
    }

    $candidates = [];
    foreach ($companyoptions as $cid => $ignored) {
        $cid = (int) $cid;
        try {
            $ctx = \core\context\company::instance($cid);
        } catch (\Exception $e) {
            continue;
        }
        if (has_capability('local/dashboard:view', $ctx)) {
            $candidates[] = $cid;
        }
    }
    if ($candidates === []) {
        return 0;
    }
    if (!empty($SESSION->currenteditingcompany)) {
        $sess = (int) $SESSION->currenteditingcompany;
        if (in_array($sess, $candidates, true)) {
            return $sess;
        }
    }
    return $candidates[0];
}

/**
 * Extend global navigation with Dashboard link when user can access it.
 *
 * @param global_navigation $nav
 * @return void
 */
function local_dashboard_extend_navigation(global_navigation $nav): void {
    global $DB, $SESSION, $USER, $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $companyid = local_dashboard_first_company_with_dashboard_view();
    if (!$companyid) {
        return;
    }

    $url = new moodle_url('/local/dashboard/index.php');
    $label = get_string('pluginname', 'local_dashboard');

    $homenode = $nav->find('home', null);
    if (!$homenode) {
        $homenode = $PAGE->navigation->add(
            get_string('home'),
            new moodle_url('/index.php'),
            navigation_node::TYPE_ROOTNODE
        );
        $homenode->force_open();
    }

    $existing = $homenode->find('local_dashboard', navigation_node::TYPE_CUSTOM);
    if (!$existing) {
        $homenode->add($label, $url, navigation_node::TYPE_CUSTOM, null, 'local_dashboard');
    }
}

/**
 * Extend settings navigation with Dashboard shortcut.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 * @return void
 */
function local_dashboard_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    global $DB, $SESSION, $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $companyid = local_dashboard_first_company_with_dashboard_view();
    if (!$companyid) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_dashboard'),
        new moodle_url('/local/dashboard/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_dashboard_settings'
    );
    $settingsnav->add_node($node);
}
