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
 * Normalize integrity status bucket counts for the index UI (handles legacy cached payloads).
 *
 * @param mixed $raw From index snapshot payload (array, stdClass, or missing).
 * @return array<string,int> Keys in display order.
 */
function local_dashboard_normalize_statuscounts($raw): array {
    $keys = [
        'tabswitch',
        'facemismatch',
        'nofacedetected',
        'eyesnotfocused',
        'multiplepeople',
        'objectsdetected',
        'otheralerts',
    ];
    $out = array_fill_keys($keys, 0);
    if ($raw === null || $raw === false) {
        return $out;
    }
    if (is_object($raw)) {
        $raw = (array) $raw;
    }
    if (!is_array($raw)) {
        return $out;
    }
    if (array_key_exists('otheralerts', $raw)) {
        foreach ($keys as $k) {
            $out[$k] = (int) ($raw[$k] ?? 0);
        }
        return $out;
    }
    if (array_key_exists('otheranomalies', $raw) || array_key_exists('absencedetected', $raw)) {
        $out['tabswitch'] = (int) ($raw['tabswitch'] ?? 0);
        $out['facemismatch'] = (int) ($raw['facemismatch'] ?? 0);
        $out['multiplepeople'] = (int) ($raw['multiplepeople'] ?? 0);
        $out['otheralerts'] = (int) ($raw['otheranomalies'] ?? 0) + (int) ($raw['absencedetected'] ?? 0);
        return $out;
    }
    foreach ($keys as $k) {
        $out[$k] = (int) ($raw[$k] ?? 0);
    }
    return $out;
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
function local_dashboard_proctor_reviewattempts_link_html(
    int $userid,
    int $quizid,
    array $linkattrs = [],
    ?int $dashboardcompanyid = null,
    int $attemptid = 0
): string {
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
    global $USER;
    $allowlink = has_capability('quizaccess/quizproctoring:quizproctoringoverallreport', $modctx)
        || local_dashboard_user_is_site_exam_admin();
    if (!$allowlink && $dashboardcompanyid !== null && $dashboardcompanyid > 0
            && $DB->record_exists('company_course', [
                'companyid' => $dashboardcompanyid,
                'courseid' => (int) $quiz->course,
            ])) {
        try {
            $companyctx = \core\context\company::instance($dashboardcompanyid);
            $allowlink = has_capability('local/dashboard:view', $companyctx, $USER->id, false);
        } catch (\Exception $e) {
            $allowlink = false;
        }
    }
    if (!$allowlink) {
        return $label;
    }
    $urlparams = [
        'userid' => $userid,
        'quizid' => $quizid,
    ];
    if ($attemptid > 0) {
        $urlparams['attemptid'] = $attemptid;
    }
    $url = new moodle_url('/local/dashboard/proctor_review_entry.php', $urlparams);
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
 * Site-wide exam dashboard administrators (all companies).
 *
 * Moodle site admins, system-level site configurators, and IOMAD multi-company admins
 * ({@see block/iomad_company_admin:company_add} at system). All checks use doanything=false.
 *
 * @return bool
 */
function local_dashboard_user_is_site_exam_admin(): bool {
    global $USER;

    if (is_siteadmin($USER)) {
        return true;
    }

    $systemcontext = context_system::instance();
    if (has_capability('moodle/site:config', $systemcontext, $USER->id, false)) {
        return true;
    }

    return has_capability('block/iomad_company_admin:company_add', $systemcontext, $USER->id, false);
}

/**
 * Companies the current user may open on the exam dashboard.
 *
 * Site admins: all companies. Others: only where {@see local/dashboard:view} is assigned.
 *
 * @return array<int, string> company id => name
 */
function local_dashboard_get_viewable_company_options(): array {
    global $DB;

    if (local_dashboard_user_is_site_exam_admin()) {
        return $DB->get_records_sql_menu(
            "SELECT id, name
               FROM {company}
           ORDER BY name",
            []
        ) ?: [];
    }

    $options = [];
    $companies = $DB->get_records_sql(
        "SELECT id, name
           FROM {company}
       ORDER BY name"
    );
    foreach ($companies as $company) {
        $cid = (int) $company->id;
        try {
            $cctx = \core\context\company::instance($cid);
        } catch (\Exception $e) {
            continue;
        }
        if (local_dashboard_user_has_view_in_company($cid, $cctx)) {
            $options[$cid] = $company->name;
        }
    }
    return $options;
}

/**
 * Home page URL for the current user (respects Moodle home page preference).
 *
 * @return \moodle_url
 */
function local_dashboard_home_url(): moodle_url {
    switch (get_home_page()) {
        case HOMEPAGE_MY:
            return new moodle_url('/my/');
        case HOMEPAGE_MYCOURSES:
            return new moodle_url('/my/courses.php');
        default:
            return new moodle_url('/');
    }
}

/**
 * Send users without exam dashboard access to their home page.
 *
 * @return void
 */
function local_dashboard_redirect_unauthorised_to_home(): void {
    redirect(local_dashboard_home_url());
}

/**
 * Require exam dashboard access for a company (site admins bypass).
 *
 * @param int $companyid
 * @param \context $companycontext Company context instance.
 */
function local_dashboard_require_dashboard_view(int $companyid, \context $companycontext): void {
    if (local_dashboard_user_is_site_exam_admin()) {
        return;
    }

    if (!local_dashboard_user_has_view_in_company($companyid, $companycontext)) {
        local_dashboard_redirect_unauthorised_to_home();
    }
}

/**
 * Block page access when the user has no exam dashboard permission.
 *
 * @param int $requestedcompanyid companyid from the URL, if any.
 * @return void
 */
function local_dashboard_require_page_access(int $requestedcompanyid = 0): void {
    if (local_dashboard_user_can_view()) {
        if ($requestedcompanyid > 0 && !local_dashboard_user_is_site_exam_admin()) {
            try {
                $companycontext = \core\context\company::instance($requestedcompanyid);
            } catch (\Exception $e) {
                local_dashboard_redirect_unauthorised_to_home();
            }
            local_dashboard_require_dashboard_view($requestedcompanyid, $companycontext);
        }
        return;
    }

    local_dashboard_redirect_unauthorised_to_home();
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

    local_dashboard_require_page_access($requestedcompanyid);

    $canviewallcompanies = local_dashboard_user_is_site_exam_admin();
    $companyoptions = local_dashboard_get_viewable_company_options();

    $companyid = local_dashboard_resolve_company_id($requestedcompanyid, $companyoptions);

    if (!$companyid) {
        if (!local_dashboard_user_can_view()) {
            local_dashboard_redirect_unauthorised_to_home();
        }
        $PAGE->set_url('/local/dashboard/index.php');
        $PAGE->set_context($systemcontext);
        $PAGE->set_pagelayout('report');
        $PAGE->set_title(get_string('heading', 'local_dashboard'));
        $PAGE->set_heading(get_string('heading', 'local_dashboard'));
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('nocompanyavailable', 'local_dashboard'), 'warning');
        echo $OUTPUT->footer();
        return null;
    }

    $companycontext = \core\context\company::instance($companyid);
    local_dashboard_require_dashboard_view($companyid, $companycontext);

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

    // Remember last dashboard company so menu links without companyid do not revert to IOMAD editing company.
    $SESSION->local_dashboard_last_companyid = $companyid;

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
 * Whether review log rows are keyed by quiz attempt id (post-upgrade).
 *
 * @return bool
 */
function local_dashboard_review_log_has_attemptid(): bool {
    global $DB;
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    if (!local_dashboard_review_log_table_ready()) {
        return $has = false;
    }
    $columns = $DB->get_columns('local_dashboard_proctor_review_log');
    return $has = array_key_exists('attemptid', $columns);
}

/**
 * SQL fragments to attach latest review time per attempt (or legacy candidate+quiz).
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
    if (local_dashboard_review_log_has_attemptid()) {
        return [
            'join' => " LEFT JOIN (
                            SELECT attemptid, MAX(timecreated) AS reviewedat
                              FROM {local_dashboard_proctor_review_log}
                             WHERE attemptid > 0
                          GROUP BY attemptid
                       ) ld_rev ON ld_rev.attemptid = qmp.attemptid ",
            'select' => ', ld_rev.reviewedat',
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
 * JOIN + WHERE fragment to exclude attempts already logged as reviewed.
 *
 * Queries must join {quizaccess_main_proctor} as qmp (attempt id).
 *
 * @return array{join: string, where: string}
 */
function local_dashboard_review_log_pending_only_sql_parts(): array {
    if (!local_dashboard_review_log_table_ready()) {
        return ['join' => '', 'where' => ''];
    }
    if (local_dashboard_review_log_has_attemptid()) {
        return [
            'join' => " LEFT JOIN (
                            SELECT attemptid AS apid, MAX(timecreated) AS reviewedat
                              FROM {local_dashboard_proctor_review_log}
                             WHERE attemptid > 0
                          GROUP BY attemptid
                       ) ld_pend ON ld_pend.apid = qmp.attemptid ",
            'where' => ' AND ld_pend.reviewedat IS NULL ',
        ];
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
 * Mark organisations linked to this quiz so the next exam dashboard load (cached index)
 * can refresh review-sensitive metrics without a full snapshot rebuild.
 *
 * @param int $quizid Quiz instance id.
 */
function local_dashboard_mark_index_review_merge_pending_for_quiz(int $quizid): void {
    global $SESSION, $DB;

    if (!local_dashboard_review_log_table_ready()) {
        return;
    }
    $courseid = (int) $DB->get_field('quiz', 'course', ['id' => $quizid], IGNORE_MISSING);
    if ($courseid < 1) {
        return;
    }
    $companyids = $DB->get_fieldset_sql(
        "SELECT DISTINCT companyid FROM {company_course} WHERE courseid = ?",
        [$courseid]
    );
    if (!$companyids) {
        return;
    }
    if (!isset($SESSION->local_dashboard_review_merge) || !is_array($SESSION->local_dashboard_review_merge)) {
        $SESSION->local_dashboard_review_merge = [];
    }
    foreach ($companyids as $cid) {
        $SESSION->local_dashboard_review_merge[(int) $cid] = true;
    }
}

/**
 * True once per company after a review was logged; clears the session flag for that company.
 *
 * @param int $companyid Organisation id for the dashboard context.
 */
function local_dashboard_consume_pending_index_review_merge(int $companyid): bool {
    global $SESSION;

    if (empty($SESSION->local_dashboard_review_merge) || !is_array($SESSION->local_dashboard_review_merge)) {
        return false;
    }
    if (empty($SESSION->local_dashboard_review_merge[$companyid])) {
        return false;
    }
    unset($SESSION->local_dashboard_review_merge[$companyid]);
    return true;
}

/**
 * Log that the current user opened the ProctorLink review page for a candidate attempt.
 *
 * @param int $candidateuserid Student user id.
 * @param int $cmid Quiz course-module id.
 * @param int $quizid Quiz instance id.
 * @param int $attemptid Quiz attempt id (0 = not stored when column missing).
 * @return void
 */
function local_dashboard_note_proctor_review_access(int $candidateuserid, int $cmid, int $quizid, int $attemptid = 0): void {
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
    if ($attemptid > 0 && local_dashboard_review_log_has_attemptid()) {
        $record->attemptid = $attemptid;
    }
    $DB->insert_record('local_dashboard_proctor_review_log', $record);
    local_dashboard_mark_index_review_merge_pending_for_quiz((int) $quizid);
}

/** Minimum proctor alert count for medium-risk (inclusive). */
define('LOCAL_DASHBOARD_RISK_ALERTS_MEDIUM', 3);

/** Minimum proctor alert count for high-risk when not auto-submitted (inclusive). */
define('LOCAL_DASHBOARD_RISK_ALERTS_HIGH', 6);

/**
 * Whether an attempt is high-risk (priority queue and pipeline).
 *
 * @param int $alertcount Proctor alert rows for the attempt.
 * @param int $isautosubmit 1 if auto-submitted.
 * @return bool
 */
function local_dashboard_is_high_risk_attempt(int $alertcount, int $isautosubmit): bool {
    return $isautosubmit === 1 || $alertcount >= LOCAL_DASHBOARD_RISK_ALERTS_HIGH;
}

/**
 * Risk bucket for one attempt (aligned with review pipeline and priority queue).
 *
 * @param int $alertcount
 * @param int $isautosubmit
 * @return string zerorisk|low|medium|high
 */
function local_dashboard_attempt_risk_bucket(int $alertcount, int $isautosubmit): string {
    if ($alertcount === 0) {
        return 'zerorisk';
    }
    if (local_dashboard_is_high_risk_attempt($alertcount, $isautosubmit)) {
        return 'high';
    }
    if ($alertcount >= LOCAL_DASHBOARD_RISK_ALERTS_MEDIUM) {
        return 'medium';
    }
    if ($alertcount >= 1) {
        return 'low';
    }
    return 'zerorisk';
}

/**
 * Severity + status language keys for a priority-queue row (status uses review log when applicable).
 *
 * Row must include: alertcount, isautosubmit; optional reviewedat (unix int or null).
 *
 * @param stdClass $row
 * @return array{0: string, 1: string} [severitykey, statuskey] full keys e.g. severityhighrisk, statusreviewed
 */
function local_dashboard_queue_row_status_keys(stdClass $row): array {
    $alerts = (int) $row->alertcount;
    $isautosubmit = (int) $row->isautosubmit;
    $reviewed = !empty($row->reviewedat);

    $bucket = local_dashboard_attempt_risk_bucket($alerts, $isautosubmit);
    $severitykey = 'severitylow';
    if ($bucket === 'high') {
        $severitykey = 'severityhighrisk';
    } else if ($bucket === 'medium') {
        $severitykey = 'severitymedium';
    } else if ($bucket === 'low') {
        $severitykey = 'severitylow';
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
 * CSS classes for a priority-queue severity pill (aligned with review pipeline colours).
 *
 * @param string $severitykey e.g. severityhighrisk
 * @return string
 */
function local_dashboard_queue_severity_pill_classes(string $severitykey): string {
    $map = [
        'severityhighrisk' => 'ld-pill ld-pill-sev-highrisk ld-pill-sev-critical',
        'severitymedium' => 'ld-pill ld-pill-sev-medium',
        'severitylow' => 'ld-pill ld-pill-sev-low',
    ];
    return $map[$severitykey] ?? 'ld-pill ld-pill-sev-low';
}

/**
 * Load jQuery DataTables (same CDN stack as quizaccess_quizproctoring reports).
 *
 * @return void
 */
function local_dashboard_require_datatables(): void {
    global $PAGE;

    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css'));
    $PAGE->requires->js(new moodle_url('https://code.jquery.com/jquery-3.7.0.min.js'), true);
    $PAGE->requires->js(new moodle_url('https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js'), true);
}

/**
 * Initialise a client-side DataTable on a detail-page table.
 *
 * Call {@see local_dashboard_require_datatables()} before $OUTPUT->header(); this function
 * only queues the footer JavaScript initialisation.
 *
 * @param string $selector jQuery selector (e.g. '#ld-queue-datatable').
 * @param int $pagelength Rows per page (default 50).
 * @param array $options Optional keys: order (array), columndefs (array), searchable (bool).
 * @return void
 */
function local_dashboard_init_datatable(string $selector, int $pagelength = 50, array $options = []): void {
    global $PAGE;

    $pagelength = max(10, min(500, $pagelength));
    $searchable = $options['searchable'] ?? true;

    $config = [
        'pageLength' => $pagelength,
        'lengthMenu' => [[25, 50, 100, -1], [25, 50, 100, 'All']],
        'order' => $options['order'] ?? [],
        'searching' => (bool) $searchable,
        'language' => [
            'search' => 'Search:',
            'lengthMenu' => 'Show _MENU_ rows',
            'info' => 'Showing _START_ to _END_ of _TOTAL_',
            'paginate' => [
                'next' => 'Next',
                'previous' => 'Previous',
            ],
            'zeroRecords' => 'No matching records found',
            'infoEmpty' => 'No records available',
            'infoFiltered' => '(filtered from _MAX_ total)',
        ],
    ];

    if (!empty($options['columndefs'])) {
        $config['columnDefs'] = $options['columndefs'];
    }

    $selectorjson = json_encode($selector);
    $configjson = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    $PAGE->requires->js_init_code(<<<JS
jQuery(function($) {
    var \$table = $({$selectorjson});
    if (!\$table.length) {
        return;
    }
    if ($.fn.DataTable && $.fn.DataTable.isDataTable(\$table)) {
        return;
    }
    \$table.DataTable({$configjson});
});
JS
    );
}

/**
 * Plain text for PDF table cells (strip HTML, normalize whitespace, optional truncate).
 *
 * @param string $value
 * @param int $maxlen
 * @return string
 */
function local_dashboard_pdf_plain(string $value, int $maxlen = 120): string {
    $value = html_to_text($value, 0);
    $value = preg_replace('/\s+/u', ' ', trim($value));
    if ($maxlen > 0 && \core_text::strlen($value) > $maxlen) {
        return \core_text::substr($value, 0, $maxlen - 3) . '...';
    }
    return $value;
}

/**
 * Build title, headers and plain-text rows for action.php PDF export.
 *
 * @param string $view highrisk|lowrisk|scores|activity|spike
 * @param \stdClass $r Bootstrap from {@see local_dashboard_bootstrap_report()}.
 * @return array{filename: string, title: string, subtitle: string, headers: string[], rows: string[][]}|null
 */
function local_dashboard_action_export_pack(string $view, \stdClass $r): ?array {
    global $DB;

    $allowed = ['highrisk', 'spike', 'lowrisk', 'scores', 'activity'];
    if (!in_array($view, $allowed, true)) {
        return null;
    }

    $companyid = (int) $r->companyid;
    $companyname = (string) $r->companyname;
    $timerangeoptions = $r->timerangeoptions;
    $quizoptions = $r->quizoptions;
    $timerange = (int) $r->timerange;
    $quizid = (int) $r->quizid;
    $fromtime = (int) $r->fromtime;
    $baseparams = $r->baseparams;
    $quizsql = $r->quizsql;

    $title = get_string('actionpage_title_' . $view, 'local_dashboard');
    $subtitle = $companyname . ' · ' . get_string('detailfiltercontext', 'local_dashboard', (object) [
        'timerange' => $timerangeoptions[$timerange] ?? '',
        'quiz' => $quizoptions[$quizid] ?? '',
    ]);
    $filename = 'exam-dashboard-' . $view . '-company-' . $companyid . '-' . userdate(time(), '%Y%m%d');

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

    if ($view === 'highrisk' || $view === 'lowrisk') {
        $having = '';
        if ($view === 'highrisk') {
            $having = " HAVING SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) > 0
            AND (MAX(qmp.isautosubmit) = 1
            OR SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) >= 6)
        ORDER BY alertcount DESC, isautosubmit DESC";
        } else {
            $having = " HAVING SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) > 0
            AND MAX(qmp.isautosubmit) = 0
            AND SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) >= 1
            AND SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) <= 2
        ORDER BY alertcount DESC";
        }
        $records = $DB->get_records_sql($queueselect . $queuefrom . $queuegroup . $having, $baseparams);
        $headers = [
            get_string('rank', 'local_dashboard'),
            get_string('queuecandidate', 'local_dashboard'),
            get_string('queueassessment', 'local_dashboard'),
            get_string('queuealerts', 'local_dashboard'),
            get_string('queueseverity', 'local_dashboard'),
        ];
        $rows = [];
        $rank = 1;
        foreach ($records as $row) {
            [$severitykey] = local_dashboard_queue_row_status_keys($row);
            $rows[] = [
                (string) $rank++,
                local_dashboard_pdf_plain(fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname])),
                local_dashboard_pdf_plain(format_string($row->quizname)),
                (string) (int) $row->alertcount,
                local_dashboard_pdf_plain(get_string($severitykey, 'local_dashboard')),
            ];
        }
        return [
            'filename' => $filename,
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    if ($view === 'scores') {
        $records = local_dashboard_fetch_ranked_scores_rows($quizsql, $baseparams);
        $headers = [
            get_string('exportcsv_rank', 'local_dashboard'),
            get_string('exportcsv_userid', 'local_dashboard'),
            get_string('firstname', 'moodle'),
            get_string('lastname', 'moodle'),
            get_string('exportcsv_quizid', 'local_dashboard'),
            get_string('tableassessment', 'local_dashboard'),
            get_string('tablecourse', 'local_dashboard'),
            get_string('exportcsv_scorepct', 'local_dashboard'),
            get_string('tableattempts', 'local_dashboard'),
            get_string('tablealerts', 'local_dashboard'),
            get_string('tablefailed', 'local_dashboard'),
        ];
        $rows = [];
        $rank = 1;
        foreach ($records as $row) {
            $failed = (int) ($row->failedflag ?? 0) > 0;
            $rows[] = [
                (string) $rank++,
                (string) (int) $row->userid,
                (string) $row->firstname,
                (string) $row->lastname,
                (string) (int) $row->quizid,
                local_dashboard_pdf_plain(format_string($row->quizname)),
                local_dashboard_pdf_plain(format_string($row->coursename)),
                format_float((float) $row->bestscore, 1),
                number_format((int) ($row->attemptcount ?? 0)),
                number_format((int) $row->alertcount),
                local_dashboard_pdf_plain($failed ? get_string('yes') : get_string('no')),
            ];
        }
        return [
            'filename' => $filename,
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    // activity or spike.
    $stats = local_dashboard_fetch_assessment_stats($companyid, $fromtime, $quizsql, $baseparams);
    usort($stats, static function ($a, $b) {
        return ((int) $b->alerts) <=> ((int) $a->alerts);
    });
    if ($view === 'spike') {
        $stats = array_values(array_filter($stats, static function ($row) {
            return (int) $row->alerts >= 3;
        }));
    }
    $headers = [
        get_string('rank', 'local_dashboard'),
        get_string('tableassessment', 'local_dashboard'),
        get_string('tablecourse', 'local_dashboard'),
        get_string('statcandidates', 'local_dashboard'),
        get_string('tableattempts', 'local_dashboard'),
        get_string('tablealerts', 'local_dashboard'),
        get_string('statflagged', 'local_dashboard'),
    ];
    $rows = [];
    $rank = 1;
    foreach ($stats as $row) {
        $quizlabel = format_string($row->quiznameraw);
        $rows[] = [
            (string) $rank++,
            local_dashboard_pdf_plain($quizlabel),
            local_dashboard_pdf_plain((string) $row->course),
            number_format($row->users),
            number_format($row->attempts),
            number_format($row->alerts),
            number_format($row->flaggedunion),
        ];
    }
    return [
        'filename' => $filename,
        'title' => $title,
        'subtitle' => $subtitle,
        'headers' => $headers,
        'rows' => $rows,
    ];
}

/**
 * Send a landscape PDF table download and exit.
 *
 * @param string $filename Base filename without extension.
 * @param string $title Report title.
 * @param string $subtitle Filter context line.
 * @param string[] $headers Column headings.
 * @param string[][] $rows Table body (plain text).
 */
function local_dashboard_download_table_pdf(
    string $filename,
    string $title,
    string $subtitle,
    array $headers,
    array $rows
): void {
    global $CFG;

    require_once($CFG->libdir . '/pdflib.php');

    $pdf = new pdf('L', 'mm', 'A4');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $font = PDF_DEFAULT_FONT;
    $pdf->SetFont($font, 'B', 14);
    $pdf->Cell(0, 8, local_dashboard_pdf_plain($title, 200), 0, 1, 'L');
    $pdf->SetFont($font, '', 9);
    $pdf->MultiCell(0, 5, local_dashboard_pdf_plain($subtitle, 500), 0, 'L');
    $pdf->Ln(3);

    if (empty($headers)) {
        $pdf->Output(clean_filename($filename) . '.pdf', 'D');
        exit;
    }

    $margins = $pdf->getMargins();
    $pagewidth = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
    $colcount = count($headers);
    $colwidth = $pagewidth / max(1, $colcount);

    $pdf->SetFont($font, 'B', 8);
    $pdf->SetFillColor(235, 235, 235);
    foreach ($headers as $header) {
        $pdf->Cell($colwidth, 7, local_dashboard_pdf_plain($header, 80), 1, 0, 'L', true);
    }
    $pdf->Ln();

    $pdf->SetFont($font, '', 8);
    $pdf->SetFillColor(255, 255, 255);
    if (empty($rows)) {
        $pdf->Cell($pagewidth, 7, get_string('nofiltereddata', 'local_dashboard'), 1, 1, 'C');
    } else {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $pdf->Cell($colwidth, 7, local_dashboard_pdf_plain((string) $cell, 80), 1, 0, 'L');
            }
            $pdf->Ln();
        }
    }

    $pdf->Output(clean_filename($filename) . '.pdf', 'D');
    exit;
}

/**
 * CSV download for action.php (same data columns as PDF export pack).
 *
 * @param string $view highrisk|lowrisk|scores|activity|spike
 * @param \stdClass $r Bootstrap from {@see local_dashboard_bootstrap_report()}.
 */
function local_dashboard_download_action_csv(string $view, \stdClass $r): void {
    global $CFG;

    $pack = local_dashboard_action_export_pack($view, $r);
    if ($pack === null) {
        return;
    }

    require_once($CFG->libdir . '/csvlib.class.php');
    $csv = new csv_export_writer();
    $csv->set_filename($pack['filename']);
    $csv->add_data($pack['headers']);
    foreach ($pack['rows'] as $row) {
        $csv->add_data($row);
    }
    $csv->download_file();
}

/**
 * ProctorLink overall report URL for a quiz (proctoringreport.php).
 *
 * @param int $quizid
 * @return moodle_url|null
 */
function local_dashboard_proctoring_report_url(int $quizid): ?moodle_url {
    if ($quizid < 1) {
        return null;
    }
    $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, IGNORE_MISSING);
    if (!$cm) {
        return null;
    }
    $modctx = context_module::instance($cm->id);
    if (!has_capability('quizaccess/quizproctoring:quizproctoringoverallreport', $modctx)) {
        return null;
    }
    return new moodle_url('/mod/quiz/accessrule/quizproctoring/proctoringreport.php', [
        'cmid' => $cm->id,
        'quizid' => $quizid,
    ]);
}

/**
 * Link to ProctorLink proctoringreport.php for a quiz, or plain label if unavailable.
 *
 * @param int $quizid
 * @return string HTML
 */
function local_dashboard_proctoring_report_link_html(int $quizid): string {
    $url = local_dashboard_proctoring_report_url($quizid);
    $label = get_string('viewproctorreport', 'local_dashboard');
    if ($url === null) {
        return html_writer::span($label, 'text-muted');
    }
    return html_writer::link($url, $label, [
        'class' => 'ld-proctor-report-link',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]);
}

/**
 * PDF and CSV download buttons for action.php detail views.
 *
 * @param \stdClass $r Bootstrap object.
 * @param string $view Current view key.
 * @param int $rowcount Unused; kept for call-site compatibility.
 * @return string HTML
 */
function local_dashboard_action_export_buttons_html(\stdClass $r, string $view, int $rowcount = 0): string {
    $base = array_merge(local_dashboard_filter_url_params($r), [
        'view' => $view,
        'sesskey' => sesskey(),
    ]);
    $pdfurl = new moodle_url('/local/dashboard/action.php', array_merge($base, ['export' => 'pdf']));
    $csvurl = new moodle_url('/local/dashboard/action.php', array_merge($base, ['export' => 'csv']));
    $buttons = html_writer::link(
        $pdfurl,
        get_string('downloadpdf', 'local_dashboard'),
        ['class' => 'btn btn-secondary ld-export-pdf-link']
    );
    $buttons .= ' ' . html_writer::link(
        $csvurl,
        get_string('downloadcsv', 'local_dashboard'),
        ['class' => 'btn btn-secondary ld-export-csv-link']
    );
    return html_writer::div($buttons, 'ld-action-export-buttons');
}

/**
 * Export row: row count summary + download buttons.
 *
 * @param \stdClass $r Bootstrap object.
 * @param string $view View key.
 * @param int $rowcount Rows in the current table.
 * @param string $countstring Lang string key for count (e.g. actionpage_rowcount).
 * @return string HTML
 */
function local_dashboard_action_export_row_html(\stdClass $r, string $view, int $rowcount, string $countstring): string {
    $out = html_writer::start_div('ld-action-export-row');
    $out .= html_writer::div(get_string($countstring, 'local_dashboard', $rowcount), 'ld-detail-meta ld-detail-summary');
    if ($rowcount > 0) {
        $out .= local_dashboard_action_export_buttons_html($r, $view, $rowcount);
    }
    $out .= html_writer::end_div();
    return $out;
}

/**
 * URL for a recommended-action detail page.
 *
 * @param \stdClass $r Bootstrap object from local_dashboard_bootstrap_report().
 * @param string $view One of: highrisk, spike, lowrisk, scores, activity.
 * @return moodle_url
 */
function local_dashboard_action_view_url(\stdClass $r, string $view): moodle_url {
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
 * @return stdClass[] List rows: userid, firstname, lastname, quizid, quizname, coursename, bestscore,
 *     failedflag, alertcount, attemptcount.
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
                COUNT(DISTINCT CASE WHEN pd.deleted = 0 AND pd.status != '' THEN pd.id ELSE NULL END) AS alertcount,
                COUNT(DISTINCT qa.id) AS attemptcount
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
 * Table headers for ranked scores detail tables (action scores view).
 *
 * @return string[]
 */
function local_dashboard_scores_table_head(): array {
    return [
        get_string('rank', 'local_dashboard'),
        get_string('candidate', 'local_dashboard'),
        get_string('tablecourse', 'local_dashboard'),
        get_string('tableassessment', 'local_dashboard'),
        get_string('score', 'local_dashboard'),
        get_string('tableattempts', 'local_dashboard'),
        get_string('tablealerts', 'local_dashboard'),
        get_string('tablefailed', 'local_dashboard'),
        get_string('queuereview', 'local_dashboard'),
        get_string('proctorreport', 'local_dashboard'),
    ];
}

/**
 * One ranked-scores table row (HTML cells).
 *
 * @param \stdClass $row From {@see local_dashboard_fetch_ranked_scores_rows()}.
 * @param \stdClass $r Dashboard bootstrap.
 * @param int $rank Display rank (1-based).
 * @return string[]
 */
function local_dashboard_scores_table_row_cells(\stdClass $row, \stdClass $r, int $rank): array {
    $alertcount = (int) $row->alertcount;
    $statuspill = $alertcount > 0
        ? html_writer::span(get_string('statusalertcount', 'local_dashboard', $alertcount), 'ld-pill ld-pill-stat-pending')
        : html_writer::span(get_string('statusclean', 'local_dashboard'), 'ld-pill ld-pill-stat-clean');
    $name = fullname((object) ['firstname' => $row->firstname, 'lastname' => $row->lastname]);
    $failed = (int) ($row->failedflag ?? 0) > 0;
    $reviewlink = local_dashboard_proctor_reviewattempts_link_html(
        (int) $row->userid,
        (int) $row->quizid,
        [],
        (int) $r->companyid
    );

    return [
        html_writer::span((string) $rank, 'ld-rank-box'),
        html_writer::div($name, 'ld-candidate-name'),
        format_string($row->coursename),
        local_dashboard_quizview_link_html((int) $row->quizid, (string) $row->quizname),
        html_writer::span(format_float((float) $row->bestscore, 1) . '%', 'ld-score-cell'),
        number_format((int) ($row->attemptcount ?? 0)),
        $statuspill,
        $failed ? get_string('yes') : get_string('no'),
        $reviewlink,
        local_dashboard_proctoring_report_link_html((int) $row->quizid),
    ];
}

/**
 * Maximum assessment health cards shown on the main exam dashboard index widget.
 *
 * @return int
 */
function local_dashboard_assessment_health_widget_max(): int {
    return 6;
}

/**
 * Cards to show in the index assessment health widget (top by alerts, then flagged).
 *
 * @param array $assessmentstats From {@see local_dashboard_fetch_assessment_stats()}.
 * @return array{cards: array, total: int}
 */
function local_dashboard_assessment_health_widget_cards(array $assessmentstats): array {
    $total = count($assessmentstats);
    if ($total === 0) {
        return ['cards' => [], 'total' => 0];
    }
    $cards = array_values($assessmentstats);
    usort($cards, static function ($a, $b) {
        $cmp = ((int) $b->alerts) <=> ((int) $a->alerts);
        if ($cmp !== 0) {
            return $cmp;
        }
        return ((int) $b->flaggedunion) <=> ((int) $a->flaggedunion);
    });
    $max = local_dashboard_assessment_health_widget_max();
    if ($total > $max) {
        $cards = array_slice($cards, 0, $max);
    }
    return ['cards' => $cards, 'total' => $total];
}

/**
 * Per-assessment health stats for the company (same logic as the main dashboard).
 *
 * @param int $companyid Company id.
 * @param int $fromtime Unix timestamp lower bound for attempts.
 * @param string $quizsql Fragment e.g. " AND q.id = :quizid " or empty.
 * @param array $baseparams Params including companyid, fromtime, optional quizid.
 * @return stdClass[] List of row objects for cards/tables (quizzes with no attempts in range are omitted).
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

        $attempts = (int) $DB->count_records_sql(
            "SELECT COUNT(qa.id)
               FROM {quiz_attempts} qa
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime",
            $params
        );
        if ($attempts < 1) {
            continue;
        }

        $users = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.userid)
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

        $attemptriskrows = $DB->get_records_sql(
            "SELECT qa.id AS attemptid,
                    COALESCE(MAX(qmp.isautosubmit), 0) AS isautosubmit,
                    SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) AS warningcount
               FROM {quiz_attempts} qa
               LEFT JOIN {quizaccess_main_proctor} qmp
                      ON qmp.attemptid = qa.id
                     AND qmp.quizid = qa.quiz
                     AND qmp.deleted = 0
               LEFT JOIN {quizaccess_proctor_data} pd
                      ON pd.attemptid = qa.id
                     AND pd.quizid = qa.quiz
              WHERE qa.quiz = :quizid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
           GROUP BY qa.id",
            $params
        );
        $zeroriskcnt = 0;
        $lowriskcnt = 0;
        $mediumriskcnt = 0;
        $highriskcnt = 0;
        foreach ($attemptriskrows as $attemptrow) {
            switch (local_dashboard_attempt_risk_bucket((int) $attemptrow->warningcount, (int) $attemptrow->isautosubmit)) {
                case 'high':
                    $highriskcnt++;
                    break;
                case 'medium':
                    $mediumriskcnt++;
                    break;
                case 'low':
                    $lowriskcnt++;
                    break;
                default:
                    $zeroriskcnt++;
            }
        }

        $at = max(1, $attempts);
        $clearedpct = ($zeroriskcnt / $at) * 100.0;
        $orangepct = (($lowriskcnt + $mediumriskcnt) / $at) * 100.0;
        $highriskpct = ($highriskcnt / $at) * 100.0;
        $completedpct = $at > 0 ? (($finished / $at) * 100.0) : 0.0;

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
function local_dashboard_resolve_company_id(int $requestedcompanyid, array $companyoptions): int {
    global $SESSION;

    if ($requestedcompanyid > 0 && array_key_exists($requestedcompanyid, $companyoptions)) {
        return $requestedcompanyid;
    }

    if (!empty($SESSION->local_dashboard_last_companyid)) {
        $last = (int) $SESSION->local_dashboard_last_companyid;
        if (array_key_exists($last, $companyoptions)) {
            return $last;
        }
    }

    if (!empty($SESSION->currenteditingcompany)) {
        $editing = (int) $SESSION->currenteditingcompany;
        if (array_key_exists($editing, $companyoptions)) {
            return $editing;
        }
    }

    if (!empty($companyoptions)) {
        return (int) array_key_first($companyoptions);
    }

    return 0;
}

function local_dashboard_index_url(): moodle_url {
    $companyid = local_dashboard_first_company_with_dashboard_view();
    if ($companyid > 0) {
        return new moodle_url('/local/dashboard/index.php', ['companyid' => $companyid]);
    }
    return new moodle_url('/local/dashboard/index.php');
}

function local_dashboard_first_company_with_dashboard_view(): int {
    $companyoptions = local_dashboard_get_viewable_company_options();
    if ($companyoptions === []) {
        return 0;
    }
    return local_dashboard_resolve_company_id(0, $companyoptions);
}

/**
 * Whether the user may view the exam dashboard for this company.
 *
 * Site admins: always. Others: explicit {@see local/dashboard:view} only.
 *
 * @param int $companyid
 * @param \context|null $companycontext Optional pre-loaded company context.
 * @return bool
 */
function local_dashboard_user_has_view_in_company(int $companyid, ?\context $companycontext = null): bool {
    global $USER;

    if ($companyid < 1) {
        return false;
    }
    if (local_dashboard_user_is_site_exam_admin()) {
        return true;
    }
    if ($companycontext === null) {
        try {
            $companycontext = \core\context\company::instance($companyid);
        } catch (\Exception $e) {
            return false;
        }
    }
    return has_capability('local/dashboard:view', $companycontext, $USER->id, false);
}

/**
 * Whether the current user may see the exam dashboard menu and open the plugin.
 *
 * @return bool
 */
function local_dashboard_user_can_view(): bool {
    if (!isloggedin() || isguestuser()) {
        return false;
    }
    if (local_dashboard_user_is_site_exam_admin()) {
        return true;
    }
    return local_dashboard_first_company_with_dashboard_view() > 0;
}

/**
 * Remove exam dashboard URLs from custom menu text (site or company menu).
 *
 * @param string $text Custom menu definition text.
 * @return string
 */
function local_dashboard_strip_dashboard_from_custom_menu_text(string $text): string {
    if ($text === '') {
        return '';
    }

    $kept = [];
    foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (strpos($line, '/local/dashboard/') !== false) {
            continue;
        }
        $kept[] = $line;
    }

    return implode("\n", $kept);
}

/**
 * Append the exam dashboard link to custom menu text when missing.
 *
 * Used for both site and IOMAD company custom menus (company menu replaces site config).
 *
 * @param string $text
 * @return string
 */
function local_dashboard_append_dashboard_to_custom_menu_text(string $text): string {
    if (strpos($text, '/local/dashboard/') !== false) {
        return $text;
    }

    $label = get_string('pluginname', 'local_dashboard');
    $path = local_dashboard_index_url()->out_omit_querystring(false);

    return rtrim($text) . "\n{$label}|{$path}\n";
}

/**
 * Filter custom menu text for the current user (called from core primary navigation).
 *
 * Capable users get the exam dashboard link appended when missing. Others have dashboard lines removed.
 *
 * @param string $text
 * @return string
 */
function local_dashboard_filter_custom_menu_items_text(string $text): string {
    if (!local_dashboard_user_can_view()) {
        return local_dashboard_strip_dashboard_from_custom_menu_text($text);
    }
    return local_dashboard_append_dashboard_to_custom_menu_text($text);
}

/**
 * Ensure exam dashboard appears in $CFG->custommenuitems for this request (site menu fallback).
 *
 * @return void
 */
function local_dashboard_ensure_site_custom_menu_link(): void {
    global $CFG;

    if (!isloggedin() || isguestuser() || !local_dashboard_user_can_view()) {
        return;
    }

    if (!isset($CFG->dbunmodifiedcustommenuitems)) {
        $CFG->dbunmodifiedcustommenuitems = $CFG->custommenuitems ?? '';
    }

    $CFG->custommenuitems = local_dashboard_append_dashboard_to_custom_menu_text($CFG->custommenuitems ?? '');
}

/**
 * Strip dashboard links from site custom menu config for users without access.
 *
 * @return void
 */
function local_dashboard_sanitize_site_custom_menu(): void {
    global $CFG;

    if (!isloggedin() || isguestuser() || local_dashboard_user_can_view()) {
        return;
    }

    $CFG->custommenuitems = local_dashboard_strip_dashboard_from_custom_menu_text($CFG->custommenuitems ?? '');
}

/**
 * Extend the main navigation drawer with the Exam Dashboard link.
 *
 * The link is added for site admins or users with {@see local/dashboard:view} in at least one company.
 * Top navbar: {@see \core\hook\navigation\primary_extend}, custom menu filter, and site menu fallback.
 *
 * @param global_navigation $nav
 * @return void
 */
function local_dashboard_extend_navigation(global_navigation $nav): void {
    local_dashboard_sanitize_site_custom_menu();
    local_dashboard_ensure_site_custom_menu_link();

    if (!local_dashboard_user_can_view()) {
        return;
    }

    $label = get_string('pluginname', 'local_dashboard');
    $indexurl = local_dashboard_index_url();

    if (!$nav->find('local_dashboard', navigation_node::TYPE_CUSTOM)) {
        $node = $nav->add(
            $label,
            $indexurl,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_dashboard',
            new pix_icon('i/report', '')
        );
        if ($node) {
            $node->showinflatnavigation = true;
            $node->mainnavonly = true;
        }
    }
}

/**
 * Extend the settings navigation with the Exam Dashboard shortcut for capable users.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 * @return void
 */
function local_dashboard_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    if (!local_dashboard_user_can_view()) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_dashboard'),
        local_dashboard_index_url(),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_dashboard_settings',
        new pix_icon('i/report', '')
    );
    $settingsnav->add_node($node);
}

/**
 * Company courses where the current user may add a new quiz/assessment.
 *
 * @param int $companyid
 * @return int[] Course ids, ascending.
 */
function local_dashboard_company_creatable_course_ids(int $companyid): array {
    global $DB, $CFG;

    $companycourseids = $DB->get_fieldset_sql(
        "SELECT cc.courseid
           FROM {company_course} cc
          WHERE cc.companyid = :companyid
       ORDER BY cc.courseid",
        ['companyid' => $companyid]
    );
    if ($companycourseids === []) {
        return [];
    }

    $creatable = [];
    $usequickquiz = file_exists($CFG->dirroot . '/local/quickquiz/lib.php');
    if ($usequickquiz) {
        require_once($CFG->dirroot . '/local/quickquiz/lib.php');
    }

    foreach ($companycourseids as $courseid) {
        $courseid = (int) $courseid;
        if ($usequickquiz && local_quickquiz_can_create_in_course($courseid)) {
            $creatable[] = $courseid;
            continue;
        }
        if (!$usequickquiz) {
            $ctx = context_course::instance($courseid, IGNORE_MISSING);
            if ($ctx && has_capability('moodle/course:manageactivities', $ctx)) {
                $creatable[] = $courseid;
            }
        }
    }

    return $creatable;
}

/**
 * URL for the "+ New Assessment" control on the exam dashboard.
 *
 * Uses {@see local_quickquiz} when installed; otherwise falls back to core quiz modedit.
 * When the company has more than one creatable course and no single assessment is
 * selected, opens the course picker (no courseid in the URL).
 *
 * @param int $companyid Organisation filter.
 * @param int $quizid Assessment filter (0 = all).
 * @return moodle_url|null Null when the user cannot create a quiz in any company course.
 */
function local_dashboard_new_assessment_url(int $companyid, int $quizid): ?moodle_url {
    global $DB, $CFG;

    $creatable = local_dashboard_company_creatable_course_ids($companyid);
    if ($creatable === []) {
        return null;
    }

    $targetcourseid = 0;
    if ($quizid > 0) {
        $quizcourseid = (int) $DB->get_field('quiz', 'course', ['id' => $quizid], IGNORE_MISSING);
        if ($quizcourseid > 0 && in_array($quizcourseid, $creatable, true)) {
            $targetcourseid = $quizcourseid;
        }
    } else if (count($creatable) === 1) {
        $targetcourseid = $creatable[0];
    }

    if (file_exists($CFG->dirroot . '/local/quickquiz/lib.php')) {
        $quickquizparams = ['companyid' => $companyid];
        if ($targetcourseid > 0) {
            $quickquizparams['courseid'] = $targetcourseid;
        }
        return new moodle_url('/local/quickquiz/index.php', $quickquizparams);
    }

    // Fallback: full Moodle quiz activity form (single course only).
    if (count($creatable) !== 1) {
        return null;
    }

    return new moodle_url('/course/modedit.php', [
        'add' => 'quiz',
        'type' => '',
        'course' => $creatable[0],
        'section' => 0,
        'sr' => 0,
    ]);
}
