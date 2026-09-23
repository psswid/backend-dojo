# Backend Dojo — Lesson content spec (for AI authors)

Author lesson files into `database/seeders/content/lessons/<topic_slug>.json`.
One file per module. Existing, **user-approved** example: `database/seeders/content/lessons/php-plain-code.json` — read it first and mirror its tone, rhythm, density and callout usage.

## File shape

```json
{
  "topic_slug": "<slug>",
  "lessons": [
    {
      "slug": "kebab-case-unique",
      "title": "Readable title (<= ~90 chars)",
      "summary": "2-3 plain sentences, NO markup. Why this idea matters and what the reader will be able to do after.",
      "minutes": 6,
      "difficulty": "easy | medium | hard",
      "tags": ["kebab-tags"],
      "sections": [ ... ]
    }
  ]
}
```

- `tags` MUST overlap the module's **question** tags and **task** tags (drives the "Put it to work" related blocks and the quiz reveal "Read the lesson" links). When in doubt copy the exact tags used in the module's content JSONs.
- `sections` in reading order. Keys are stable ids: `[a-z0-9-]{1,40}`, unique per lesson. A lesson typically starts with a `prose` hook and is split by `heading`s into 3-5 parts, mixing steps/tables/figures/callouts, and often ends with a short "so what" prose/callout.

## Section kinds (renderers: `resources/views/lessons/partials/<kind>.blade.php` — read them)

**Inline markup** in text-like strings only: `` `code` ``, `**bold**`, `[label](https://…)`. Blank line separates paragraphs; a block whose lines all start with `- ` renders as bullets. All text fields are HTML-escaped at render time, so operators like `=>`, `<`, `>` are safe to write literally — only the figure `html` payload is raw markup.

1. `prose` — payload `{"text": "..."}`.
2. `heading` — uses the section-level `title` field, NO payload: `{"key":"x","kind":"heading","title":"Part title"}`. Gives an anchor + teal bar; keeps the lesson scannable.
3. `code` — payload `{"code":"...","lang":"php|sql|text|bash|plain","title"?: "...","caption"?: "..."}`. `code` is ONE string with real newlines, never triple-fenced. `php` payloads are linted by the validator — keep them syntactically valid standalone snippets.
4. `table` — payload `{"title"?: str,"headers":[...],"rows":[[...]],"note"?: str,"mono"?: [colIndex...],"em"?: [colIndex...]}`. Rows must be rectangular vs headers. Use for comparison matrices and for **running-total / state-transition tables** (recompute every number!).
5. `steps` — payload `{"title"?, "intro"?, "steps":[{title?, text?, code?, table?:{"headers":[...],"rows":[[...]]}, state?}]}`. The signature "example split into digestible parts": numbered blocks, optional per-step code/mini-table, and `state` = short string shown as `After this step → <code>`. Max ~6 steps per block.
6. `callout` — payload `{"tone":"tip|watch|why|warning|danger","title"?: str,"text": str}`. tip=good practice, watch=common trap, why=concept motivation, warning/danger=severe.
7. `figure` — payload `{"html":"<div class=\"ld-...\">…</div>", "title": str, "caption"?: str}`.

   ⚠️ **Known bug to avoid:** `html` must be the actual DIAGRAM MARKUP, `title` the plain-text heading, `caption` an optional note. Swapping them renders escaped markup as text (this happened once; the user spotted it).
   Markup uses ONLY bespoke classes defined in `resources/css/app.css` (Tailwind never scans DB content): `ld-flow`, `ld-flow-nw`, `ld-col`, `ld-box` + state colors `ld-a` (good/emerald), `ld-b` (indigo), `ld-c` (amber), `ld-r` (bad/red), `ld-dim`; inside an `ld-box` you may add `<span class="tag">…</span>`; separators `ld-arrow` (`&#8594;` →, `&#8595;` ↓, `&#8658;` ⇒), `ld-down`, `ld-stack`, `ld-legend`, `ld-note`. No Tailwind utilities, no inline `style=`, no scripts, no `<svg>`. Keep diagrams small (a few lanes / ≤ ~15 nodes) — they must survive a phone-width scroll.

## Coverage requirement

Read the module corpus BEFORE writing:
- questions: `database/seeders/content/<slug>.json`
- tasks: `database/seeders/content/tasks/<slug>.json`

Cluster the module into **5-7 lessons** so that **EVERY question and EVERY task shares ≥ 1 tag with ≥ 1 lesson** (the validator warns about tag orphans). Natural structure: one lesson per concept family (quiz explainers), with the module's heavier task families (e.g. debug/scenario pairs) folded into their concept lesson as the "Put it to work" anchors. Where a concept has no dedicated task, the lesson still links the module's related questions automatically.

## Factual discipline (non-negotiable)

- Concrete claims — a function's return value, a printed output, a checksum/algorithm step, an ordering/timing guarantee, an SQL result, a Redis/Laravel behavior — MUST be verified before you write them.
- **Best source: the module's own question explanations and task solutions inside the content JSONs.** They are curated and were verified when the curriculum was built. Prefer restating/deriving from them over inventing new examples.
- For NEW small PHP examples/outputs: execute them with the host binary (`php -r '...'` — PHP 8.4.1 available on this host) and print exactly what it produces.
- For SQL: prefer the module's own example queries/results; you may run queries against the live MySQL at `127.0.0.1:3307` (creds: `DB_USER`/`DB_PASSWORD` in `/data/backend-dojo/.env`; e.g. `mysql -h127.0.0.1 -P3307 -u<user> -p<pass>`). Only the results you actually saw may be printed.
- Framework claims (Laravel/Symfony/Redis behavior): state only what the module's corpus asserts, or what you confirm via docs. If unverifiable, omit or phrase qualitatively without concrete numbers.
- Recompute every arithmetic table (checksums, running totals, state transitions).
- Never contradict the module's existing explanations; where the corpus is silent, stay qualitative.
- This dojo's plain-PHP sandbox has NO `mbstring`/extensions — don't propose `mb_*` in lesson code for the module tasks.

## Scope & length

5-7 lessons per module; total sections ~60-110 per module file; `minutes` 4-12 each; file lands around 40-90 KB. Quality over volume — five excellent lessons beat seven padded ones. Diagrams/figures: use sparingly but effectively (1-3 per module is fine; steps + tables are the primary vehicle).

## Validation (MANDATORY before you finish — run it and fix until PASS)

```bash
python3 /data/backend-dojo/scripts/validate_lessons.py /data/backend-dojo/database/seeders/content/lessons/<slug>.json
```

Must end with `PASS` and 0 errors. Warnings (tag orphans, markup imbalance) should be read and, where legitimate, fixed.

Do NOT run docker, migrations, seeders or the test suite — only host `python3`/`php` for validation and fact-checking. Write ONLY your module's lessons JSON file; leave every other file untouched.
