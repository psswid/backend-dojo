# Backend Dojo

Personal training platform for **senior Laravel/Symfony backend developer** technical interviews.
Built to move past "vague answers" into deep understanding — spaced repetition, curated questions
with correct answers + explanations + resources, and a local AI mentor.

> Goal: be able to **destroy** technical interviews — without grinding leetcode.

## Stack

- **Laravel 13** + PHP 8.4 (Livewire 3 / Volt, Tailwind, Alpine)
- **MySQL 8** (curriculum + progress), **Redis** (cache, queues, Horizon), **Meilisearch** (search — Phase 3)
- **Laravel Horizon** (queue + failed-jobs dashboard — itself a study subject)
- **Prism** → local **Qwen3.6-35B-A3B** via the Olympus LiteLLM proxy (AI mentor)
- Fully Dockerized (`docker compose`)

## Quick start

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Open http://localhost:8180 — seeded user: `psswiderski@gmail.com` / `dojo1234`.

### AI mentor

The AI features talk to the local model through the LiteLLM proxy (`LLM_BASE_URL` in `.env`).
The cluster is **not auto-started** — start it first:

```bash
~/ai-cluster/cluster start      # then wait ~1 min warm-up
```

The dashboard shows a live online/offline badge; when offline the app keeps working
(quizzes, spaced repetition) and only the AI features degrade gracefully.

## Features (Phase 0–1 delivered)

- **Modules** — topic taxonomy; MVP seeds 6 priority modules (API/legacy, concurrency/race conditions,
  multi-tenancy, queues/failed jobs, architecture/scalability, caching/Redis).
- **Quizzes** — single / multiple / open questions with correct answers, teaching explanations, and
  canonical resource links.
- **Spaced repetition (SM-2)** — every answer is scheduled; weak ones return sooner until mastery.
- **Dashboard** — overall stats + per-module mastery heatmap + "due for review" queue.
- **AI mentor scaffold** — `LlmService` (Prism → LiteLLM), liveness probe, graceful offline.

## Curriculum data model

Content lives as **data** in `database/seeders/content/*.json` (one file per module) and is loaded by
`CurriculumSeeder`. Each file is self-contained: topic + questions + resources. To add a module, drop a
new JSON file and run `php artisan migrate --seed`.

## Structure

```
app/Models             Topic, Question, Resource, Task, Attempt, Review, AiConversation, AiMessage, Insight, InterviewLog
app/Services           SpacedRepetition (SM-2), ProgressService, QuizService, Llm/LlmService
app/Livewire           Dashboard, TopicIndex, QuizSession
database/seeders/content/*.json   curriculum (data)
docker-compose.yml     app, web (nginx), queue (Horizon), scheduler, mysql, redis, meilisearch
```

## Roadmap

- **Phase 2** — coding tasks (snippet / scenario / debug) + PHP sandbox runner + AI evaluation.
- **Phase 3** — AI agent personae (explainer, evaluator, mock interviewer, gap analyzer) with streaming.
- **Phase 4** — paste-a-job-posting → targeted plan + predicted questions, interview journal, cheat sheets.
