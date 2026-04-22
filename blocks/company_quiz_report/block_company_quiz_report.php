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
 * Block to show company quiz and proctoring summary.
 *
 * @package   block_company_quiz_report
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/iomad/lib/company.php');

/**
 * Company quiz/proctoring report block.
 */
class block_company_quiz_report extends block_base {
    /**
     * Initialise block title.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_company_quiz_report');
    }

    /**
     * Block has no plugin-level settings page.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Use custom header inside content for a cleaner dashboard card look.
     *
     * @return bool
     */
    public function hide_header() {
        return true;
    }

    /**
     * Build block contents.
     *
     * @return stdClass
     */
    public function get_content() {
        global $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $this->page->requires->css(new moodle_url('/blocks/company_quiz_report/styles.css'));

        $systemcontext = context_system::instance();

        $companyoptions = company::get_companies_select(false, false, true, 'name');
        if (empty($companyoptions)) {
            $this->content->text = html_writer::div(
                html_writer::div(
                    $OUTPUT->pix_icon('i/warning', '', 'moodle', ['class' => 'me-2']) .
                    get_string('nocompanyavailable', 'block_company_quiz_report'),
                    'd-flex align-items-center'
                ),
                'alert alert-warning company-quiz-report__empty mb-0'
            );
            return $this->content;
        }

        $selectedcompanyid = $this->resolve_selected_company($companyoptions);
        if (!array_key_exists($selectedcompanyid, $companyoptions)) {
            $selectedcompanyid = (int) array_key_first($companyoptions);
        }

        // Capability is defined at CONTEXT_COMPANY; check in company context (not block context).
        $companycontext = \core\context\company::instance($selectedcompanyid);
        if (!iomad::has_capability('block/company_quiz_report:view', $companycontext, $selectedcompanyid)) {
            return $this->content;
        }

        $selectorhtml = '';
        $canviewallcompanies = iomad::has_capability('block/iomad_company_admin:company_view_all', $systemcontext);
        if ($canviewallcompanies || count($companyoptions) > 1) {
            $selectorhtml = $this->render_company_selector($companyoptions, $selectedcompanyid, $OUTPUT);
        }

        $summary = $this->get_company_summary($selectedcompanyid);
        $this->content->text .= $this->render_report_shell(
            $OUTPUT,
            $companyoptions[$selectedcompanyid],
            $selectedcompanyid,
            $selectorhtml,
            $summary
        );

        return $this->content;
    }

    /**
     * Resolve selected company id for the report.
     *
     * @param array $companyoptions
     * @return int
     */
    private function resolve_selected_company(array $companyoptions): int {
        $requestedcompanyid = optional_param('companyid', 0, PARAM_INT);
        if ($requestedcompanyid > 0 && array_key_exists($requestedcompanyid, $companyoptions)) {
            return $requestedcompanyid;
        }

        $defaultcompanyid = iomad::get_my_companyid(context_system::instance(), false);
        if ($defaultcompanyid > 0 && array_key_exists($defaultcompanyid, $companyoptions)) {
            return $defaultcompanyid;
        }

        return (int) array_key_first($companyoptions);
    }

    /**
     * Render company dropdown selector.
     *
     * @param array $companyoptions
     * @param int $selectedcompanyid
     * @param core_renderer $output
     * @return string
     */
    private function render_company_selector(array $companyoptions, int $selectedcompanyid, core_renderer $output): string {
        $currenturl = new moodle_url($this->page->url->out_omit_querystring(), $this->page->url->params());
        $selector = new single_select(
            $currenturl,
            'companyid',
            $companyoptions,
            $selectedcompanyid,
            null,
            'company-quiz-report-selector'
        );
        $selector->label = get_string('selectcompany', 'block_company_quiz_report');

        return html_writer::div($output->render($selector), 'company-quiz-report__selector');
    }

    /**
     * Return summary metrics for selected company.
     *
     * @param int $companyid
     * @return stdClass
     */
    private function get_company_summary(int $companyid): stdClass {
        global $DB;

        $summary = new stdClass();
        $summary->coursescount = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cc.courseid)
               FROM {company_course} cc
              WHERE cc.companyid = :companyid",
            ['companyid' => $companyid]
        );

        $sqlparams = ['companyid' => $companyid, 'enabled' => 1];

        if ($DB->get_manager()->table_exists('quizaccess_quizproctoring')) {
            $summary->proctoredquizzescount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT q.id)
                   FROM {company_course} cc
                   JOIN {quiz} q ON q.course = cc.courseid
                   JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
                  WHERE cc.companyid = :companyid
                    AND qp.enableproctoring = :enabled",
                $sqlparams
            );
        } else {
            $summary->proctoredquizzescount = 0;
        }

        $summary->usersattemptedcount = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.id)
               FROM {company_course} cc
               JOIN {quiz} q ON q.course = cc.courseid
               JOIN {quiz_attempts} qa ON qa.quiz = q.id
              WHERE cc.companyid = :companyid
                AND qa.preview = 0",
            ['companyid' => $companyid]
        );

        if ($DB->get_manager()->table_exists('quizaccess_main_proctor') &&
            $DB->get_manager()->table_exists('quizaccess_quizproctoring')) {
            $summary->proctorfaileduserscount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT qmp.attemptid)
                   FROM {company_course} cc
                   JOIN {quiz} q ON q.course = cc.courseid
                   JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
                   JOIN {quizaccess_main_proctor} qmp ON qmp.quizid = q.id
                  WHERE cc.companyid = :companyid
                    AND qp.enableproctoring = :enabled
                    AND qmp.deleted = 0
                    AND qmp.image_status = :imagestatus
                    AND qmp.isautosubmit = :isautosubmit
                    AND qmp.attemptid IS NOT NULL
                    AND qmp.attemptid > 0",
                [
                    'companyid' => $companyid,
                    'enabled' => 1,
                    'imagestatus' => 'M',
                    'isautosubmit' => 1,
                ]
            );
        } else {
            $summary->proctorfaileduserscount = 0;
        }

        return $summary;
    }

    /**
     * Render the full styled report shell (hero, optional selector, stat tiles).
     *
     * @param core_renderer $output
     * @param string $companyname
     * @param int $companyid
     * @param string $selectorhtml
     * @param stdClass $summary
     * @return string
     */
    private function render_report_shell(
        core_renderer $output,
        string $companyname,
        int $companyid,
        string $selectorhtml,
        stdClass $summary
    ): string {
        $eyebrow = html_writer::span(get_string('reporteyebrow', 'block_company_quiz_report'), 'company-quiz-report__eyebrow');
        $title = html_writer::tag(
            'h4',
            format_string($companyname),
            ['class' => 'company-quiz-report__title']
        );
        $meta = html_writer::tag(
            'p',
            get_string('reportmeta', 'block_company_quiz_report', $companyid),
            ['class' => 'company-quiz-report__meta']
        );

        $hero = html_writer::div($eyebrow . $title . $meta, 'company-quiz-report__hero');

        $tiles = $this->render_stat_tile(
            $output,
            'courses',
            'i/course',
            'moodle',
            get_string('statcourses', 'block_company_quiz_report'),
            get_string('statcourses_hint', 'block_company_quiz_report'),
            (int) $summary->coursescount
        );
        $tiles .= $this->render_stat_tile(
            $output,
            'proctor',
            't/locked',
            'moodle',
            get_string('statproctor', 'block_company_quiz_report'),
            get_string('statproctor_hint', 'block_company_quiz_report'),
            (int) $summary->proctoredquizzescount
        );
        $tiles .= $this->render_stat_tile(
            $output,
            'attemptedusers',
            'i/users',
            'moodle',
            get_string('statusersattempted', 'block_company_quiz_report'),
            get_string('statusersattempted_hint', 'block_company_quiz_report'),
            (int) $summary->usersattemptedcount
        );
        $tiles .= $this->render_stat_tile(
            $output,
            'failedusers',
            'i/warning',
            'moodle',
            get_string('statusersfailed', 'block_company_quiz_report'),
            get_string('statusersfailed_hint', 'block_company_quiz_report'),
            (int) $summary->proctorfaileduserscount
        );

        $grid = html_writer::div($tiles, 'company-quiz-report__grid');
        $detailsurl = new moodle_url('/blocks/company_quiz_report/details.php', ['companyid' => $companyid]);
        $detailsbutton = html_writer::link(
            $detailsurl,
            get_string('viewdetailedreport', 'block_company_quiz_report'),
            ['class' => 'btn btn-primary btn-sm']
        );
        $actions = html_writer::div($detailsbutton, 'company-quiz-report__actions');

        $shell = html_writer::div($hero . $selectorhtml . $grid . $actions, 'company-quiz-report__shell');

        return html_writer::div($shell, 'company-quiz-report');
    }

    /**
     * One statistic tile with icon, value, and labels.
     *
     * @param core_renderer $output
     * @param string $variant courses|proctor|attemptedusers|failedusers
     * @param string $iconname
     * @param string $iconcomponent
     * @param string $label
     * @param string $hint
     * @param int $value
     * @return string
     */
    private function render_stat_tile(
        core_renderer $output,
        string $variant,
        string $iconname,
        string $iconcomponent,
        string $label,
        string $hint,
        int $value
    ): string {
        $icon = $output->pix_icon(
            $iconname,
            '',
            $iconcomponent,
            ['class' => 'company-quiz-report__tile-icon']
        );
        $valuehtml = html_writer::span((string) $value, 'company-quiz-report__tile-value', ['aria-label' => $label]);
        $labelhtml = html_writer::span($label, 'company-quiz-report__tile-label');
        $hinthtml = html_writer::span($hint, 'company-quiz-report__tile-hint');

        $body = html_writer::div($valuehtml . $labelhtml . $hinthtml, 'company-quiz-report__tile-body');

        return html_writer::div(
            $icon . $body,
            'company-quiz-report__tile company-quiz-report__tile--' . $variant
        );
    }
}
