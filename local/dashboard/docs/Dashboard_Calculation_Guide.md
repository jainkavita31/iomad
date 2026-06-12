# Exam Dashboard — How the Numbers Are Calculated

This guide explains, in plain language, how the Exam Dashboard works and where each number comes from.

**Who is this for?** Managers and admins who use the dashboard and want to understand the figures without reading code.

---

## 1. Filters — what counts?

Almost every number on the dashboard respects the filters at the top of the page:

| Filter | What it does |
|--------|----------------|
| **Organisation** | Only courses linked to that company are included |
| **Time range** | Only attempts **started** in that window (e.g. last 7, 30, 90, or 365 days) |
| **Assessment** | **All assessments** = every proctored quiz in the org’s courses; or pick **one quiz** only |

**Proctoring rule:** For integrity, pipeline, queue, and score metrics, only quizzes with **ProctorLink / proctoring turned on** are counted.

**Department filter:** Does **not** change dashboard numbers (only organisation, time range, and assessment apply).

---

## 2. Top KPI cards (the five boxes at the top)

### Total candidates assessed
**How many different people** took at least one attempt in the filtered scope.

- Counts **people**, not attempts.
- Example: if Alice took 3 quizzes, she counts as **1** candidate.

### Assessments conducted
**How many different quizzes** had at least one attempt in scope.

### Assessments completed / in progress
Shown under “Assessments conducted”:

- **In progress** = at least one attempt still open (`in progress` or `overdue`) on that quiz.
- **Completed** = conducted minus in progress.

### Flagged sessions
**How many exam sessions** had at least one proctor alert (any non-empty warning status).

- Counts **sessions (attempts)**, not individual alert rows.
- One session with 5 alerts still counts as **1** flagged session.

### Review backlog
**How many sessions still need a human review**, split by risk (see Review pipeline below).

- Formula: low-risk pending + medium-risk pending + high-risk pending.
- If your site uses the review log, sessions already reviewed are **removed** from the backlog.

### Average score & pass rate
These appear on the **Average score** KPI (pass rate as subtitle) and again under **Score distribution**.

- **Average score** = mean of all **finished** attempt scores in scope.
- **Pass rate** = share of **finished attempts** that scored **50% or higher**.

See [Section 8 — Pass rate](#8-pass-rate-explained-simply) and the [Score distribution guide](./Score_Distribution_Calculation_Guide.md) for detail.

---

## 3. Review pipeline (the coloured count cards)

These cards group **proctored sessions** by how many warnings they have.

**Warning count** = number of proctor alert rows where the alert is active (not deleted) and has a status.

Only sessions with main proctor image status **`M`** and **not yet reviewed** (when review log is enabled) are in the pipeline.

| Card | Meaning |
|------|---------|
| **Total sessions** | All proctored sessions in the time range |
| **Zero-risk** | No warnings (`warning count = 0`) |
| **High-risk pending** | Auto-submitted **or** 6 or more warnings |
| **Medium-risk pending** | 3–5 warnings (and not high-risk) |
| **Low-risk pending** | 1–2 warnings (and not medium/high) |

**Note:** A session with **zero** warnings is **zero-risk**, not “low-risk”.

---

## 4. Priority review queue (dashboard widget)

Shows up to **10** sessions that need attention most urgently.

| Rule | Detail |
|------|--------|
| Included | Only sessions with **at least 1 alert** |
| Excluded | Zero-warning sessions |
| Severity | Same rules as pipeline: High (auto-submit or ≥6 alerts), Medium (≥3), Low (≥1) |
| Sort order | Most alerts first, then auto-submit |
| Full list | **View all queue** opens the full queue page |

When review log is enabled, already-reviewed sessions drop off the queue.

---

## 5. Top performers (dashboard widget)

Shows up to **10** rows ranked by **best score** (highest first). The full list uses the same rules on `performers.php`.

| | Score distribution | Top performers |
|--|-------------------|----------------|
| What is counted | Every **finished attempt** | One row per **person + quiz** |
| Score used | That attempt’s score | **Best** score for that person on that quiz |
| Reattempts | Each attempt counts separately | Only the highest score matters |

### Tie-breakers (same score, e.g. both 100%)

When two rows have the same **best score**, rank is decided in this order:

1. **Fewer proctor alerts** on that quiz (cleaner session wins)
2. **Fewer finished attempts** on that quiz (passed in fewer tries)
3. **Surname, then given name** (stable order if still tied)

Example: Alice and Bob both **100%** on the same quiz — Alice with **0 alerts** and **1 attempt** ranks above Bob with **2 alerts** and **2 attempts**.

**Full ranking** is on the performers detail page.

---

## 6. Score distribution (chart and three numbers)

The chart shows **how finished attempts spread across score bands**:

- 0–40%, 41–50%, 51–60%, 61–75%, 76–90%, 91–100%

**Important:** Each **attempt** is one bar in the chart — not each person, and not “best score only”.

Under the chart:

| Number | Meaning |
|--------|---------|
| **Average score** | Mean of all attempt percentages |
| **Pass rate** | % of attempts scoring **≥ 50%** |
| **Median** | Middle score when all attempts are sorted |

**Detailed examples (courses with many quizzes):** [Score_Distribution_Calculation_Guide.md](./Score_Distribution_Calculation_Guide.md)

---

## 7. Assessment health overview

Up to **6** assessment cards on the main dashboard (most alerts first). **View all assessments** shows every qualifying quiz.

### Which quizzes appear
- In the selected organisation’s courses
- Proctoring enabled
- At least one attempt **started** in the time range

### Numbers on each card

| Label | Meaning |
|-------|---------|
| **Candidates** | Different people who attempted this quiz |
| **Completed %** | Finished attempts ÷ all attempts × 100 |
| **Flagged** | Sessions that were auto-submitted **or** had at least one warning |

### Coloured progress bar (per quiz)
Splits **all attempts** in the time range into three groups:

| Colour | Meaning |
|--------|---------|
| Green (cleared) | No warnings |
| Orange (low/medium) | 1–5 warnings, not high-risk |
| Red (high-risk) | Auto-submitted or 6+ warnings |

The three segments always add up to **100%** of attempts on that quiz.

---

## 8. Pass rate — explained simply

**Pass rate does not use Moodle’s quiz “pass grade” setting.** The dashboard always treats **50% or higher** as a pass.

### Formula

```text
Pass rate = (passing attempts ÷ total finished attempts) × 100
```

- **Passing attempt** = score **≥ 50%**
- **Total** = all finished, non-preview, proctored attempts in your filters

### How each score is calculated

```text
Score % = (attempt grade ÷ quiz maximum grade) × 100
```

### Example → Pass rate 60.0%

Five finished attempts in **Last 30 days**, **All assessments**:

| Person | Score | Pass? (≥ 50%) |
|--------|-------|---------------|
| Alice | 72% | Yes |
| Bob | 48% | No |
| Cara | 55% | Yes |
| Dan | 30% | No |
| Eve | 81% | Yes |

- Passing attempts = **3**
- Total attempts = **5**
- Pass rate = 3 ÷ 5 × 100 = **60.0%**

### What pass rate is **not**

| Common assumption | Actual behaviour |
|-------------------|------------------|
| “% of people who passed” | **% of attempts** that passed |
| Uses quiz pass mark from Moodle | Fixed **50%** threshold |
| Same as “cleared” in pipeline | Pipeline uses **warnings**, not quiz scores |

### Score exactly 50%

A score of **50.0%** counts as a **pass** for pass rate, even though the chart puts it in the **41–50** band.

---

## 9. When does data update? (cache and cron)

The main dashboard page (**All assessments** view) loads from a **saved snapshot** (cache) so it opens quickly.

| How data refreshes | What happens |
|--------------------|--------------|
| **Every 2 hours (automatic)** | Moodle cron runs the task *Refresh exam dashboard index cache* and rebuilds snapshots for all organisations |
| **Sync now** (button on dashboard) | Rebuilds the snapshot immediately for **your current organisation** |
| **Refresh the page (F5)** | Shows whatever is **already in the cache** — it does not recalculate everything live |
| **After opening Review** | Pipeline, backlog, and queue excerpt can update on the **next page load** without waiting for cron |
| **Single quiz filter** | Always calculated **live** (not from the 2-hour cache) |

### Sub-pages always live
These pages query the database directly each time:

- Priority queue (full list)
- Top performers (full ranking)
- Assessment health (full table)

So they may show slightly newer numbers than the main dashboard KPIs between cron runs.

### If cron has never run
Use **Sync now** once, or run Moodle cron manually. Until the cache is built, the dashboard may compute live on first visit or show older data.

---

## 10. Quick reference — attempts vs people

| Metric | Counts |
|--------|--------|
| Total candidates | **People** |
| Assessments conducted | **Quizzes** |
| Flagged sessions | **Attempts / sessions** |
| Score distribution & pass rate | **Attempts** |
| Top performers | **Best score per person per quiz** |
| Review pipeline & queue | **Sessions (attempts)** |

---

## 11. For developers — where the code lives

| Area | File |
|------|------|
| Main calculations & cache | `local/dashboard/classes/local/index_snapshot.php` |
| Helpers (assessment stats, review log) | `local/dashboard/lib.php` |
| Scheduled cache refresh | `local/dashboard/classes/task/refresh_index_cache.php` |
| Dashboard page | `local/dashboard/index.php` |
| On-demand sync | `local/dashboard/sync.php` |

---

## Related document

- [Score_Distribution_Calculation_Guide.md](./Score_Distribution_Calculation_Guide.md) — more examples for pass rate, buckets, and courses with many quizzes
