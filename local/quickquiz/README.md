# Quick quiz creator (`local_quickquiz`)

A small Moodle local plugin that creates **quiz** activities with a short form instead of the full quiz settings UI.

## Form fields

| Section | Fields |
|---------|--------|
| **Name** | Quiz name, course section |
| **Timing** | Open time, close time, time limit (optional) |
| **Grade** | Maximum grade |
| **Extra restrictions** | Quiz password (optional); ProctorLink options when installed |

All other quiz options (layout, review options, attempts, etc.) use Moodle quiz defaults (same as a standard new quiz).

## Proctoring (ProctorLink)

When `quizaccess_quizproctoring` is installed, the form can enable proctoring and set:

- Image interval  
- Warning threshold  
- Profile match, student video, eye tracking  

All defaults (grade, time limit, proctoring options) are configured in **Site administration → Plugins → Quick quiz creator** — not from global ProctorLink settings.

## Access

- Capability: `local/quickquiz:create` (editing teacher and manager by default)  
- Users with `mod/quiz:add` in the course may also use the tool  

## URLs

- Pick course: `/local/quickquiz/index.php`  
- Create in course: `/local/quickquiz/index.php?courseid=COURSEID`  

A **Create quick quiz** link is added under the course navigation when the user has permission.

## After creation

Students need **questions** in the quiz. Open the new quiz → **Questions** (or `/mod/quiz/edit.php?cmid=...`) to add them.

## Install

1. Copy `local/quickquiz` into Moodle `local/`.  
2. **Site administration → Notifications** to install.  
3. Adjust default grade / proctoring in plugin settings if needed.
