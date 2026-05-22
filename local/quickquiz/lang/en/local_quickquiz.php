<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Language strings.
 *
 * @package   local_quickquiz
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quick quiz creator';
$string['createquiz'] = 'Create quick quiz';
$string['createquizheading'] = 'Create quiz';
$string['selectcourse'] = 'Select course';
$string['selectcoursehelp'] = 'This organisation has more than one course. Choose which course the new quiz should be added to.';
$string['fieldname'] = 'Quiz name';
$string['fieldtiming'] = 'Timing';
$string['fieldgrade'] = 'Grade';
$string['fieldrestrictions'] = 'Extra restrictions';
$string['timeopen'] = 'Open the quiz';
$string['timeclose'] = 'Close the quiz';
$string['timelimit'] = 'Time limit';
$string['timelimit_help'] = 'Maximum time allowed for each attempt. Leave empty for no limit.';
$string['maxgrade'] = 'Maximum grade';
$string['maxgrade_help'] = 'The highest score a student can achieve on this quiz (percentage scale used in reports).';
$string['quizpassword'] = 'Quiz password';
$string['quizpassword_help'] = 'Optional password students must enter before starting the quiz.';
$string['enableproctoring'] = 'Enable ProctorLink proctoring';
$string['enableproctoring_help'] = 'When enabled, identity verification and proctor monitoring apply to this quiz.';
$string['proctoringnotinstalled'] = 'ProctorLink (quizaccess_quizproctoring) is not installed. The quiz will be created without proctoring.';
$string['timeinterval'] = 'Proctor image interval';
$string['warningthreshold'] = 'Warning threshold';
$string['enableprofilematch'] = 'Profile photo match';
$string['enablestudentvideo'] = 'Show student video preview';
$string['enableeyecheck'] = 'Eye tracking';
$string['section'] = 'Course section';
$string['section_help'] = 'Section where the quiz activity will appear on the course page.';
$string['createsuccess'] = 'Quiz "{$a}" was created. Add questions next.';
$string['nocourses'] = 'You do not have permission to create quizzes in any course.';
$string['course'] = 'Course';
$string['continue'] = 'Continue';
$string['cancel'] = 'Cancel to course';
$string['managequiz'] = 'Open quiz';
$string['addquestions'] = 'Add questions';
$string['error_timeclose'] = 'Close time must be after open time.';
$string['privacy:metadata'] = 'The Quick quiz creator plugin does not store personal data. It creates standard Moodle quiz activities.';

$string['settings_defaultgrade'] = 'Default maximum grade';
$string['settings_defaultgrade_desc'] = 'Default value for the grade field on the quick create form.';
$string['settings_defaultproctoring'] = 'Enable proctoring by default';
$string['settings_defaultproctoring_desc'] = 'When ProctorLink is installed, pre-check enable proctoring on the form.';
$string['settings_defaulttimelimit'] = 'Default time limit (minutes)';
$string['settings_defaulttimelimit_desc'] = '0 means no time limit.';

$string['settings_quizheading'] = 'Quiz defaults';
$string['settings_quizheading_desc'] = 'Pre-filled values on the quick create form (name and section are chosen each time).';
$string['settings_proctorheading'] = 'ProctorLink defaults for new quizzes';
$string['settings_proctorheading_desc'] = 'Defaults used when creating a quiz through Quick quiz creator. These are independent of the global ProctorLink plugin settings.';
$string['settings_defaulttimeinterval'] = 'Default proctor image interval';
$string['settings_defaulttimeinterval_desc'] = 'How often proctoring captures images during an attempt.';
$string['settings_defaultwarningthreshold'] = 'Default warning threshold';
$string['settings_defaultwarningthreshold_desc'] = '0 = Unlimited warnings before action.';
$string['settings_unlimited'] = 'Unlimited';
$string['interval_15s'] = '15 seconds';
$string['interval_20s'] = '20 seconds';
$string['interval_30s'] = '30 seconds';
$string['interval_60s'] = '1 minute';
$string['interval_120s'] = '2 minutes';
$string['interval_180s'] = '3 minutes';
$string['interval_240s'] = '4 minutes';
$string['interval_300s'] = '5 minutes';
$string['settings_defaultenableteacherproctor'] = 'Default: enable teacher proctor view';
$string['settings_defaultstoreallimages'] = 'Default: store all images';
$string['settings_defaultenableuploadidentity'] = 'Default: require identity upload';
$string['settings_defaultenableprofilematch'] = 'Default: profile photo match';
$string['settings_defaultenablestudentvideo'] = 'Default: show student video preview';
$string['settings_defaultenableeyecheckreal'] = 'Default: eye tracking';
$string['settings_defaultenablerecordaudio'] = 'Default: record audio';
$string['settings_defaultenableobjectdetect'] = 'Default: object detection';
