# Score Distribution & Pass Rate — Easy Guide

This document explains the **Score distribution** panel and **Pass rate** on the Exam Dashboard in simple terms.

**Source code:** `local/dashboard/classes/local/index_snapshot.php`

---

## What you see on screen

| Part | What it means |
|------|----------------|
| **Six coloured bars** | How many **finished exam attempts** fell into each score range |
| **Average score** | Typical score across those attempts |
| **Pass rate** | What **percentage of attempts** scored 50% or higher |
| **Median** | The middle score when all attempts are lined up lowest to highest |

---

## Pass rate in one sentence

> **Pass rate = (number of attempts with score ≥ 50%) ÷ (total finished attempts) × 100**

It is **not** “how many candidates passed the course.”

### Example: Pass rate 60.0%

| Attempt | Score | Pass? |
|---------|-------|-------|
| 1 | 72% | Yes |
| 2 | 48% | No |
| 3 | 55% | Yes |
| 4 | 30% | No |
| 5 | 81% | Yes |

**3 passes out of 5 attempts → 60.0% pass rate**

The dashboard does **not** read Moodle’s quiz pass grade — it always uses **50%** as the pass line.

---

## Which attempts are included?

An attempt counts only if **all** of these are true:

1. Quiz is in a course linked to the **selected organisation**
2. **Proctoring is enabled** on that quiz
3. Attempt is **finished** (submitted)
4. Attempt is **not a preview**
5. Attempt **started** within the selected **time range**
6. Matches the **assessment** filter (one quiz or all quizzes)

**Not included:** in-progress attempts, preview attempts, non-proctored quizzes, attempts started before the time window.

---

## How the score percentage is worked out

For each attempt:

```text
Score % = (grade on attempt ÷ maximum grade for quiz) × 100
```

Same formula as **Average score** elsewhere on the dashboard.

---

## Score bands (the six bars)

Each attempt goes into **one** band only:

| Bar label | Score range |
|-----------|-------------|
| 0–40 | 0% up to and including 40% |
| 41–50 | above 40% up to and including 50% |
| 51–60 | above 50% up to and including 60% |
| 61–75 | above 60% up to and including 75% |
| 76–90 | above 75% up to and including 90% |
| 91–100 | above 90% |

**Tip:** 50.0% sits in the **41–50** bar on the chart but still **counts as a pass** for pass rate.

---

## Average, pass rate, and median

| Metric | Plain English |
|--------|----------------|
| **Average** | Add all attempt scores, divide by how many attempts |
| **Pass rate** | Count attempts ≥ 50%, divide by total attempts, × 100 |
| **Median** | Sort all scores; pick the middle one (or average the two middle ones if even count) |

All three use the **same set of finished attempts**.

---

## Course with many quizzes

If one course has **Module 1**, **Module 2**, and **Final exam** (all proctored), and you choose **All assessments**:

- **Every finished attempt** on every quiz counts
- Alice with 3 quizzes = **3 separate attempts** in the chart (maybe in 3 different bars)
- Bob’s **reattempt** on Module 1 counts as **another** attempt

To see only the final exam, set **Assessment** to that quiz.

---

## Score distribution vs Top performers

| | Score distribution | Top performers |
|--|-------------------|----------------|
| Counts | Every **attempt** | One row per **person + quiz** |
| Reattempts | All count | Only **best** score counts |
| Good for | “How did attempts spread?” | “Who scored highest?” |

---

## Chart labels — how to read them

- **% under each bar** = that band’s attempts ÷ **total attempts** × 100  
  Example: 2 of 10 attempts in 76–90 → **20.0%** under that bar

- **Bar height** = relative to the **tallest** bar, not relative to 100% of all attempts  
  A small band can look tall if every other band is smaller

---

## When numbers refresh

Score distribution on the **main dashboard** (with **All assessments**) comes from the **cached snapshot**:

- Refreshed every **2 hours** by cron, or immediately via **Sync now**
- Refreshing the browser page shows the **latest cache**, not a live recalculation

With a **single quiz** selected, scores are always calculated **live**.

---

## Worked example — three quizzes, seven attempts

**Filters:** All assessments, Last 30 days

| # | Person | Quiz | Score |
|---|--------|------|-------|
| 1 | Alice | Module 1 | 88% |
| 2 | Alice | Module 2 | 42% |
| 3 | Alice | Final | 76% |
| 4 | Bob | Module 1 | 35% |
| 5 | Bob | Module 1 (reattempt) | 72% |
| 6 | Bob | Final | 55% |
| 7 | Cara | Module 2 | 91% |

**Bands:** 0–40: 1 | 41–50: 1 | 51–60: 1 | 61–75: 0 | 76–90: 2 | 91–100: 1 → **7 total**

**Pass rate:** 6 of 7 ≥ 50% → **≈ 85.7%**

**If you filter to Final only:** 2 attempts (76% and 55%) → **100% pass rate**

---

## Quick checklist for managers

1. Pass rate = **attempts**, not people.
2. Pass line = **50%** (not Moodle quiz settings).
3. **All assessments** mixes every proctored quiz together.
4. **Reattempts** add extra attempts and can change pass rate.
5. Use **Sync now** for up-to-date numbers without waiting for cron.
6. Use **Top performers** when you care about best scores per person.

---

## Related document

- [Dashboard_Calculation_Guide.md](./Dashboard_Calculation_Guide.md) — full dashboard (KPIs, pipeline, queue, cache)
