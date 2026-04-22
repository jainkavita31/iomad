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
 * Language strings for company quiz report block.
 *
 * @package   block_company_quiz_report
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Company quiz report';
$string['privacy:metadata'] = 'The Company quiz report block only displays aggregate data from existing records.';

$string['company_quiz_report:addinstance'] = 'Add a new Company quiz report block';
$string['company_quiz_report:myaddinstance'] = 'Add a new Company quiz report block to dashboard';
$string['company_quiz_report:view'] = 'View company quiz report';

$string['selectcompany'] = 'Select company';
$string['nocompanyavailable'] = 'No company is available for reporting.';
$string['reporttitle'] = 'Report for {$a->company} (ID: {$a->id})';
$string['reporteyebrow'] = 'Company overview';
$string['reportmeta'] = 'Company ID {$a}';
$string['statcourses'] = 'Courses';
$string['statcourses_hint'] = 'Linked to this company';
$string['statproctor'] = 'ProctorLink';
$string['statproctor_hint'] = 'Quizzes with proctoring enabled';
$string['statusersattempted'] = 'Distinct attempts';
$string['statusersattempted_hint'] = 'Distinct quiz attempts in company courses';
$string['statusersfailed'] = 'Distinct proctor-failed attempts';
$string['statusersfailed_hint'] = 'Distinct auto-submitted attempts by ProctorLink';
$string['viewdetailedreport'] = 'View detailed report';
$string['detailedreporttitle'] = 'Detailed quiz report';
$string['detailedreportheading'] = 'Detailed report for {$a}';
$string['tablecourse'] = 'Course';
$string['tablequiz'] = 'Quiz';
$string['tableuserscount'] = 'Users count';
$string['tableattempts'] = 'Attempts attempted';
$string['tableproctorfailed'] = 'Proctor failed';
$string['tablewarningtriggered'] = 'Warnings triggered';
$string['nodetaileddata'] = 'No proctored quiz data found for this company.';
$string['proctorpluginmissing'] = 'ProctorLink data tables are not available.';
$string['coursescreatedlabel'] = 'Courses created: {$a}';
$string['quizzescreatedlabel'] = 'Quizzes created: {$a}';
$string['quizzeswithproctoringlabel'] = 'Quizzes using ProctorLink: {$a}';
