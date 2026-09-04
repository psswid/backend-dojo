# Backend Dojo

Personal training platform for **senior Laravel/Symfony backend developer** technical interviews.
Built to move past "vague answers" into deep understanding — spaced repetition, curated questions
with correct answers + explanations + resources, and a local AI mentor.

> Goal: be able to **destroy** technical interviews — without grinding leetcode.

## Stack

- **Laravel 13** + PHP 8.4 (Livewire 3 / Volt, Tailwind, Alpine)
- **MySQL 8** (curriculum + progress), **Redis** (cache, queues, Horizon), **Meilisearch** (search — Phase 3)
- **Laravel Horizon** (queue + failed-jobs dashboard — itself a study subject)
- **Prism** → local **Qwen3.6-35B-A3B** (Mars llama.cpp, OpenAI-compatible `:11000`) (AI mentor)
- **Isolated PHP sandbox runner** (read-only container, no creds, memory+CPU capped) for coding tasks
- Fully Dockerized (`docker compose`)

## Quick start

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Open http://localhost:8180 — seeded user: `psswiderski@gmail.com` / `dojo1234`.

### AI mentor

The AI features talk to the local model directly on Mars (`LLM_BASE_URL` in `.env`,
default `http://host.docker.internal:11000/v1` — OpenAI-compatible llama.cpp).
The cluster is **not auto-started** — start it first:

```bash
~/ai-cluster/cluster start mars      # then wait ~1 min warm-up
```

(Alternative: route through the Olympus LiteLLM proxy at `http://192.168.1.82:4001/v1`
with model `mars-qwen35b` when that proxy is running.)

The dashboard shows a live online/offline badge; when offline the app keeps working
(quizzes, spaced repetition) and only the AI features degrade gracefully.

## Features (Phase 0–2 delivered)

- **Modules** — topic taxonomy; seeds 6 priority modules (API/legacy, concurrency/race conditions,
  multi-tenancy, queues/failed jobs, architecture/scalability, caching/Redis).
- **Quizzes** — single / multiple / open questions with correct answers, teaching explanations, and
  canonical resource links.
- **Spaced repetition (SM-2)** — every answer is scheduled; weak ones return sooner until mastery.
- **Dashboard** — overall stats + per-module mastery heatmap + "due for review" queue.
- **Coding tasks** — 18 tasks across the 6 modules, in 3 types:
  - **snippet** — write a small backend function from a skeleton (auto-graded by the sandbox);
  - **debug** — fix a planted bug (auto-graded);
  - **scenario** — open design question, AI-reviewed against a rubric (score + feedback).
- **PHP sandbox runner** — isolated `runner` container runs submitted code with `expect()`-based tests.
- **AI mentor** — live Qwen3.6-35B-A3B via Prism: scenario evaluation, code hints, liveness badge.

## Content data model

Content lives as **data** (reviewable in git), loaded by seeders:
- `database/seeders/content/*.json` — questions + resources (`CurriculumSeeder`);
- `database/seeders/content/tasks/*.json` — coding tasks (`TaskSeeder`).

Each file is self-contained. To add a module/task, drop a new JSON file and run `php artisan migrate --seed`.
Validate task code before seeding: `php scripts/validate-tasks.php`.

## Structure

```
app/Models             Topic, Question, Resource, Task, Attempt, Review, AiConversation, AiMessage, Insight, InterviewLog
app/Services           SpacedRepetition (SM-2), ProgressService, QuizService, CodeRunner, Llm/LlmService, Llm/TaskEvaluator
app/Livewire           Dashboard, TopicIndex, QuizSession, TaskIndex, TaskSession
database/seeders/content/*.json          questions + resources
database/seeders/content/tasks/*.json    coding tasks
docker/runner/runner.php                 isolated PHP sandbox
docker-compose.yml     app, web (nginx), queue (Horizon), scheduler, mysql, redis, meilisearch, runner
```

## Roadmap

- **Phase 3** — AI agent personae (explainer, evaluator, mock interviewer, gap analyzer) with streaming.
- **Phase 4** — paste-a-job-posting → targeted plan + predicted questions, interview journal, cheat sheets.
