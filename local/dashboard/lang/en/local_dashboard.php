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
 * Language strings for local_dashboard.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Exam Dashboard';
$string['dashboard:view'] = 'View exam dashboard';
$string['local/dashboard:view'] = 'View exam dashboard';
$string['privacy:metadata'] = 'The exam dashboard stores proctor review access when a reviewer opens ProctorLink reviewattempts.php (reviewer, candidate, quiz, time). Aggregate dashboard metrics are otherwise not stored per user in this plugin.';
$string['privacy:metadata:reviewer_userid'] = 'The user who opened the proctor review screen.';
$string['privacy:metadata:candidate_userid'] = 'The student whose proctoring was viewed.';
$string['privacy:metadata:quizid'] = 'Quiz instance id.';
$string['privacy:metadata:cmid'] = 'Course module id of the quiz.';
$string['privacy:metadata:timecreated'] = 'When the review page was opened.';

$string['heading'] = 'Exam Dashboard';
$string['lastupdated'] = 'Last updated: {$a}';

$string['filtertimerange'] = 'Time range';
$string['filterassessment'] = 'Assessment';
$string['filterdepartment'] = 'Department';
$string['selectcompany'] = 'Company';
$string['allassessments'] = 'All assessments';
$string['alldepartments'] = 'All departments';
$string['last7days'] = 'Last 7 days';
$string['last30days'] = 'Last 30 days';
$string['last90days'] = 'Last 90 days';
$string['last365days'] = 'Last 365 days';
$string['applyfilters'] = 'Apply filters';
$string['resetfilters'] = 'Reset';
$string['newassessment'] = '+ New Assessment';

$string['totalcandidates'] = 'Total candidates assessed';
$string['assessmentsconducted'] = 'Assessments conducted';
$string['flaggedsessions'] = 'Flagged sessions';
$string['reviewbacklog'] = 'Review backlog';
$string['averagescore'] = 'Average score';

$string['integrityanalytics'] = 'Integrity analytics';
$string['totalalerts'] = 'Total alerts';
$string['avgalertspercandidate'] = 'Avg/candidate';
$string['tabswitch'] = 'Tab switch / app change';
$string['facemismatch'] = 'Face mismatch';
$string['absencedetected'] = 'Absence detected';
$string['multiplepeople'] = 'Multiple people';
$string['otheranomalies'] = 'Other anomalies';

$string['reviewpipeline'] = 'Review pipeline';
$string['totalsessions'] = 'Total sessions';
$string['autocleared'] = 'Auto-cleared';
$string['lowriskpending'] = 'Low-risk pending';
$string['mediumriskpending'] = 'Medium-risk pending';
$string['highriskpending'] = 'High-risk pending';

$string['assessmenthealth'] = 'Assessment health overview';
$string['assessmenthealthdesc'] = 'Alert volume, completion and integrity status by assessment';
$string['tablecourse'] = 'Course';
$string['tableassessment'] = 'Assessment';
$string['tablecandidates'] = 'Candidates';
$string['tableattempts'] = 'Attempts';
$string['tablealerts'] = 'Alerts';
$string['tablefailed'] = 'Proctor failed';
$string['tablescore'] = 'Average score';
$string['nofiltereddata'] = 'No data found for selected filters.';
$string['nocompanyavailable'] = 'No company is available for this user.';
$string['nodashboardaccess'] = 'You do not have permission to view the exam dashboard for any organisation you can access.';
$string['proctorpluginmissing'] = 'ProctorLink tables are not available. Please install/enable quizaccess_quizproctoring.';

$string['priorityqueue'] = 'Priority review queue';
$string['priorityqueuedesc'] = 'Sessions ranked by alert severity';
$string['priorityqueuesub'] = 'Sessions ranked by alert severity — review these first';
$string['viewallqueue'] = 'View all {$a} >';
$string['viewallassessments'] = 'View all assessments >';
$string['fullrankinglink'] = 'Full ranking >';
$string['kpi_flaggedsub'] = '{$a}% of total sessions';
$string['kpi_backlogsub'] = '{$a} high-risk pending';
$string['kpi_scores_sub'] = 'Pass rate: {$a}%';
$string['kpi_assessmentssub'] = 'Across {$a} assessments (company courses, all quizzes)';
$string['kpi_assessmentssplit'] = '{$a->completed} completed · {$a->inprogress} in progress';
$string['kpi_candidates_sub'] = 'Within selected filters';
$string['statcandidates'] = 'Candidates';
$string['statcompleted'] = 'Completed';
$string['statflagged'] = 'Flagged';
$string['pctcleared'] = '{$a}% cleared';
$string['pcthighrisk'] = '{$a}% high-risk';
$string['unusualactivity'] = 'Unusual activity';
$string['reviewarrow'] = 'Review →';
$string['exportrankedreport'] = 'Export ranked score report';
$string['exportrankedreportdesc'] = 'Download candidate scores for the selected filters';
$string['downloadactivityreport'] = 'Download suspicious activity report';
$string['downloadactivityreportdesc'] = 'All assessments — sorted by alert count';
$string['topperformers'] = 'Top performers';
$string['topperformersdesc'] = 'Candidates ranked by highest score';
$string['rank'] = '#';
$string['candidate'] = 'Candidate';
$string['score'] = 'Score';
$string['session'] = 'Session';
$string['scoredistribution'] = 'Score distribution';
$string['distribution0_40'] = '0-40';
$string['distribution41_50'] = '41-50';
$string['distribution51_60'] = '51-60';
$string['distribution61_75'] = '61-75';
$string['distribution76_90'] = '76-90';
$string['distribution91_100'] = '91-100';
$string['avgscorelabel'] = 'Avg score';
$string['passratelabel'] = 'Pass rate';
$string['medianlabel'] = 'Median';
$string['recommendedactions'] = 'Recommended actions';
$string['reviewhighriskaction'] = 'Review high-risk sessions';
$string['reviewhighriskactioncount'] = 'Review {$a} high-risk sessions';
$string['reviewhighriskdesc'] = 'Critical and high-severity alerts pending';
$string['investigateaction'] = 'Investigate alert spike';
$string['investigatedesc'] = 'Warning trend above normal threshold';
$string['approveaction'] = 'Approve low-risk sessions';
$string['approvedesc'] = 'Auto-cleared with no significant alerts';
$string['fullranking'] = 'Full ranking';
$string['queuecandidate'] = 'Candidate';
$string['queueassessment'] = 'Assessment';
$string['queuealerts'] = 'Alerts';
$string['queueseverity'] = 'Severity';
$string['queuestatus'] = 'Status';
$string['queuereview'] = 'Review';

$string['backtodashboard'] = '← Back to dashboard';
$string['queuepagetitle'] = 'Priority review queue — full list';
$string['performerspagetitle'] = 'Top performers — full ranking';
$string['assessmentspagetitle'] = 'Assessment health — all assessments';
$string['detailfiltercontext'] = '{$a->timerange} · {$a->quiz}';
$string['queuerowcount'] = '{$a} sessions in queue';
$string['performersrowcount'] = '{$a} ranked entries';
$string['scoresdownloadcsv'] = 'Download CSV';
$string['exportcsv_rank'] = 'Rank';
$string['exportcsv_userid'] = 'User ID';
$string['exportcsv_quizid'] = 'Quiz ID';
$string['exportcsv_scorepct'] = 'Score (%)';
$string['assessmentrowcount'] = '{$a} assessments';
$string['assessmentslistall'] = 'All proctored assessments (not filtered by single quiz)';
$string['assessmentsdetailtableintro'] = '';
$string['tablecompletedpct'] = 'Completed %';
$string['tablepctcleared'] = 'Cleared %';
$string['tablepctwarning'] = 'Warning %';
$string['tablepcthighriskcol'] = 'High-risk %';

$string['syncnow'] = 'Sync now';
$string['indexsynctopdesc'] = 'Need the latest numbers? Sync now to refresh this dashboard with current proctoring data.';
$string['indexcachehint'] = 'Dashboard numbers load from cache (refreshed every 2 hours automatically). Use Sync now for live data.';
$string['indexcacheasof'] = 'Cached data as of {$a}';
$string['indexlivequizfilter'] = 'Live data — single assessment filter';
$string['indexsyncok'] = 'Dashboard sync completed.';
$string['taskrefreshindexcache'] = 'Refresh exam dashboard index cache';

$string['privacy:metadata:indexcache'] = 'Serialized dashboard snapshot (may include user ids in queue excerpts).';
$string['privacy:metadata:indexcachecompany'] = 'Company this snapshot belongs to';
$string['privacy:metadata:indexcachetimerange'] = 'Time window in days (e.g. 7, 30)';
$string['privacy:metadata:indexcachequiz'] = 'Quiz filter (0 = all assessments)';
$string['privacy:metadata:indexcachetimemodified'] = 'When this snapshot was built';

$string['actionpage_title_highrisk'] = 'High-risk sessions';
$string['actionpage_title_spike'] = 'Alert spike — assessments to review';
$string['actionpage_title_lowrisk'] = 'Low-risk pending sessions';
$string['actionpage_title_scores'] = 'Ranked scores — full detail';
$string['actionpage_title_activity'] = 'Suspicious activity by assessment';
$string['actionpage_intro_highrisk'] = 'Review high-risk sessions.';
$string['actionpage_intro_spike'] = 'Assessments flagged as unusual or with at least three proctor alerts in the selected period.';
$string['actionpage_intro_lowrisk'] = 'Sessions with one or two alerts and no auto-submit — candidates you may clear quickly.';
$string['actionpage_intro_scores'] = 'Every candidate best score in scope, highest first (same data as the full ranking page).';
$string['actionpage_intro_activity'] = 'All proctored assessments in scope, ordered by total alert count.';
$string['actionpage_rowcount'] = '{$a} rows';

$string['severitycritical'] = 'Critical';
$string['severityhigh'] = 'High';
$string['severitymedium'] = 'Medium';
$string['severitylow'] = 'Low';
$string['statuspending'] = 'Pending';
$string['statusalerts'] = 'Alerts';
$string['statusalertcount'] = '{$a} alerts';
$string['statusclean'] = 'Clean';
$string['statusreviewed'] = 'Reviewed';
