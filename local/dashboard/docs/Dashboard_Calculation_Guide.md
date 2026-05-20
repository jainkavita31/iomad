# Exam Dashboard Calculation Guide

This document explains, in simple terms, how dashboard numbers are calculated in `local_dashboard`.

## Filter scope used for calculations

Most metrics are calculated using the selected:

- organisation (`companyid`)
- time range (`fromtime`)
- assessment filter (`quizid`, or all if `0`)

Common base filters:

- attempt started within selected range (`qa.timestart >= :fromtime`)
- course belongs to selected organisation (`company_course`)

For proctoring metrics, only quizzes with proctoring enabled are used (`quizaccess_quizproctoring.enableproctoring = 1`).

## Top KPI cards

- **Total candidates assessed**  
  Distinct users with attempts in filter scope.

- **Assessments conducted**  
  Distinct quizzes with attempts in filter scope.

- **Assessments completed / in progress**  
  Per distinct quiz in scope:
  - in progress: at least one attempt in state `inprogress` or `overdue`
  - completed: `assessmentsconducted - assessmentsinprogress`

- **Flagged sessions**  
  Distinct attempts where at least one non-deleted proctor event exists with non-empty status.

- **Review backlog**  
  All pending sessions waiting for review:
  - `lowriskpending + mediumriskpending + highriskpending`
  - and (if review log table exists) excludes attempts already logged in `local_dashboard_proctor_review_log` (by `attemptid` when that column exists; otherwise by candidate+quiz)

- **Average score**  
  Average of `(qa.sumgrades * 100 / q.sumgrades)` for finished attempts (`qa.timefinish > 0`).

- **Pass rate (shown under average score)**  
  `passcount(score >= 50) / totalcount * 100`.

## Review pipeline cards

Pipeline uses attempt-level warning counts:

- `warningcount` = count of proctor data rows where:
  - `pd.deleted = 0`
  - `pd.status != ''`

Risk buckets (only attempts with `image_status = 'M'` and not yet reviewed):

- **Zero-risk**: `warningcount = 0`
- **High-risk pending**: `warningcount > 0` AND (`isautosubmit = 1` OR `warningcount >= 6`)
- **Medium-risk pending**: `warningcount >= 3` and not high-risk
- **Low-risk pending**: `warningcount >= 1` and not high/medium (zero-warning attempts are not low-risk)

If review log is enabled, pending buckets exclude attempts already logged as reviewed.

## Priority Review Queue widget

- Only sessions with **alert count > 0** (zero-warning sessions are excluded)
- **Severity** labels match the review pipeline buckets:
  - **High-risk**: autosubmit OR `alertcount >= 6`
  - **Medium**: `alertcount >= 3` (and not high-risk)
  - **Low**: `alertcount >= 1` (and not medium/high)
- Sorted by `alertcount DESC`, then `isautosubmit DESC`
- Shows top **10** rows on dashboard widget
- Full list is available on queue detail page
- If review log is enabled, excludes attempts already logged in `local_dashboard_proctor_review_log` (by `attemptid` when available so reattempts appear again until reviewed)

## Top Performers widget

- Ranked by best score percentage
- Shows top **10** rows on dashboard widget
- Full list is available on performers detail page

## Assessment health overview

The **Assessment health overview** panel on the main dashboard shows up to **6** assessment cards (highest **alert** count first, then **flagged**). The full list is on **View all assessments** (`assessments.php`). Data is built by `local_dashboard_fetch_assessment_stats()` in `local/dashboard/lib.php` (same helper used for the cached index payload and the assessments detail page).

### Which assessments appear

- Quizzes in courses linked to the selected organisation (`company_course`)
- Proctoring enabled on the quiz (`quizaccess_quizproctoring.enableproctoring = 1`)
- **At least one** non-preview attempt with `qa.timestart` in the selected time range; quizzes with **zero** attempts in that range are omitted from the widget and the assessments table
- On the main dashboard, the assessment dropdown filter applies (`quizid` limits to one quiz when set)
- **View all assessments** always opens `assessments.php` with **all** proctored quizzes for the company (assessment filter is not applied on that page; only organisation and time range apply)

### Per-assessment counts (time range)

All attempt-based figures use non-preview attempts with `qa.timestart >= :fromtime` for that quiz.

| Field (UI) | Meaning |
|------------|---------|
| **Candidates** | Distinct users with at least one attempt |
| **Completed %** | `finished / attempts × 100`, where **finished** = attempts with `qa.timefinish > 0` |
| **Flagged** | Distinct attempts in the **flagged union** (see below) |
| **Alerts** (detail table only) | Total proctor event rows (`quizaccess_proctor_data`) with `deleted = 0` and non-empty `status` (not deduplicated by attempt) |
| **Failed / autosubmit** (detail table) | Distinct attempts with `quizaccess_main_proctor.isautosubmit = 1`, `image_status = 'M'`, `deleted = 0` |
| **Score** (detail table) | Average `(qa.sumgrades × 100 / q.sumgrades)` over finished attempts |

**Flagged union** (used for the Flagged stat and “unusual activity”):

Distinct attempts that match **either**:

1. Autosubmit session: `qmp.isautosubmit = 1`, `qmp.image_status = 'M'`, `qmp.deleted = 0`, or  
2. At least one warning: proctor data row with `pd.deleted = 0` and `pd.status != ''`

### Integrity progress bar (three segments)

The bar is a **percentage split across all attempts** in the time range (not the same buckets as the review pipeline). Let `attempts` = total attempts (minimum 1 for math).

1. **High-risk %** (`highriskpct`): `(failed / attempts) × 100`, capped at 100  
   - `failed` = autosubmit attempts only (same as table column above)

2. **Warning %** (`orangepct`): derived from warned attempts  
   - `warnedattempts` = distinct attempts with at least one non-deleted proctor warning  
   - `warnrate = (warnedattempts / attempts) × 100`  
   - Base: `min(100 − highriskpct, warnrate − highriskpct × 0.35)`  
   - If there are warnings but no autosubmit and the segment would be tiny (`< 0.5%`), a small floor is applied: `min(18%, warnrate)`  
   - Final value is capped so the three segments do not exceed 100%

3. **Cleared %** (`clearedpct`): `100 − highriskpct − orangepct` (remainder shown as “cleared” in the footer)

Bar colours: green = cleared, orange = warning band, red = high-risk (autosubmit share).

Footer labels: **X% cleared** (left), optional warning % (centre if `orangepct > 0.5`), **X% high-risk** (right).

### Unusual activity badge

A card is marked **Unusual activity** when:

- `flaggedunion > 40`, **or**
- `highriskpct > 12`

### Main dashboard vs detail page

| | Main dashboard widget | `assessments.php` |
|--|----------------------|-------------------|
| Assessments shown | Up to **6** cards (by alert volume); filtered by dashboard assessment dropdown | All qualifying proctored quizzes for the company |
| Layout | Cards with bar + three headline stats | Sortable table (default sort: alerts DESC, then flagged DESC) |
| Extra columns | — | Attempts, alerts, failed, score, cleared / warning / high-risk % |

Assessment health is included in the index cache payload (`assessmentstats`) when viewing all assessments (`quizid = 0`).

## Data freshness

- Dashboard index data is cached for all-assessment view
- Scheduled task refreshes cache every 12 hours
- "Sync now" triggers immediate refresh for current organisation
- After a manager opens **Review** (via `proctor_review_entry.php`), the next load of the exam dashboard for that organisation still uses the cached snapshot for bulk metrics, but **recomputes and saves** review-sensitive fields only: review pipeline counts, backlog, and the priority-queue excerpt (`index_snapshot::compute_review_sensitive_slice()`), so backlog/queue update without a full cache rebuild

## Source of truth in code

Main computation class:

- `local/dashboard/classes/local/index_snapshot.php`

Related helpers:

- `local/dashboard/lib.php` (`local_dashboard_fetch_assessment_stats`, review-log pending SQL helpers)
- `local/dashboard/assessments.php` (full assessment health table)
