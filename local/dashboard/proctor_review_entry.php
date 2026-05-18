<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Records a dashboard “review” log entry then redirects to ProctorLink reviewattempts.php.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$userid = required_param('userid', PARAM_INT);
$quizid = required_param('quizid', PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);

$quiz = $DB->get_record('quiz', ['id' => $quizid], 'id,course', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $quizid, (int) $quiz->course, false, MUST_EXIST);

$modctx = context_module::instance($cm->id);
$canreport = has_capability('quizaccess/quizproctoring:quizproctoringoverallreport', $modctx);
$canviewdashboard = false;
$companyids = $DB->get_fieldset_select('company_course', 'companyid', 'courseid = :cid', ['cid' => (int) $quiz->course]);
foreach ($companyids as $cid) {
    try {
        $cctx = \core\context\company::instance((int) $cid);
        if (has_capability('local/dashboard:view', $cctx)) {
            $canviewdashboard = true;
            break;
        }
    } catch (\Exception $e) {
        continue;
    }
}
if (!$canreport && !$canviewdashboard) {
    require_capability('quizaccess/quizproctoring:quizproctoringoverallreport', $modctx);
}

$reviewurl = local_dashboard_proctor_reviewattempts_url($userid, (int) $cm->id, $quizid);
if ($attemptid > 0) {
    $reviewurl->param('attemptid', $attemptid);
}
redirect($reviewurl);
