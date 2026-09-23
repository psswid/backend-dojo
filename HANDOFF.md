# Backend Dojo — Handoff & Open Threads

**Last updated:** 2026-09 (session: strategic counsel + curriculum build + quiz UX + **lessons module: trial + full rollout to all 8 modules**; Rebilly application ghosted)
**Role of this file:** durable memory of what exists, what was decided, and what is still open — for any future agent session or for Piotr returning cold.

> Read first, before acting in this repo: `/data/ai-job-search/CLAUDE.md` (canonical career strategy — **PHP→Python/AI pivot is the active direction; do not relitigate it**). The dojo is the *interview-sharpness tool for the bridge income (PHP/Laravel)*, not a strategic pivot on its own. Funnel data: `/data/ai-job-search/job_search_tracker.csv`. Cast AI outcome (why the ai-cluster thread below exists): `/data/ai-job-search/documents/applications/cast_ai_senior_ai_engineer/outcome.md`.

---

## 1. Current state (verified in DB, 2026-09)

**8 topics, 105 questions, 62 coding tasks, 57 lessons (877 sections) — every module covered.** Content JSON files under `database/seeders/content/` (plus `.../content/lessons/`) are the **source of truth**; the DB is derived (seeders use `updateOrCreate`).

| # | slug | questions | tasks (snippet/debug/scenario) | lessons |
|---|------|-----------|-------------------------------|---------|
| 1 | api-legacy-integration | 14 | 5 (2/2/1) | 7 (109) |
| 2 | concurrency-race-conditions | 14 | 5 (2/2/1) | 7 (113) |
| 3 | multi-tenancy | 14 | 5 (2/2/1) | 7 (109) |
| 4 | queues-failed-jobs | 14 | 5 (2/2/1) | 7 (110) |
| 5 | architecture-scalability | 14 | 5 (2/2/1) | 7 (109) |
| 6 | caching-redis | 14 | 5 (2/2/1) | 7 (103) |
| 7 | **php-plain-code** | 8 | 26 (18/6/2) | **8** (117) |
| 8 | **sql** | 13 | 6 (0/0/6, all scenario) | 7 (107) |

Task types: `snippet` (write fn, auto-graded in sandbox), `debug` (fix planted bug, auto-graded), `scenario` (open design, AI-reviewed against rubric — needs the local model online; degrades gracefully offline).

### Build log (this session)
- **Lessons rollout — full coverage of all 8 modules** (work order, done):
  - After Piotr approved the php-plain-code trial format, 7 parallel authoring agents produced one file per module under `database/seeders/content/lessons/`: `api-legacy-integration` (7), `concurrency-race-conditions` (7), `multi-tenancy` (7), `queues-failed-jobs` (7), `architecture-scalability` (7), `caching-redis` (7), `sql` (7). Total library: **57 lessons / 877 sections**.
  - Authoring guardrails: `database/seeders/content/lessons/_SPEC.md` (schema + section-kind payload contracts + figure-argument gotcha + fact-verification rules) and `scripts/validate_lessons.py` (schema, payload shapes, markup balance, https-only links, rectangular tables, per-lesson tag coverage vs the module's questions+tasks, `php -l` on every php code payload). All 8 files validate PASS with 0 errors; every module's questions and tasks share ≥1 tag with ≥1 lesson (quiz-reveal + "Put it to work" wiring works everywhere).
  - Fact discipline held: pure-PHP example outputs executed on host PHP 8.4.1 before writing; SQL results verified against the live MySQL 8.4 (sql author ran row sets for INNER/LEFT/anti-join/FULL-OUTER-UNION, COUNT variants, ROW_NUMBER/RANK/DENSE_RANK ties, ONLY_FULL_GROUP_BY 1055, FULL OUTER parse 1064); framework/Redis claims restated strictly from each module's curated explanations, never invented; unverifiable corpus asides deliberately omitted.
  - Bugs fixed along the way: (a) authoring helper had swapped `figure()` args (markup in `title`, heading in `html`) → escaped markup rendered as text — fixed in the JSON, regression test added (`test_figure_sections_render_markup_not_raw_html`); (b) callout payloads stored the tone word in the headline field → all callouts fell back to one color; repaired all 14 in php-plain-code (tone from section key, title/text kept), validator now enforces tone values.
  - Smoke render in-container (`scripts/render_lessons_smoke.php`): all 57 lesson pages + 8 indexes render, 0 failures, no figure-payload regressions. Full suite: **77 passed / 273 assertions** (incl. generic `test_every_module_has_lessons_after_seeding`). Live DB seeded.
- **Lessons module — trial on `php-plain-code`** (work order, done):
  - New tables `lessons` + `lesson_sections` (migration `2026_09_17_000000_create_lesson_tables`), models `App\Models\Lesson` / `LessonSection` (tags ↔ questions/tasks for cross-linking; `prev/next` in topic order).
  - Content convention: **`database/seeders/content/lessons/<topic_slug>.json`** (`{"topic_slug":…,"lessons":[…]}`) — separate from topic files, parsed by `CurriculumSeeder::seedLessons()` (idempotent `updateOrCreate`, sort-order driven).
  - Section model: `prose` (plain text + backticks/bold/links via new `App\Support\LessonText`), `heading`, `code`, `table`, `steps` (numbered digestible steps with per-step `code`/mini-`table`/`After this step →` state line), `callout` (tip/watch/why/warning), `figure` (author-authored HTML diagrams using `.ld-*` CSS classes in `app.css` — no Tailwind from content). Partial renderers in `resources/views/lessons/partials/`.
  - UI: `topics/{topic}/lessons` (index) + `topics/{topic}/lessons/{lesson}` (show) — plain view closures, `auth+verified`, names `topics.lessons.*` (stay highlighted under the Modules nav). Module cards now split their footer into "Practice →" and "📖 N lessons" links (and `TopicIndex` now uses `withCount(['questions','lessons'])`, which also fixed the grid always showing `0/N`). Quiz reveal panel gained **"Read the lesson(s) first"** links (tag overlap via `Lesson::forQuestion`). Lesson show ranks *related tasks/questions* by tag-overlap strength, not just any overlap.
  - **8 lessons / 117 sections** authored for php-plain-code, all factual claims verified by executing PHP 8.4 (comparison truth table incl. `0 == ''` → **false** on PHP 8; slugify/phone states; Luhn `79927398713` + even-length `4417123456789113` parity; ISBN-10 `0306406152`, ISBN-13 `9780306406157`, PESEL `44051401359`, NIP `1234563218` row-by-row tables; `foreach (&$v)` corruption `[1,2,2]`; preg 1/0/false; generator interleave). Code snippets in lessons all pass `php -l`.
  - Tests: `tests/Feature/LessonsTest.php` (11: seeding + idempotency, auth gate, index/show render, figure-markup regression, every-module coverage, 404s, prev/next, tag matching) + `tests/Unit/LessonTextTest.php` (6: escaping, inline markup, code-not-reinterpreted, prose/bullets). Full suite green. Live DB migrated + seeded; views render in-container against MySQL.
  - **Dead-link sweep:** audited all 96 resource URLs in content (`/tmp/url_check.txt`); the only genuinely dead ones were martinfowler.com AntiCorruptionLayer + CacheAside (site removed them) and the old redis.io rate-limiting page → replaced with live Microsoft Azure Architecture Center pattern pages and `https://redis.io/tutorials/howtos/ratelimiting/`. dev.mysql.com 403s are bot-protection, fine in browsers — left alone.
- **Quiz UX — readable formatting + resumable sessions** (work order, done):
  - New `App\Support\QuizText`: turns plain-text prompts into safe HTML blocks — scenario questions with `(a) (b) (c)` subpoints render as an enumerated list with letter badges (markers only recognized after `. ? ! : ;` and contiguous — ordinary prose never split); backticked `` `code` `` spans become mono `<code>`; multi-line explanations get paragraphs, with SQL/PHP code-shaped blocks lifted into `<pre>`.
  - `resources/views/livewire/quiz-session.blade.php` rebuilt: lettered options (A/B/C/D…, ✓/✗ on reveal), type chip humanized ("Single/Multiple choice", "Open answer"), readable prompt/explanations, "Select all that apply" hint for multi.
  - **Quiz resume**: new `quiz_sessions` table + `App\Models\QuizSession` (state JSON snapshot per user/scope/topic). Snapshot written on select/toggle/answer/reveal/grade/next and debounced open-answer typing; `mount()` restores an unfinished session (amber "Resumed an interrupted session" banner + "Start over"); finishing a quiz deletes the row; a fresh session row is only created once real progress happens (no phantom "resumed" banners).
  - Verified: migration run; `tests/Feature/QuizSessionTest.php` (7 tests: resume, finished-not-resumed, restart-clears, open-answer text survival, content preservation, html escaping, enum detection) — green; `PagesTest|QuizRecordingTest` still green; blades compile; assets rebuilt. Session resume mimics the existing task-draft persistence pattern (`drafts` table + `TaskSession`).
  - Follow-up fix (user report): one-line options showed an empty first line — the option text span used `whitespace-pre-line` while the Blade source put `{{ $option }}` on its own indented line, so the leading newline rendered as a blank line. Fixed by inlining the option text (and dropping `whitespace-pre-line` there; options never carry newlines). Reveal/explanation panel restyled: verdict header band (✓ Correct / ✗ Not quite with "Correct answer: A/B…" for choice questions), white explanation body with "Explanation" label, `slate-900` code blocks.
- **P1 — `php-plain-code` module** (sort 7): Rebilly-class plain-code — string↔hex, slugify (PL transliteration), phone/PESEL/NIP/ISBN/Luhn validators, emails, arrays (group/sort/dedup/flatten), `safe_int` type-juggling, masking; + 8 knowledge questions (loose vs strict, falsy table, preg_match, array_column, uasort, strtr, foreach-by-ref, generators). Rationale: the plain-code ("naked PHP") gap is Piotr's actual failure mode (failed Proxify technical; Rebilly-style take-homes; market burned by AI-generated candidates).
- **P2 — `sql` module** (sort 8): 13 questions (JOINs incl. MySQL FULL-OUTER-JOIN-UNION gotcha, WHERE vs HAVING, COUNT(*) vs COUNT(col), N+1, leftmost-prefix, sargability, window functions, OFFSET vs keyset, EXPLAIN, SQL injection) + 6 scenarios. **Every example query was executed against the live MySQL 8.4 to verify** (schema `dojo_sql_check`, dropped after).
- **P3 — harder variants** in all 6 original modules (+1 snippet, +1 debug each, thematically tied: `parse_query_string`, `has_conflict` (3-way merge), `scoped_where` / `assert_scoped` (fail-closed), `backoff_plan`, `parse_ttl`, `rate_limit_ok`).

All three verified: `php scripts/validate-tasks.php` → **48 code tasks, 0 failures** (24 plain-code + 24 across the 6 original modules; scenarios excluded from the validator by design); every `debug` starter was run and confirmed to fail on **its own** planted bug; seeded into the live DB (topics id 7 and 8).

---

## 2. Open threads — pick up here

### Thread A — Dedupe the "backoff" task family
Three overlapping tasks now exist:
- `backoffDelay` (concurrency; base·2^(attempt−1), custom base/cap) — 1-liner
- `backoff_delay` (queues; min(2^attempt, 3600)) — 1-liner, near-duplicate
- `backoff_plan` (queues; full schedule, added in P3) — substantive, **keep this one**

Recommendation: keep `backoff_plan` as the real queues task; collapse the two 1-liners into one distinct warm-up or remove one. **Important:** seeders only create/update — removing a task from JSON does **not** remove its DB row (and may orphan `attempts`/`reviews` referencing the task id). Any removal must also delete DB rows, and check FK/cascade or existing attempts for that title first.

### Thread B — ai-cluster → agent harness (the positioning play; highest strategic value, NOT income)
Source signal (Cast AI outcome): the 3-node LLM cluster "resonated but was TS-only"; a friend (also TS, no Go/K8s) **passed** Cast AI's screen with an *in-production agent pipeline* (Hermes + Slack, triages issues → tickets → assigns devs). Lesson: the AI-infra/harness-engineering lane pays for **agents doing real work in production**, not model-serving infra.

Direction: pivot `~/ai-cluster` from "LLM inference + routing" to a **self-hosted agent harness, dogfooded on Piotr's own problems**:
1. **Job-search agent** — scrape offers (reuse scrapers under `/data/ai-job-search/.agents/skills/`), rank vs candidate profile, draft cover-letter skeletons → attacks the application-volume leak (~4.3 apps/week, see Thread C).
2. **Dojo drill agent** — generate/grade plain-code snippet drills via the local Qwen → attacks the technical-interview leak. (Partly realized already: the dojo's AI mentor talks to the cluster via Prism; `.env` `LLM_BASE_URL` points at the `mars` node / LiteLLM proxy.)
3. **GitHub issue-triage agent** — the friend's pattern, as a public repo → maps 1:1 to the Cast AI/Kimchi screen.

Positioning outcome: public repo + blog (the moat per CLAUDE.md) that reads as "agent harness infrastructure", not "homelab". TypeScript control plane already exists (never claim NestJS; see CLAUDE.md guardrails). Honest ROI: not income inside the 4–5-month runway; a 6–12-week positioning play that also fixes the two leaks blocking income. `~/ai-cluster` is outside this repo — this dojo is its inference substrate/consumer.

### Thread C — Application volume (survival money)
- Tracker reality: ~74 applications over ~17 weeks ≈ **4.3/week** — too low for survival mode; target 8–10/week in the bills-payer band (Symfony/Laravel/mid roles, 13–17k and lower).
- Salary stance updated this session: **willing to take 9–10k net B2B** (was a 12k floor); **zero loyalty** — keep applying until a 20k+ role lands; Uber/temp income = last-resort trigger tied to a calendar date, capped at ~10–12 h/week (evenings), main-day block stays protected for job landing.
- Pipeline at handoff: **Rebilly ghosted** (was in-process, ~$6k/m, PHP/Symfony, at code-challenge stage — the exact plain-code class he failed; see Thread on php-plain-code). No other hot lead. Tracker under-reports ghosting (~58 entries still "Applied").

### Thread D — README drift (nice-to-have)
`README.md` still says "6 modules / 18 tasks". Actual: 8 modules / 105 questions / 62 tasks / 8 lessons (trial). Update when convenient, and reflect the new modules in the feature list.

### Thread E — Lessons module (RESOLVED)
Piotr approved the php-plain-code trial format ("wyszło całkiem nieźle") and lessons were rolled out to **all 8 modules** (57 lessons / 877 sections, see build log). Open follow-ups, none blocking: Piotr's visual QA of the new modules' lessons; long-term ideas (per-question deep links into a lesson's section, "mark lesson read" tracking, lessons authoring for future modules added to the curriculum — a new module only needs a new `content/lessons/<slug>.json` + `db:seed`). Authoring discipline for the future lives in `database/seeders/content/lessons/_SPEC.md` + `scripts/validate_lessons.py` — reuse both before adding any lesson content.

---

## 3. Operations recipe (for future sessions)

- **Seed content:** normal — `docker compose exec app php artisan migrate --seed` (idempotent). From host only (`.env` hostname `mysql` resolves only inside the docker network): `DB_HOST=127.0.0.1 DB_PORT=3307 php artisan db:seed`.
- **Validate tasks:** `php scripts/validate-tasks.php` (host PHP; requires PHP ≥ 8.3). Checks: every snippet/debug **solution** passes its `test_suite` (exit 0) and every debug **starter** fails (prints `WARN` if a planted bug does not reproduce). Scenarios are skipped by design.
- **Grading contract:** sandbox runner (`docker/runner/runner.php`, container `php:8.4-cli-alpine`) concatenates `helper + submitted code + test_suite`; `expect(name, actual, expected)` uses **strict `===`**. Solutions must be plain PHP, self-contained, no Laravel/`mb_*`/non-core extensions (the runner image is stock alpine — `mbstring` is NOT assumed; write UTF-8-safe code without `mb_*`, e.g. `strtr` maps + `strtolower`).
- **Adding content:** drop a new JSON file under `database/seeders/content/` (topic+questions+resources) and/or `.../content/tasks/` (`topic_slug` must match an existing seeded topic) → re-seed. Multiple task files may share a `topic_slug`. Lessons go under `database/seeders/content/lessons/<topic_slug>.json` — `{"topic_slug": …, "lessons": [{slug, title, summary, minutes, difficulty, tags, sections: [{key, kind, title?, payload?}]}]}`; section kinds: `prose | heading | code | table | steps | callout | figure`. Prose/table/step text supports `` `code` ``, `**bold**`, `[label](https://…)` via `App\Support\LessonText`. Figure HTML must use only `.ld-*` diagram classes from `resources/css/app.css` (Tailwind does not scan DB content). All factual lesson claims were verified by executing PHP 8.4 — keep that discipline.
- **Removing content:** must also delete DB rows (seeders never delete).
- **Dojo app:** docker stack (app/web/queue/scheduler/mysql/redis/meilisearch/runner); app at http://localhost:8180 (README: seeded user `psswiderski@gmail.com` / `dojo1234`). AI mentor needs the cluster: `~/ai-cluster/cluster start mars`.

---

## 4. Where to start learning (day-1 path, written for Piotr)

Everything here was vibe-coded — **smoke-test the machine before trusting it**:

1. **Day 1 — validate the tool:** open http://localhost:8180; run one `snippet` and one `debug` task end-to-end (php-plain-code) and confirm the sandbox grades them; start the cluster (`~/ai-cluster/cluster start mars`) and check the AI mentor is online (liveness badge), then let it review one `scenario`; answer a few quiz questions and confirm the SM-2 "due for review" queue populates. If any of those silently fails, that is a bug to fix first — a training tool you cannot trust is worthless.
2. **Start with the two modules that target known gaps — not the six theory modules.** Piotr's failure mode is *plain-code fluency + SQL*, not conceptual depth (the concept base is strong by design):
   - `php-plain-code` (daily, timed, **no AI** — the tool's whole point is rebuilding the "naked" muscle; AI only as explainer *after* the attempt);
   - `sql` (write/read queries — heavily tested in Polish Symfony/Laravel interviews, previously zero coverage in the dojo).
3. **Daily rhythm:** morning 30–45 min of timed plain-code snippets/debugs (re-run failures); afternoon one module's quizzes via the "due for review" queue; evening 20–30 min SQL questions or one scenario with the AI mentor acting as mock interviewer.
4. **Weekly:** one full mock interview on a single module (scenario tasks with AI rubric review), plus one real take-home-style session (sit the dojo like an exam).
5. **Before any real technical interview:** 2 days out, drill that module's tasks + quiz to completion — the SM-2 queue is the prioritizer; trust it.
