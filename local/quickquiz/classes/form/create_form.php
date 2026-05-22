<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Simplified quiz create form.
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickquiz\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Quick quiz creation form.
 */
class create_form extends \moodleform {

    /**
     * @return void
     */
    protected function definition() {
        global $CFG;

        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $hasproctoring = $this->_customdata['hasproctoring'];

        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'basic', get_string('fieldname', 'local_quickquiz'));
        $mform->addElement('text', 'name', get_string('fieldname', 'local_quickquiz'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $sections = [];
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->section >= 0) {
                $sections[$section->section] = get_section_name($course, $section->section);
            }
        }
        $mform->addElement('select', 'section', get_string('section', 'local_quickquiz'), $sections);
        $mform->addHelpButton('section', 'section', 'local_quickquiz');

        $mform->addElement('header', 'gradehdr', get_string('fieldgrade', 'local_quickquiz'));

        $defaultgrade = (float) get_config('local_quickquiz', 'defaultgrade');
        if ($defaultgrade <= 0) {
            $defaultgrade = 100;
        }
        $mform->addElement('float', 'grade', get_string('maxgrade', 'local_quickquiz'), ['size' => 6]);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', $defaultgrade);
        $mform->addRule('grade', null, 'required', null, 'client');
        $mform->addHelpButton('grade', 'maxgrade', 'local_quickquiz');

        $gradecategories = grade_get_categories_menu($course->id);
        $defaultcat = local_quickquiz_default_gradecategory_id($course->id);
        if (!array_key_exists($defaultcat, $gradecategories) && !empty($gradecategories)) {
            $defaultcat = (int) array_key_first($gradecategories);
        }
        $mform->addElement('select', 'gradecat', get_string('gradecategoryonmodform', 'grades'), $gradecategories);
        $mform->setDefault('gradecat', $defaultcat);
        $mform->addHelpButton('gradecat', 'gradecategoryonmodform', 'grades');

        $mform->addElement('float', 'gradepass', get_string('gradepass', 'grades'), ['size' => 6]);
        $mform->setType('gradepass', PARAM_FLOAT);
        $mform->setDefault('gradepass', '');
        $mform->addHelpButton('gradepass', 'gradepass', 'grades');

        $attemptoptions = ['0' => get_string('unlimited')];
        for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
            $attemptoptions[$i] = (string) $i;
        }
        $mform->addElement('select', 'attempts', get_string('attemptsallowed', 'quiz'), $attemptoptions);
        $mform->setDefault('attempts', 0);

        $mform->addElement('select', 'grademethod', get_string('grademethod', 'quiz'), quiz_get_grading_options());
        $mform->setDefault('grademethod', QUIZ_GRADEHIGHEST);
        $mform->addHelpButton('grademethod', 'grademethod', 'quiz');

        $mform->addElement('header', 'timing', get_string('fieldtiming', 'local_quickquiz'));
        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'local_quickquiz'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'local_quickquiz'), ['optional' => true]);
        $mform->addElement(
            'duration',
            'timelimit',
            get_string('timelimit', 'local_quickquiz'),
            ['optional' => true, 'defaultunit' => MINSECS]
        );
        $mform->addHelpButton('timelimit', 'timelimit', 'local_quickquiz');

        if ($hasproctoring) {
            require_once($CFG->dirroot . '/mod/quiz/accessrule/quizproctoring/rule.php');
            $mform->addElement('header', 'proctoringhdr', get_string('pluginname', 'quizaccess_quizproctoring'));
            local_quickquiz_add_proctoring_form_fields($mform, $course);
        } else {
            $mform->addElement('static', 'proctoringmissing', '', get_string('proctoringnotinstalled', 'local_quickquiz'));
        }

        $deftimelimit = (int) get_config('local_quickquiz', 'defaulttimelimit');
        if ($deftimelimit > 0) {
            $mform->setDefault('timelimit', $deftimelimit * MINSECS);
        }

        $this->add_action_buttons(true, get_string('createquiz', 'local_quickquiz'));
    }

    /**
     * @return void
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $mform->hideIf('grademethod', 'attempts', 'eq', 1);
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $open = !empty($data['timeopen']) ? (int) $data['timeopen'] : 0;
        $close = !empty($data['timeclose']) ? (int) $data['timeclose'] : 0;
        if ($open > 0 && $close > 0 && $close <= $open) {
            $errors['timeclose'] = get_string('error_timeclose', 'local_quickquiz');
        }

        if (isset($data['grade']) && (float) $data['grade'] <= 0) {
            $errors['grade'] = get_string('required');
        }

        if (!empty($data['gradepass']) && isset($data['grade']) && (float) $data['gradepass'] > (float) $data['grade']) {
            $errors['gradepass'] = get_string('gradepassgreaterthangrade', 'grades', format_float($data['grade'], 2));
        }

        return $errors;
    }
}
