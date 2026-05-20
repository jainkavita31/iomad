# Score distribution — detailed calculation guide

This document explains how the **Score distribution** panel on the Exam Dashboard (`local_dashboard`) is built: what is counted, how percentage scores are computed, how attempts are placed into buckets, and how that behaves when a **course has many proctored quizzes**.

**Source code:** `local/dashboard/classes/local/index_snapshot.php` (SQL and median), `local/dashboard/index.php` (chart display).

---

## 1. What the widget shows

The panel has two parts:

| Part | Meaning |
|------|---------|
| **Six bar columns** | How many **finished attempts** fall into each score band (0–40%, 41–50%, …, 91–100%). |
| **Three summary numbers** | **Average score**, **Pass rate**, and **Median** over the same set of attempts. |

Important: the distribution counts **attempts**, not **candidates** and not **“best score per person per quiz”**.

---

## 2. Which attempts are included

An attempt is included only if **all** of the following are true:

| Rule | Field / condition |
|------|-------------------|
| Belongs to the selected organisation | Quiz’s course is in `{company_course}` for `companyid` |
| Proctoring enabled | `{quizaccess_quizproctoring}.enableproctoring = 1` for that quiz |
| Not a preview | `qa.preview = 0` |
| Finished | `qa.timefinish > 0` (submitted / completed) |
| Started in the time window | `qa.timestart >= fromtime` where `fromtime = now − (timerange days)` |
| Assessment filter (optional) | If dashboard **Assessment** is one quiz: `q.id = quizid`. If **All assessments**: every proctored quiz in the company’s courses |

**Not included:**

- In-progress or overdue attempts (`timefinish = 0`)
- Preview attempts
- Quizzes without ProctorLink enabled
- Courses not linked to the selected company
- Attempts started **before** the selected time range (even if finished later)

**Department filter:** The dashboard department filter does **not** change score distribution (only organisation, time range, and assessment apply).

---

## 3. Score percentage formula

For each included attempt:

```text
scorepct = (qa.sumgrades × 100) / q.sumgrades
```

- `qa.sumgrades` — raw grade on the attempt (Moodle quiz attempt)
- `q.sumgrades` — quiz maximum grade (denominator for the quiz)
- If `q.sumgrades` is 0, SQL uses `NULLIF(q.sumgrades, 0)` so that attempt does not produce a valid percentage (excluded from buckets via the inner query logic)

This matches the **Average score** KPI and assessment **Score** columns elsewhere on the dashboard.

**Pass** for pass rate: `scorepct >= 50`.

---

## 4. Score buckets (bands)

Each attempt is placed in **exactly one** band using these rules (boundaries are inclusive on the upper end of each range except 91–100):

| UI label | Bucket key | SQL condition on `scorepct` |
|----------|------------|------------------------------|
| 0–40 | `c0_40` | `>= 0` AND `<= 40` |
| 41–50 | `c41_50` | `> 40` AND `<= 50` |
| 51–60 | `c51_60` | `> 50` AND `<= 60` |
| 61–75 | `c61_75` | `> 60` AND `<= 75` |
| 76–90 | `c76_90` | `> 75` AND `<= 90` |
| 91–100 | `c91_100` | `> 90` (no upper cap in SQL) |

Examples:

- `40.0%` → **0–40**
- `40.1%` → **41–50**
- `50.0%` → **41–50**
- `50.1%` → **51–60**
- `90.0%` → **76–90**
- `90.1%` → **91–100**

`totalcount` = sum of all six bucket counts = number of finished attempts in scope.

---

## 5. Summary metrics (below the chart)

| Metric | Calculation |
|--------|-------------|
| **Average score** | `AVG(scorepct)` over all included attempts |
| **Pass rate** | `(attempts with scorepct >= 50) / totalcount × 100` |
| **Median** | All `scorepct` values loaded, sorted ascending; middle value (or average of two middles if even count) |

Median uses the **same attempt set** as the buckets (separate query, same filters).

---

## 6. How the chart labels work (UI)

For each bucket:

- **Percentage under the bar** = `(bucket count / totalcount) × 100`  
  Example: 12 attempts in 61–75 out of 40 total → **30.0%** under that column.

- **Bar height** = relative to the **largest** bucket count, not relative to `totalcount`.  
  The tallest bar is 100% height; others scale proportionally. So a bucket with 5 attempts can look “tall” if every other bucket is smaller.

---

## 7. Courses with many quizzes — core behaviour

### 7.1 Scope: company → courses → all proctored quizzes

The organisation is linked to **courses** via `{company_course}`. Every **quiz** in those courses with `enableproctoring = 1` can contribute attempts.

If one course **“Safety Training”** has three proctored quizzes:

- Quiz A — *Module 1 check*
- Quiz B — *Module 2 check*
- Quiz C — *Final exam*

…and the dashboard filter is **All assessments** and **Last 30 days**, then **every finished attempt** on A, B, or C (in that window) is one row in the distribution.

There is **no** grouping by course and **no** “only count the final exam” rule.

### 7.2 Worked example (one course, three quizzes)

**Setup**

- Company: *Acme Corp*
- Course: *Safety Training* (linked to Acme)
- Time range: Last 30 days
- Assessment filter: **All assessments**

**Attempts in range (all finished)**

| # | Candidate | Quiz | scorepct |
|---|-----------|------|----------|
| 1 | Alice | Module 1 (A) | 88% |
| 2 | Alice | Module 2 (B) | 42% |
| 3 | Alice | Final (C) | 76% |
| 4 | Bob | Module 1 (A) | 35% |
| 5 | Bob | Module 1 (A) | 72% (reattempt) |
| 6 | Bob | Final (C) | 55% |
| 7 | Cara | Module 2 (B) | 91% |

**Bucket counts**

| Band | Attempts | Which rows |
|------|----------|------------|
| 0–40 | 1 | Bob 35% (row 4) |
| 41–50 | 1 | Alice 42% (row 2) |
| 51–60 | 1 | Bob 55% (row 6) |
| 61–75 | 0 | — |
| 76–90 | 2 | Alice 88%, Alice 76% (rows 1, 3) |
| 91–100 | 1 | Cara 91% (row 7) |
| **Total** | **7** | |

Note: Bob’s **two** Module 1 attempts both count (rows 4 and 5). Alice appears in **three** bands because she has **three separate attempts** on three quizzes, not because she is counted three times on one quiz.

**Summary numbers (this example)**

- Average ≈ `(88+42+76+35+72+55+91) / 7` ≈ **65.6%**
- Pass rate: 6 of 7 ≥ 50% → ≈ **85.7%**
- Median: sorted scores `35, 42, 55, 72, 76, 88, 91` → middle = **55%**

**Chart labels**

- 0–40: 1/7 ≈ **14.3%**
- 41–50: **14.3%**
- 51–60: **14.3%**
- 61–75: **0%**
- 76–90: 2/7 ≈ **28.6%**
- 91–100: **14.3%**

### 7.3 Same course, filter to one quiz

If the manager selects **Assessment = Final exam (C)** only:

- Only rows **3** and **6** remain → `totalcount = 2`
- Bands: 76–90 (76%), 51–60 (55%)
- Average = 65.5%, pass rate = 100%, median = 65.5%

Short quizzes and final exams are **not** mixed when a single assessment is selected.

### 7.4 Multiple courses, many quizzes each

If Acme has:

- *Safety Training* — 3 proctored quizzes → 7 attempts (example above)
- *Onboarding* — 2 proctored quizzes → 4 attempts in range

With **All assessments**, `totalcount = 11`. All eleven attempts share one distribution. A course with more quizzes or more candidates usually contributes **more attempts** and therefore **more weight** in the chart, simply because there are more finished attempts—not because the formula weights courses differently.

### 7.5 What does *not* happen

| Expectation (incorrect) | Actual behaviour |
|-------------------------|------------------|
| One bar per candidate | One bar **per attempt** |
| Best score only per user per quiz | **Every** finished attempt in range |
| One row per course | All quizzes in all linked courses pool together |
| Only highest quiz in a course | All proctored quizzes count equally |

---

## 8. Comparison with “Top performers”

| | Score distribution | Top performers (widget / ranked list) |
|--|-------------------|--------------------------------------|
| Unit | Each **attempt** | One row per **user + quiz** |
| Score used | That attempt’s `scorepct` | **Best** `scorepct` among attempts in range for that user+quiz |
| Reattempts | Each finished reattempt adds to distribution | Only the max score affects ranking |
| Multiple quizzes | All attempts from all quizzes pool into six buckets | One ranked row per quiz taken (e.g. Alice appears up to three times) |

So a candidate who fails Module 1 (30%) and passes Final (80%) contributes **two** distribution buckets but may rank highly on the final quiz row in Top performers.

---

## 9. Edge cases

| Situation | Result |
|-----------|--------|
| No finished attempts in scope | All bucket counts 0; `totalcount = 0`; UI uses `max(1, totalcount)` only for bar % math so labels do not divide by zero; averages show 0 |
| Only in-progress attempts | Excluded — distribution empty |
| `sumgrades` null / quiz max 0 | Problematic row; typically excluded or yields null `scorepct` |
| Score &gt; 100% (extra credit / rounding) | Lands in **91–100** (`> 90`) |
| Same user, same quiz, 3 finished attempts in range | **3** counts in distribution (possibly different buckets) |
| Quiz in course not linked to company | Excluded even if users are company members |
| Cached dashboard | `scorestats` and median are stored in index cache (`local_dashboard_index_cache`); **Sync now** or scheduled task rebuilds them |

---

## 10. SQL reference (simplified)

Inner set — one row per attempt:

```sql
SELECT (qa.sumgrades * 100.0) / NULLIF(q.sumgrades, 0) AS scorepct
  FROM mdl_quiz_attempts qa
  JOIN mdl_quiz q ON q.id = qa.quiz
  JOIN mdl_company_course cc ON cc.courseid = q.course
  JOIN mdl_quizaccess_quizproctoring qp ON qp.quizid = q.id
 WHERE cc.companyid = :companyid
   AND qp.enableproctoring = 1
   AND qa.preview = 0
   AND qa.timefinish > 0
   AND qa.timestart >= :fromtime
   -- optional: AND q.id = :quizid
```

Outer aggregation — bucket counts, average, pass count, total:

```sql
SELECT
  SUM(CASE WHEN scorepct >= 0 AND scorepct <= 40 THEN 1 ELSE 0 END) AS c0_40,
  SUM(CASE WHEN scorepct > 40 AND scorepct <= 50 THEN 1 ELSE 0 END) AS c41_50,
  -- ... other buckets ...
  AVG(scorepct) AS avgscore,
  SUM(CASE WHEN scorepct >= 50 THEN 1 ELSE 0 END) AS passcount,
  COUNT(*) AS totalcount
FROM ( ... inner query ... ) scored
```

---

## 11. Quick checklist for managers

When interpreting the chart for a course with many quizzes:

1. **All assessments** — distribution reflects **volume of attempts** across every proctored quiz in every company course (module checks + finals together).
2. **One assessment** — distribution reflects only that quiz (useful for “how hard was the final?”).
3. **Reattempts** inflate `totalcount` and can spread one candidate across multiple buckets.
4. **Pass rate** here is “% of **attempts** scoring ≥ 50%”, not “% of candidates who passed the course”.
5. Compare with **Top performers** when you care about best achievement per person per quiz, not attempt-level spread.

---

## Related documents

- [Dashboard_Calculation_Guide.md](./Dashboard_Calculation_Guide.md) — overview of all dashboard metrics
- Code: `local/dashboard/classes/local/index_snapshot.php` (`compute_data`, `$scorestats` query)
