<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Pre-computed exam dashboard index payload (cache + cron).
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Build and persist dashboard aggregates for fast index loads (quizid = all only).
 */
final class index_snapshot {

    /** @var string[] Payload keys restored on index when reading cache. */
    public const PAYLOAD_KEYS = [
        'totalcandidates',
        'assessmentsconducted',
        'assessmentscompleted',
        'assessmentsinprogress',
        'totalsessions',
        'totalalerts',
        'flaggedsessions',
        'reviewbacklog',
        'averagescore',
        'statuscounts',
        'highriskpending',
        'mediumriskpending',
        'lowriskpending',
        'zerorisk',
        'assessmentstats',
        'scorestats',
        'medianscore',
        'passrate',
        'topperformers',
        'priorityqueue',
    ];

    /**
     * Payload keys that depend on the proctor review log (pending vs reviewed).
     *
     * These are recomputed on the next dashboard load after a review without rebuilding the full cache.
     */
    public const REVIEW_SENSITIVE_KEYS = [
        'highriskpending',
        'mediumriskpending',
        'lowriskpending',
        'zerorisk',
        'reviewbacklog',
        'priorityqueue',
    ];

    /**
     * Review pipeline counts + priority queue excerpt (same SQL as full index compute).
     *
     * @param \stdClass $r Bootstrap (companyid, baseparams, quizsql, fromtime).
     * @return \stdClass Object with REVIEW_SENSITIVE_KEYS properties.
     */
    public static function compute_review_sensitive_slice(\stdClass $r): \stdClass {
        global $DB;

        $baseparams = $r->baseparams;
        $quizsql = $r->quizsql;
        $ldpend = \local_dashboard_review_log_pending_only_sql_parts();

        $attemptriskrows = $DB->get_records_sql(
            "SELECT qmp.attemptid,
                    MAX(qmp.isautosubmit) AS isautosubmit,
                    SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) AS warningcount
               FROM {quizaccess_main_proctor} qmp
               JOIN {quiz_attempts} qa ON qa.id = qmp.attemptid
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
                $quizsql
           GROUP BY qmp.attemptid",
            $baseparams
        );

        $highriskpending = 0;
        $mediumriskpending = 0;
        $lowriskpending = 0;
        $zerorisk = 0;
        foreach ($attemptriskrows as $attemptrow) {
            $warningcount = (int) $attemptrow->warningcount;
            $isautosubmit = (int) $attemptrow->isautosubmit;
            switch (\local_dashboard_attempt_risk_bucket($warningcount, $isautosubmit)) {
                case 'high':
                    $highriskpending++;
                    break;
                case 'medium':
                    $mediumriskpending++;
                    break;
                case 'low':
                    $lowriskpending++;
                    break;
                default:
                    $zerorisk++;
            }
        }
        $reviewbacklog = $lowriskpending + $mediumriskpending + $highriskpending;

        $queueparams = $baseparams;
        $priorityqueue = $DB->get_records_sql(
            "SELECT qmp.attemptid,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    q.id AS quizid,
                    q.name AS quizname,
                    SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) AS alertcount,
                    MAX(qmp.isautosubmit) AS isautosubmit
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
                $quizsql
           GROUP BY qmp.attemptid, u.id, u.firstname, u.lastname, q.id, q.name
          HAVING SUM(CASE WHEN pd.deleted = 0 AND pd.status != '' THEN 1 ELSE 0 END) > 0
           ORDER BY alertcount DESC, isautosubmit DESC",
            $queueparams,
            0,
            10
        );

        $out = new \stdClass();
        $out->highriskpending = $highriskpending;
        $out->mediumriskpending = $mediumriskpending;
        $out->lowriskpending = $lowriskpending;
        $out->zerorisk = $zerorisk;
        $out->reviewbacklog = $reviewbacklog;
        $out->priorityqueue = $priorityqueue;

        return $out;
    }

    /**
     * Patch a cached index payload with live review-sensitive metrics (then caller may store).
     *
     * @param \stdClass $payload Mutated in place.
     * @param \stdClass $r Same bootstrap as {@see self::compute_data()}.
     */
    public static function apply_review_metrics_to_payload(\stdClass $payload, \stdClass $r): void {
        $slice = self::compute_review_sensitive_slice($r);
        foreach (self::REVIEW_SENSITIVE_KEYS as $key) {
            $payload->$key = $slice->$key;
        }
    }

    /**
     * Build the same data structure that index.php uses for KPIs, pipeline, queue, etc.
     *
     * @param \stdClass $r Bootstrap object (needs companyid, baseparams, quizsql, fromtime consistent).
     * @return \stdClass
     */
    public static function compute_data(\stdClass $r): \stdClass {
        global $DB;

        $companyid = (int) $r->companyid;
        $baseparams = $r->baseparams;
        $quizsql = $r->quizsql;

        $attemptscopefrom = " FROM {quiz_attempts} qa
                 JOIN {quiz} q ON q.id = qa.quiz
                 JOIN {company_course} cc ON cc.courseid = q.course ";
        $attemptscopewhere = " WHERE cc.companyid = :companyid
                    AND qa.preview = 0
                    AND qa.timestart >= :fromtime
                    $quizsql ";

        $totalcandidates = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.userid) " . $attemptscopefrom . $attemptscopewhere,
            $baseparams
        );
        $assessmentsconducted = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT q.id) " . $attemptscopefrom . $attemptscopewhere,
            $baseparams
        );
        $assessmentprogressrows = $DB->get_records_sql(
            "SELECT q.id AS quizid,
                    MAX(CASE WHEN qa.state IN ('inprogress', 'overdue') THEN 1 ELSE 0 END) AS hasinprogress
               FROM {quiz_attempts} qa
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
              WHERE cc.companyid = :companyid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                $quizsql
           GROUP BY q.id",
            $baseparams
        );
        $assessmentsinprogress = 0;
        foreach ($assessmentprogressrows as $assessmentprogressrow) {
            if (!empty($assessmentprogressrow->hasinprogress)) {
                $assessmentsinprogress++;
            }
        }
        $assessmentscompleted = max($assessmentsconducted - $assessmentsinprogress, 0);
        $totalsessions = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.id)
               FROM {quiz_attempts} qa
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
              WHERE cc.companyid = :companyid
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                $quizsql",
            $baseparams
        );

        $totalalerts = (int) $DB->count_records_sql(
            "SELECT COUNT(pd.id)
               FROM {quizaccess_proctor_data} pd
               JOIN {quiz_attempts} qa ON qa.id = pd.attemptid
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
               JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
              WHERE cc.companyid = :companyid
                AND qp.enableproctoring = 1
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                AND pd.deleted = 0
                AND pd.status != ''
                $quizsql",
            $baseparams
        );

        $flaggedsessions = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.id)
               FROM {quiz_attempts} qa
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
               JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
              WHERE cc.companyid = :companyid
                AND qp.enableproctoring = 1
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                $quizsql
                AND EXISTS (
                    SELECT 1
                      FROM {quizaccess_proctor_data} pd
                     WHERE pd.attemptid = qa.id
                       AND pd.quizid = q.id
                       AND pd.deleted = 0
                       AND pd.status != ''
                )",
            $baseparams
        );

        $avgscorerecord = $DB->get_record_sql(
            "SELECT AVG((qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0)) AS avgscore
               FROM {quiz_attempts} qa
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
               JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
              WHERE cc.companyid = :companyid
                AND qp.enableproctoring = 1
                AND qa.preview = 0
                AND qa.timefinish > 0
                AND qa.timestart >= :fromtime
                $quizsql",
            $baseparams
        );
        $averagescore = !empty($avgscorerecord->avgscore) ? (float) $avgscorerecord->avgscore : 0.0;

        $statuscounts = [
            'tabswitch' => 0,
            'facemismatch' => 0,
            'nofacedetected' => 0,
            'eyesnotfocused' => 0,
            'multiplepeople' => 0,
            'objectsdetected' => 0,
            'otheralerts' => 0,
        ];
        $statusrecords = $DB->get_records_sql(
            "SELECT pd.status, COUNT(pd.id) AS cnt
               FROM {quizaccess_proctor_data} pd
               JOIN {quiz_attempts} qa ON qa.id = pd.attemptid
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
               JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
              WHERE cc.companyid = :companyid
                AND qp.enableproctoring = 1
                AND qa.preview = 0
                AND qa.timestart >= :fromtime
                AND pd.deleted = 0
                AND pd.status != ''
                $quizsql
           GROUP BY pd.status",
            $baseparams
        );
        foreach ($statusrecords as $statusrecord) {
            $status = strtolower((string) $statusrecord->status);
            $count = (int) $statusrecord->cnt;
            if (in_array($status, ['minimizedetected', 'appchange', 'tabswitch'], true)) {
                $statuscounts['tabswitch'] += $count;
            } else if (in_array($status, ['nomatchfound', 'facemismatch', 'profilemismatch', 'facesnotmatched'], true)) {
                $statuscounts['facemismatch'] += $count;
            } else if ($status === 'nofacedetected') {
                $statuscounts['nofacedetected'] += $count;
            } else if ($status === 'eyesnotopened') {
                $statuscounts['eyesnotfocused'] += $count;
            } else if ($status === 'multifacesdetected') {
                $statuscounts['multiplepeople'] += $count;
            } else if (in_array($status, ['objectsdetected', 'objectdetected'], true)) {
                $statuscounts['objectsdetected'] += $count;
            } else {
                $statuscounts['otheralerts'] += $count;
            }
        }

        $reviewsensitive = self::compute_review_sensitive_slice($r);
        $highriskpending = $reviewsensitive->highriskpending;
        $mediumriskpending = $reviewsensitive->mediumriskpending;
        $lowriskpending = $reviewsensitive->lowriskpending;
        $zerorisk = $reviewsensitive->zerorisk;
        $reviewbacklog = $reviewsensitive->reviewbacklog;
        $priorityqueue = $reviewsensitive->priorityqueue;

        $assessmentstats = \local_dashboard_fetch_assessment_stats($companyid, (int) $r->fromtime, $quizsql, $baseparams);

        $scorestats = $DB->get_record_sql(
            "SELECT
                    SUM(CASE WHEN scorepct >= 0 AND scorepct <= 40 THEN 1 ELSE 0 END) AS c0_40,
                    SUM(CASE WHEN scorepct > 40 AND scorepct <= 50 THEN 1 ELSE 0 END) AS c41_50,
                    SUM(CASE WHEN scorepct > 50 AND scorepct <= 60 THEN 1 ELSE 0 END) AS c51_60,
                    SUM(CASE WHEN scorepct > 60 AND scorepct <= 75 THEN 1 ELSE 0 END) AS c61_75,
                    SUM(CASE WHEN scorepct > 75 AND scorepct <= 90 THEN 1 ELSE 0 END) AS c76_90,
                    SUM(CASE WHEN scorepct > 90 THEN 1 ELSE 0 END) AS c91_100,
                    AVG(scorepct) AS avgscore,
                    SUM(CASE WHEN scorepct >= 50 THEN 1 ELSE 0 END) AS passcount,
                    COUNT(*) AS totalcount
               FROM (
                    SELECT (qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0) AS scorepct
                      FROM {quiz_attempts} qa
                      JOIN {quiz} q ON q.id = qa.quiz
                      JOIN {company_course} cc ON cc.courseid = q.course
                      JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
                     WHERE cc.companyid = :companyid
                       AND qp.enableproctoring = 1
                       AND qa.preview = 0
                       AND qa.timefinish > 0
                       AND qa.timestart >= :fromtime
                       $quizsql
               ) scored",
            $baseparams
        );

        $scorevalues = $DB->get_fieldset_sql(
            "SELECT (qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0) AS scorepct
               FROM {quiz_attempts} qa
               JOIN {quiz} q ON q.id = qa.quiz
               JOIN {company_course} cc ON cc.courseid = q.course
               JOIN {quizaccess_quizproctoring} qp ON qp.quizid = q.id
              WHERE cc.companyid = :companyid
                AND qp.enableproctoring = 1
                AND qa.preview = 0
                AND qa.timefinish > 0
                AND qa.timestart >= :fromtime
                $quizsql
           ORDER BY scorepct",
            $baseparams
        );
        $medianscore = 0.0;
        if (!empty($scorevalues)) {
            $count = count($scorevalues);
            $mid = intdiv($count, 2);
            if ($count % 2) {
                $medianscore = (float) $scorevalues[$mid];
            } else {
                $medianscore = ((float) $scorevalues[$mid - 1] + (float) $scorevalues[$mid]) / 2;
            }
        }
        if (!$scorestats) {
            $scorestats = (object) [
                'c0_40' => 0,
                'c41_50' => 0,
                'c51_60' => 0,
                'c61_75' => 0,
                'c76_90' => 0,
                'c91_100' => 0,
                'avgscore' => null,
                'passcount' => 0,
                'totalcount' => 0,
            ];
        }

        $passrate = !empty($scorestats->totalcount) ? (((float) $scorestats->passcount / (float) $scorestats->totalcount) * 100.0) : 0.0;

        $topperformers = \local_dashboard_fetch_ranked_scores_rows($quizsql, $baseparams, 0, 10);

        $out = new \stdClass();
        foreach (self::PAYLOAD_KEYS as $key) {
            $out->$key = $$key;
        }
        return $out;
    }

    /**
     * @param int $companyid
     * @param int $timerange Days (7|30|90|365).
     * @param int $quizid Cached rows use 0 = all assessments.
     * @return array{payload: \stdClass, timemodified: int}|null
     */
    public static function load(int $companyid, int $timerange, int $quizid = 0): ?array {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_index_cache')) {
            return null;
        }
        $rec = $DB->get_record('local_dashboard_index_cache', [
            'companyid' => $companyid,
            'timerange' => $timerange,
            'quizid' => $quizid,
        ], '*', IGNORE_MISSING);
        if (!$rec || $rec->payload === null || $rec->payload === '') {
            return null;
        }
        $data = @unserialize(base64_decode($rec->payload, true));
        if (!$data instanceof \stdClass) {
            return null;
        }
        return [
            'payload' => $data,
            'timemodified' => (int) $rec->timemodified,
        ];
    }

    /**
     * @param int $companyid
     * @param int $timerange
     * @param int $quizid
     * @param \stdClass $data From {@see self::compute_data()}.
     */
    public static function store(int $companyid, int $timerange, int $quizid, \stdClass $data): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_index_cache')) {
            return;
        }
        $payload = base64_encode(serialize($data));
        $record = (object) [
            'companyid' => $companyid,
            'timerange' => $timerange,
            'quizid' => $quizid,
            'timemodified' => time(),
            'payload' => $payload,
        ];
        $existing = $DB->get_record('local_dashboard_index_cache', [
            'companyid' => $companyid,
            'timerange' => $timerange,
            'quizid' => $quizid,
        ], 'id', IGNORE_MISSING);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_dashboard_index_cache', $record);
        } else {
            $DB->insert_record('local_dashboard_index_cache', $record);
        }
    }

    /**
     * Refresh cache rows for one company (all standard timeranges, all assessments).
     *
     * @param int $companyid
     */
    public static function refresh_company(int $companyid): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_index_cache')) {
            return;
        }
        $timeranges = [7, 30, 90, 365];
        foreach ($timeranges as $tr) {
            $fromtime = time() - ($tr * DAYSECS);
            $fake = new \stdClass();
            $fake->companyid = $companyid;
            $fake->fromtime = $fromtime;
            $fake->baseparams = [
                'companyid' => $companyid,
                'fromtime' => $fromtime,
            ];
            $fake->quizsql = '';
            $fake->quizid = 0;
            $data = self::compute_data($fake);
            self::store($companyid, $tr, 0, $data);
        }
    }

    /** Cron: refresh every company's cached index payloads. */
    public static function refresh_all_companies(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_dashboard_index_cache')) {
            return;
        }
        $ids = $DB->get_fieldset_sql("SELECT id FROM {company} ORDER BY id");
        foreach ($ids as $cid) {
            self::refresh_company((int) $cid);
        }
    }
}
