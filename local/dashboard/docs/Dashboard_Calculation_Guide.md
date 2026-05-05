# Exam Dashboard Calculation Guide

This document explains, in simple terms, how dashboard numbers are calculated in `local_dashboard`.

## Filter scope used for calculations

Most metrics are calculated using the selected:

- organisation (`companyid`)
- time range (`fromtime`)
- assessment filter (`quizid`, or all if `0`)

Common base filters:

- attempt is not preview (`qa.preview = 0`)
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
  Distinct proctor attempts with:
  - `image_status = 'M'`
  - `isautosubmit = 1`
  - and (if review log table exists) not yet reviewed in `local_dashboard_proctor_review_log`

- **Average score**  
  Average of `(qa.sumgrades * 100 / q.sumgrades)` for finished attempts (`qa.timefinish > 0`).

- **Pass rate (shown under average score)**  
  `passcount(score >= 50) / totalcount * 100`.

## Review pipeline cards

Pipeline uses attempt-level warning counts:

- `warningcount` = count of proctor data rows where:
  - `pd.deleted = 0`
  - `pd.status != ''`

Risk buckets:

- **High-risk pending**: `isautosubmit = 1` OR `warningcount >= 6`
- **Medium-risk pending**: `warningcount >= 3` and not high-risk
- **Low-risk pending**: `warningcount >= 1` and not high/medium
- **Auto-cleared**: `totalsessions - (low + medium + high)` (minimum 0)

If review log is enabled, pending buckets exclude already reviewed candidate+quiz pairs.

## Priority Review Queue widget

- Sorted by `alertcount DESC`, then `isautosubmit DESC`
- Shows top **10** rows on dashboard widget
- Full list is available on queue detail page

## Top Performers widget

- Ranked by best score percentage
- Shows top **10** rows on dashboard widget
- Full list is available on performers detail page

## Data freshness

- Dashboard index data is cached for all-assessment view
- Scheduled task refreshes cache every 12 hours
- "Sync now" triggers immediate refresh for current organisation

## Source of truth in code

Main computation class:

- `local/dashboard/classes/local/index_snapshot.php`

Related helpers:

- `local/dashboard/lib.php` (review-log pending SQL helpers)
