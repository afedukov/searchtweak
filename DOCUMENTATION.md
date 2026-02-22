# SearchTweak — Comprehensive Project Documentation

> A self-hosted search relevance testing and evaluation platform for comparing and improving search engine configurations.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technology Stack](#2-technology-stack)
3. [Architecture Overview](#3-architecture-overview)
4. [Core Domain Concepts](#4-core-domain-concepts)
5. [Database Structure](#5-database-structure)
6. [Models (Eloquent)](#6-models-eloquent)
7. [Services](#7-services)
8. [Actions](#8-actions)
9. [Jobs (Queue)](#9-jobs-queue)
10. [Events & Notifications](#10-events--notifications)
11. [API Reference](#11-api-reference)
12. [Web Routes & Livewire Components](#12-web-routes--livewire-components)
13. [Permissions & Roles](#13-permissions--roles)
14. [Broadcasting & Real-Time Updates](#14-broadcasting--real-time-updates)
15. [Scoring & Metrics System](#15-scoring--metrics-system)
16. [Scales & Transformers](#16-scales--transformers)
17. [Mapper System](#17-mapper-system)
18. [Widgets System](#18-widgets-system)
19. [Tags System](#19-tags-system)
20. [Testing](#20-testing)
21. [Development Environment](#21-development-environment)
22. [Helper Functions](#22-helper-functions)
23. [Configuration Highlights](#23-configuration-highlights)

---

## 1. Project Overview

**SearchTweak** is a self-hosted web application designed for **search relevance evaluation**. It allows teams to:

- **Configure search endpoints** — define connections to any REST-based search API.
- **Create search models** — parameterize requests with headers, query parameters, and body templates.
- **Run evaluations** — execute search queries against configured models and collect relevance judgments from human evaluators.
- **Configure LLM judges** — set up AI-powered judges (OpenAI, Anthropic, Google, DeepSeek, xAI, Groq, Mistral, Custom OpenAI-compatible, Ollama) for automated relevance evaluation alongside human feedback.
- **Calculate IR metrics** — automatically compute industry-standard metrics such as Precision, MAP, MRR, DCG, NDCG, and CG over evaluation results.
- **Compare results** — track metric changes over time, set baselines, and compare evaluations.
- **Collaborate** — multi-tenant team system with role-based access control and real-time UI updates.

The platform supports three relevance grading scales (Binary, Graded, Detail) and provides grade transformers for cross-scale metric comparison. It features a feedback assignment system with configurable strategies, reuse strategies for historical judgments, LLM-as-a-judge automation, and a customizable dashboard with widgets.

---

## 2. Technology Stack

### Backend
| Technology | Version / Notes |
|---|---|
| **PHP** | 8.3 |
| **Laravel** | 11.x |
| **Laravel Horizon** | Queue monitoring & management |
| **Laravel Reverb** | WebSocket server for real-time broadcasting |
| **Laravel Jetstream** | Authentication, teams, profile management |
| **Laravel Livewire** | 3.x — reactive server-rendered UI components |
| **Laravel Sanctum** | API token authentication |
| **Laravel Fortify** | Authentication backend (registration, password reset, 2FA) |
| **GuzzleHTTP** | HTTP client for executing search endpoint requests |
| **Predis** | Redis client |
| **MySQL** | Primary database |
| **Redis** | Cache, sessions, queue broker, broadcasting |

### Frontend
| Technology | Notes |
|---|---|
| **Blade** | Laravel templating engine |
| **Alpine.js** | Lightweight JS framework (via Livewire) |
| **Tailwind CSS** | Utility-first CSS framework |
| **Vite** | Frontend build tool |
| **Chart.js** | Charting library (metric value graphs) |
| **Font Awesome** | Icon library |
| **Axios** | HTTP client (JavaScript) |
| **Laravel Echo** | JavaScript library for WebSocket subscriptions |

### Infrastructure
| Technology | Notes |
|---|---|
| **Docker Compose** | Local development orchestration |
| **Traefik** | Reverse proxy (local dev) |
| **Nginx** | Web server |
| **PHP-FPM** | PHP process manager |
| **MailHog** | Email testing (local dev) |
| **phpMyAdmin** | Database management UI (local dev) |

---

## 3. Architecture Overview

SearchTweak follows a **domain-driven architecture** built on Laravel conventions:

```
┌─────────────────────────────────────────────────────────┐
│                      Web Browser                        │
│  (Livewire + Alpine.js + Laravel Echo WebSockets)       │
└───────────────┬─────────────────────────┬───────────────┘
                │ HTTP                    │ WebSocket
                ▼                         ▼
┌───────────────────────┐   ┌─────────────────────────────┐
│     Nginx / Traefik   │   │    Laravel Reverb (WS)      │
└───────────┬───────────┘   └─────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────┐
│                    PHP-FPM (Laravel 11)                   │
│                                                           │
│  ┌─────────┐  ┌───────────┐  ┌───────────┐  ┌──────────┐  │
│  │ Routes  │  │Controllers│  │ Livewire  │  │   API    │  │
│  │  (web)  │  │           │  │Components │  │(Sanctum) │  │
│  └────┬────┘  └─────┬─────┘  └─────┬─────┘  └────┬─────┘  │
│       │             │             │              │        │
│       ▼             ▼             ▼              ▼        │
│  ┌───────────────────────────────────────────────────┐    │
│  │              Actions (Business Logic)             │    │
│  └──────────────────────┬────────────────────────────┘    │
│                         │                                 │
│       ┌─────────────────┼─────────────────┐               │
│       ▼                 ▼                 ▼               │
│  ┌──────────┐    ┌───────────┐      ┌───────────┐         │
│  │ Models   │    │ Services  │      │   Jobs    │         │
│  │(Eloquent)│    │ (Scorers, │      │  (Queue)  │         │
│  │          │    │  Mapper,  │      │           │         │
│  │          │    │  etc.)    │      │           │         │
│  └────┬─────┘    └───────────┘      └─────┬─────┘         │
│       │                                   │               │
│       ▼                                   ▼               │
│  ┌──────────┐                     ┌──────────────┐        │
│  │  MySQL   │                     │    Redis     │        │
│  └──────────┘                     │ (Queue/Cache)│        │
│                                   └──────────────┘        │
└───────────────────────────────────────────────────────────┘
```

### Key Architectural Patterns

1. **Actions Pattern** — Business logic is encapsulated in Action classes (`app/Actions/`), keeping controllers thin.
2. **Job Queue Architecture** — Long-running operations (evaluation execution, metric recalculation) are dispatched as queued jobs managed by Laravel Horizon.
3. **Real-Time Broadcasting** — Models extend `TeamBroadcastableModel` to automatically broadcast CRUD events to team-specific WebSocket channels.
4. **Service Layer** — Complex domain logic (scoring, mapping, feedback assignment) lives in dedicated service classes.
5. **Livewire Components** — The entire frontend is built with Livewire 3, providing reactive UIs without custom JavaScript.
6. **Multi-Tenancy via Teams** — All data is scoped to teams using Laravel Jetstream's team infrastructure.

---

## 4. Core Domain Concepts

### 4.1 Search Endpoint
Defines **how** to connect to a search API:
- URL, HTTP method (GET/POST), headers
- **Mapper Code** — rules for extracting document data from API responses
- Can be active or archived
- Team-scoped

### 4.2 Search Model
Defines **what** to send to a search endpoint:
- References a `SearchEndpoint`
- Custom headers, query parameters, and request body
- Body supports the `#query#` placeholder for keyword substitution
- Contains keyword lists and settings
- Team-scoped

### 4.3 Search Evaluation
An **evaluation run** that executes keywords against a model and collects relevance feedback:
- Links to a `SearchModel`
- Has a **status lifecycle**: Pending → Active → Finished
- Tracks progress (percentage of graded feedback)
- Supports **scale types**: Binary, Graded, Detail
- Contains settings: feedback strategy, reuse strategy, auto-restart, scoring guidelines, transformers
- Can be archived, pinned, or set as baseline

### 4.4 Evaluation Keyword
A single keyword within an evaluation:
- Stores the keyword text and execution results (HTTP status code, message)
- Has many `SearchSnapshot` records (one per result document)
- Tracks whether keyword execution failed

### 4.5 Search Snapshot
A single search result document for a keyword:
- Extracted from the API response using the mapper
- Contains `doc_id`, `name`, `image`, `position`, and raw document data (`doc`)
- Has many `UserFeedback` records for relevance grading

### 4.6 User Feedback
A human relevance judgment for a search snapshot:
- Assigned to a user with a time-limited lock (5 minutes)
- Contains a `grade` value according to the evaluation's scale
- Supports global pool (across evaluations) and evaluation-specific pools

### 4.7 Evaluation Metric
A computed metric result for an evaluation:
- References a specific scorer type (e.g., `precision`, `ndcg`)
- Stores current value, previous value, and number of results (top-K)
- Has many `KeywordMetric` records (per-keyword breakdown)
- Has many `MetricValue` records (historical value timeline)

### 4.8 Baseline Evaluation
A finished evaluation set as the team's baseline for comparison. Other evaluations display their metric change relative to the baseline.

### 4.9 Judge (LLM-as-a-Judge)
An **AI-powered judge** that can automatically evaluate search result relevance:
- Configures an LLM provider (`openai`, `anthropic`, `google`, `deepseek`, `xai`, `groq`, `mistral`, `custom_openai`, `ollama`) with model name
- Provider auth and endpoint behavior:
  - Most providers require API key (`ollama` is optional)
  - `custom_openai` requires configurable `base_url`
  - `ollama` supports configurable `base_url` and normalizes host-only URLs to `/v1/chat/completions`
- Has **per-scale prompt templates** (Binary, Graded, Detail) with a `#pairs#` placeholder for dynamic data injection
- Configurable **batch size** (1–20 pairs per LLM request, default 5)
- Supports optional model params (`settings.model_params`) passed directly to provider APIs
- Can be active or archived (`archived_at` timestamp)
- Team-scoped, supports tags for filtering
- API keys encrypted at rest

### 4.10 Judge Logs
All judge requests are logged in `judge_logs` with:
- Provider/model, request URL/body, response body, error message
- HTTP status, latency, prompt/completion/total tokens
- Team/evaluation/judge references for filtering and troubleshooting

---

## 5. Database Structure

### 5.1 Core Tables

#### `search_endpoints`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `user_id` | int (FK) | Creator user |
| `team_id` | int (FK) | Owning team |
| `type` | int | Endpoint type |
| `name` | varchar | Display name |
| `url` | varchar | API URL |
| `method` | varchar | HTTP method (GET/POST) |
| `description` | text | Description |
| `headers` | json | Custom HTTP headers |
| `mapper_type` | int | Mapper implementation type |
| `mapper_code` | text | Mapper extraction rules |
| `settings` | json | Additional settings |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `search_models`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `user_id` | int (FK) | Creator user |
| `team_id` | int (FK) | Owning team |
| `endpoint_id` | int (FK) | Associated endpoint |
| `name` | varchar | Display name |
| `description` | text | Description |
| `headers` | json | Additional/override headers |
| `params` | json | Query parameters (supports `#query#`) |
| `body` | text | Request body template (supports `#query#`) |
| `body_type` | int | Body content type |
| `settings` | json | Settings (keywords list, etc.) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `search_evaluations`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `user_id` | int (FK) | Creator user |
| `model_id` | int (FK) | Associated search model |
| `scale_type` | varchar | Scale type: `binary`, `graded`, `detail` |
| `status` | int | 0=Pending, 1=Active, 2=Finished |
| `progress` | float | Grading progress (0–100%) |
| `name` | varchar | Display name |
| `description` | text | Description |
| `settings` | json | Feedback strategy, reuse strategy, transformers, scoring guidelines |
| `max_num_results` | int | Maximum results per keyword |
| `successful_keywords` | int | Count of successfully executed keywords |
| `failed_keywords` | int | Count of failed keywords |
| `archived` | bool | Whether archived |
| `pinned` | bool | Whether pinned to top |
| `finished_at` | timestamp | When evaluation was finished |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `evaluation_keywords`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `search_evaluation_id` | int (FK) | Parent evaluation |
| `keyword` | varchar | Search query text |
| `total_count` | int | Total results count from API |
| `execution_code` | int | HTTP response status code |
| `execution_message` | varchar | Response message |
| `failed` | bool | Whether execution failed |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `search_snapshots`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `evaluation_keyword_id` | int (FK) | Parent keyword |
| `position` | int | Result position (1-indexed) |
| `doc_id` | varchar | Document identifier |
| `image` | varchar | Document image URL |
| `name` | varchar | Document display name |
| `doc` | json | Full extracted document data |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `user_feedbacks`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `user_id` | int (FK, nullable) | Assigned evaluator |
| `judge_id` | int (FK, nullable) | Assigned AI judge |
| `search_snapshot_id` | int (FK) | Snapshot being graded |
| `grade` | int (nullable) | Relevance grade |
| `reason` | text (nullable) | Optional explanation (primarily AI judge output) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `evaluation_metrics`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `search_evaluation_id` | int (FK) | Parent evaluation |
| `scorer_type` | varchar | Scorer identifier (e.g., `precision`, `ndcg`) |
| `value` | float (nullable) | Current aggregate metric value |
| `previous_value` | float (nullable) | Value from previous evaluation |
| `num_results` | int | Number of top results (K) |
| `settings` | json | Scorer-specific settings |
| `finished_at` | timestamp | When calculation was completed |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `keyword_metrics`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `evaluation_keyword_id` | int (FK) | Keyword |
| `evaluation_metric_id` | int (FK) | Metric |
| `value` | float | Per-keyword metric value |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `metric_values`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `evaluation_metric_id` | int (FK) | Parent metric |
| `value` | float | Snapshot of the metric value at a point in time |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `judges`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `user_id` | int (FK) | Creator user |
| `team_id` | int (FK) | Owning team |
| `name` | varchar | Display name (unique per team) |
| `description` | varchar (nullable) | Description |
| `provider` | varchar | LLM provider: `openai`, `anthropic`, `google`, `deepseek`, `xai`, `groq`, `mistral`, `custom_openai`, `ollama` |
| `model_name` | varchar | LLM model identifier (e.g., `gpt-4o`) |
| `api_key` | text (encrypted) | Provider API key |
| `prompt_binary` | text (nullable) | Prompt template for binary scale |
| `prompt_graded` | text (nullable) | Prompt template for graded scale |
| `prompt_detail` | text (nullable) | Prompt template for detail scale |
| `settings` | json (nullable) | Additional settings (`batch_size`, `model_params`, `base_url`) |
| `archived_at` | timestamp (nullable) | Archive timestamp (null = active) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `judge_logs`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `judge_id` | int (FK, nullable) | Judge (nullable if deleted) |
| `team_id` | int (FK, nullable) | Team scope |
| `search_evaluation_id` | int (FK, nullable) | Evaluation context |
| `provider` | varchar | Provider used for request |
| `model` | varchar | Model name |
| `http_status_code` | int (nullable) | Provider response code |
| `request_url` | text | Sanitized request URL |
| `request_body` | longtext | Request payload |
| `response_body` | longtext (nullable) | Raw provider response |
| `error_message` | text (nullable) | Transport/HTTP error summary |
| `latency_ms` | int (nullable) | Request latency in milliseconds |
| `prompt_tokens` | int (nullable) | Prompt/input tokens |
| `completion_tokens` | int (nullable) | Completion/output tokens |
| `total_tokens` | int (nullable) | Total tokens |
| `batch_size` | tinyint (nullable) | Pairs sent in one call |
| `scale_type` | varchar (nullable) | Scale type (`binary/graded/detail`) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 5.2 Team & User Tables

#### `teams`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `user_id` | int (FK) | Owner user |
| `name` | varchar | Team name |
| `personal_team` | bool | Whether it's a personal team |
| `baseline_evaluation_id` | int (FK, nullable) | Baseline evaluation for comparison |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `users`
Standard Laravel user table with additional fields:
- `super_admin` (bool) — platform-wide admin flag
- `current_team_id` (FK) — currently active team

#### `user_widgets`
| Column | Type | Description |
|---|---|---|
| `id` | uuid (PK) | UUID primary key |
| `user_id` | int (FK) | Widget owner |
| `team_id` | int (FK) | Team context |
| `widget_class` | varchar | Livewire widget class FQCN |
| `position` | int | Display order |
| `visible` | bool | Visibility toggle |
| `settings` | json (nullable) | Widget-specific settings |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 5.3 Tags & Pivot Tables

#### `tags`
| Column | Type | Description |
|---|---|---|
| `id` | int (PK) | Primary key |
| `team_id` | int (FK) | Owning team |
| `color` | varchar | Tag color identifier |
| `name` | varchar | Tag display name |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Pivot tables:
- `evaluation_tags` — links evaluations to tags
- `user_tags` — links users to tags (for feedback routing)
- `model_tags` — links models to tags
- `judge_tags` — links judges to tags

### 5.4 Other Tables
- `team_invitations` — pending team member invitations
- `notification_unsubscriptions` — user notification opt-outs
- `sessions` — application sessions
- `personal_access_tokens` — Sanctum API tokens
- `job_batches`, `failed_jobs` — Laravel queue tables

---

## 6. Models (Eloquent)

All models are in `app/Models/`. Key patterns:
- Field names are defined as class constants (`FIELD_*`)
- Status values use integer constants (`STATUS_*`)
- Models use typed properties and PHP 8.3 features

### 6.1 `SearchEndpoint`
- **Relationships**: `belongsTo` User, Team; `hasMany` SearchModel
- **Key Methods**: `isActive()`, `isArchived()`, `getShortenedUrl()`, `getExecutionQueue()`
- **Scopes**: Team filtering

### 6.2 `SearchModel`
- **Relationships**: `belongsTo` User, Team, SearchEndpoint; `hasMany` SearchEvaluation
- **Key Methods**: `getHeaders()`, `getHiddenHeaders()`, `getMetrics()`, `getKeywords()`
- **Implements**: `TaggableInterface`

### 6.3 `SearchEvaluation`
- **Extends**: `TeamBroadcastableModel` (auto-broadcasts CRUD events)
- **Implements**: `TaggableInterface`
- **Relationships**: `belongsTo` User, SearchModel; `hasMany` EvaluationKeyword, EvaluationMetric; `belongsToMany` Tag
- **Status Lifecycle**: Pending (0) → Active (1) → Finished (2)
- **Key Methods**: `isPending()`, `isActive()`, `isFinished()`, `isFailed()`, `isBaseline()`, `isDeletable()`, `canGiveFeedback()`, `updateProgress()`, `getScale()`, `getTransformers()`, `getFeedbackStrategy()`, `getReuseStrategy()`, `autoRestart()`, `getScoringGuidelines()`
- **Scopes**: `pending()`, `active()`, `finished()`, `notFinished()`, `team()`
- **Events**: Dispatches status/progress/scale/archive change events on update
- **Settings**: feedback strategy, show position, reuse strategy, auto-restart, transformers, scoring guidelines

### 6.4 `EvaluationKeyword`
- **Relationships**: `belongsTo` SearchEvaluation; `hasMany` SearchSnapshot, KeywordMetric
- **Key Methods**: `isFailed()`

### 6.5 `SearchSnapshot`
- **Relationships**: `belongsTo` EvaluationKeyword; `hasMany` UserFeedback
- **Key Methods**: `createFromDocument()`, `createUserFeedbacks()`, `needFeedback()`, `hasUserFeedback()`

### 6.6 `UserFeedback`
- **Relationships**: `belongsTo` User, SearchSnapshot
- **Scopes**: `team()`, `globalPool()`, `evaluationPool()`, `graded()`, `ungraded()`, `assignedTo()`
- **Key Methods**: `isAssignmentExpired()`, `isAvailableTo()`, `isUngradedAssignedTo()`, `isUngradedUnassigned()`

### 6.7 `EvaluationMetric`
- **Relationships**: `belongsTo` SearchEvaluation; `hasMany` KeywordMetric, MetricValue
- **Key Methods**: `calculateMetrics()`, `getPreviousMetricValue()`, `getChange()`, `getLastValues()`

### 6.8 `KeywordMetric`
- **Relationships**: `belongsTo` EvaluationKeyword, EvaluationMetric
- Stores per-keyword metric values

### 6.9 `MetricValue`
- **Broadcasts**: Emits `created` events on private channels
- **Relationships**: `belongsTo` EvaluationMetric
- Stores metric value history for timeline charts

### 6.10 `Team`
- **Extends**: Jetstream `Team`
- **Relationships**: `belongsTo` User (owner); `hasMany` SearchEndpoint, SearchModel, TeamInvitation, Tag; `belongsTo` SearchEvaluation (baseline)
- **Events**: Dispatches `BaselineEvaluationChangedEvent` when baseline changes

### 6.11 `User`
- **Traits**: `HasApiTokens`, `HasFactory`, `HasProfilePhoto`, `HasTeams`, `Notifiable`, `TwoFactorAuthenticatable`
- **Relationships**: Teams, SearchEndpoint, SearchModel, SearchEvaluation, UserWidget, Tags, NotificationUnsubscription
- **Key Methods**: `isSuperAdmin()`, `isAdmin()`, `isEditor()`, `isOwner()`, `isEvaluator()`, `canManageTeam()`, `canGiveFeedback()`

### 6.12 `UserWidget`
- UUID primary key
- **Key Methods**: `getNameAttribute()` — resolves widget name from class

### 6.13 `Tag`
- **Relationships**: `belongsTo` Team
- **Key Methods**: `getAvailableColors()`, `getColorClasses()`, `format()`

### 6.14 `Judge`
- **Extends**: `TeamBroadcastableModel` (auto-broadcasts CRUD events)
- **Implements**: `TaggableInterface`
- **Relationships**: `belongsTo` User, Team; `belongsToMany` Tag (via `JudgeTag`)
- **Providers**: `openai`, `anthropic`, `google`, `deepseek`, `xai`, `groq`, `mistral`, `custom_openai`, `ollama`
- **Key Methods**: `isActive()`, `isArchived()`, `getDefaultPrompt(string $scaleType)`, `getBatchSize()`, `getModelParams()`, `getBaseUrl()`
- **Provider Helpers**: `providerRequiresApiKey()`, `getProviderLabel()`
- **Scopes**: `active()`
- **Security**: `api_key` encrypted via Eloquent cast, hidden from serialization
- **Settings**: `batch_size` (1–20 per request, default 5), `model_params`, `base_url`
- **Prompt Templates**: Per-scale (`prompt_binary`, `prompt_graded`, `prompt_detail`) with `#pairs#` placeholder

### 6.15 `JudgeTag`
- Pivot model for Judge-Tag relationship
- **Relationships**: `belongsTo` Judge, Tag

### 6.16 `TeamBroadcastableModel` (Abstract)
- Base class for models that broadcast CRUD events to team channels
- Uses `BroadcastsEvents` trait
- Broadcasts to team-specific `PrivateChannel` automatically
- Supports additional broadcast channels via hook methods

---

## 7. Services

### 7.1 Scorers (`app/Services/Scorers/`)

#### `Scorer` (Abstract Base Class)
Abstract class for all metric scorers. Provides:
- `calculate(EvaluationKeyword, int $limit): ?float` — main calculation method
- `getValue(SearchSnapshot): ?float` — gets a relevance value from feedback grades
- `getRelevanceValues(EvaluationKeyword, int $limit): array` — retrieves position-indexed relevance values
- Integrates with `Transformers` for cross-scale grade conversion

#### `ScorerFactory`
Factory for creating scorer instances by type string. Registered scorer types:

| Type Key | Scorer Class | Scale | Description |
|---|---|---|---|
| `precision` | `PrecisionScorer` | Binary | Precision@K |
| `ap` | `AveragePrecisionScorer` | Binary | Average Precision (MAP when averaged) |
| `rr` | `ReciprocalRankScorer` | Binary | Reciprocal Rank (MRR when averaged) |
| `cg` | `CumulativeGainScorer` | Graded | Cumulative Gain |
| `dcg` | `DiscountedCumulativeGainScorer` | Graded | Discounted Cumulative Gain |
| `ndcg` | `NormalizedDiscountedCumulativeGainScorer` | Graded | Normalized DCG |
| `cg_d` | `CumulativeGainDetailScorer` | Detail | Cumulative Gain (Detail scale) |
| `dcg_d` | `DiscountedCumulativeGainDetailScorer` | Detail | DCG (Detail scale) |
| `ndcg_d` | `NormalizedDiscountedCumulativeGainDetailScorer` | Detail | NDCG (Detail scale) |

### 7.2 Scales (`app/Services/Scorers/Scales/`)

#### `Scale` (Abstract)
Base class for grading scales. Provides:
- `getValues(): array` — maps grade integers to labels
- `getValue(array $grades): ?float` — computes prevailing/average value from multiple grades
- `getShortcuts(): array` — keyboard shortcuts for grade buttons

#### Scale Implementations

| Scale | Type Key | Grades | Aggregation Method |
|---|---|---|---|
| **BinaryScale** | `binary` | 0=Irrelevant, 1=Relevant | Majority vote (null if tied) |
| **GradedScale** | `graded` | 0=Poor, 1=Fair, 2=Good, 3=Perfect | Average |
| **DetailScale** | `detail` | 1–10 | Average |

#### `ScaleFactory`
Creates scale instances from type strings.

### 7.3 Mapper (`app/Services/Mapper/`)

#### `MapperInterface`
Interface for response mappers: `initialize()`, `validate()`, `getDocuments()`, `getError()`.

#### `DotArrayMapper`
Primary mapper implementation that extracts documents from JSON API responses using a custom expression language:

**Mapper Code Syntax:**
```
data: response.items.*
id: data.product_id
name: data.title
image: data.thumbnail_url
custom_field: data.some_nested.value
```

- `data` — defines the array path in the JSON response (supports `*` wildcards)
- `id` (required) — document identifier expression
- `name` (required) — document display name expression
- `image` (optional) — document image URL expression
- Additional custom attributes can be mapped

Uses Symfony ExpressionLanguage for expression evaluation. Validates that required attributes (`id`, `name`) are present.

#### `Document`
DTO representing an extracted search result document with `id`, `name`, `image`, `position`, and arbitrary attributes.

#### `MapperFactory`
Creates mapper instances by type.

### 7.4 Evaluations (`app/Services/Evaluations/`)

#### `UserFeedbackService`
Manages feedback assignment for evaluators:
- `fetch(User, ?SearchEvaluation): ?UserFeedback` — fetches and assigns the next ungraded feedback item
- Uses a **5-minute lock timeout** for feedback assignments
- Supports **global pool** (all team evaluations) and **evaluation-specific pool**
- Respects evaluation tags for filtering eligible evaluators
- `getUngradedSnapshotsCountCached(User): int` — cached count with Redis tag-based cache invalidation

#### `ReuseStrategyService`
Reuses relevance judgments from previous evaluations:
- **Query-Doc strategy** — reuses grades if the same keyword + doc_id appeared before
- **Query-Doc-Position strategy** — reuses grades only if keyword + doc_id + position all match
- Reuses both human (`user_id`) and AI judge (`judge_id`) grades
- Respects evaluation tags (reuses only matching-tag users/judges)
- Avoids duplicate assignment of the same user or the same judge within one snapshot (feedback strategy=3)

#### `ScoringGuidelinesService`
Provides scoring guidelines templates for evaluators.

#### `JudgementsService`
Exports evaluation judgments in a structured format for the API.

### 7.5 Models (`app/Services/Models/`)

#### `ExecuteModelService`
Executes search queries against configured models:
- `initialize(SearchModel): self` — sets up mapper and endpoint configuration
- `execute(string $query): ModelExecutionResult` — sends HTTP request and parses response
- Substitutes `#query#` in query params and body with the actual search keyword
- Configurable timeouts (15s response, 10s connect)
- Returns a `ModelExecutionResult` DTO containing HTTP status, documents, and response content

### 7.6 Transformers (`app/Services/Transformers/`)

#### `Transformers`
Handles **cross-scale grade transformation** when metrics require a different scale than the evaluation's native scale:
- Configurable mapping rules between scales (e.g., Binary → Graded)
- `transform(string $toScaleType, int $value): int` — applies transformation
- Ships with default transformation mappings for all scale combinations
- Serializable to/from arrays and form data

### 7.7 Widgets (`app/Services/WidgetsService`)
Manages user dashboard widgets:
- Default widgets: `GiveFeedbackWidget`, `LeaderboardWidget`, `TeamsWidget`
- `getUserWidgets(User): Collection` — returns user's widgets or defaults
- `attachWidget(User, string $widgetClass, array $settings): UserWidget` — adds a widget

### 7.8 Judges (`app/Services/Judges/`)

#### `JudgeHandlerFactory`
Creates provider handlers for judge API requests:
- Native handlers: OpenAI, Anthropic, Google
- OpenAI-compatible handler: DeepSeek, xAI, Groq, Mistral, Custom, Ollama

#### `OpenAiCompatibleJudgeHandler`
- Sends chat-completions requests to OpenAI-compatible endpoints
- Supports custom base URL (`custom_openai`)
- Normalizes Ollama host/base URLs to `/v1/chat/completions`

#### `JudgeParamsService`
- Parses text params (`key: value`) into typed arrays (`int`, `float`, `bool`, `null`, `string`)
- Converts params arrays back to textarea format for the UI

---

## 8. Actions

Actions encapsulate business logic and are located in `app/Actions/`. They follow the Single Responsibility Principle.

### 8.1 Evaluation Actions (`app/Actions/Evaluations/`)

| Action | Description |
|---|---|
| `CreateSearchEvaluation` | Creates a new evaluation for a model |
| `StartSearchEvaluation` | Starts an evaluation — dispatches keyword processing jobs as a batch |
| `PauseSearchEvaluation` | Pauses an active evaluation |
| `FinishSearchEvaluation` | Finishes an evaluation — stops accepting feedback, finalizes metrics |
| `DeleteSearchEvaluation` | Deletes an evaluation and its associated data |
| `UpdateSearchEvaluation` | Updates evaluation properties |
| `ArchiveSearchEvaluation` | Toggles evaluation archive status |
| `PinSearchEvaluation` | Toggles evaluation pinned status |
| `BaselineSearchEvaluation` | Sets/unsets an evaluation as the team baseline |
| `GradeSearchEvaluation` | Handles evaluation grading operations |
| `GradeSearchSnapshot` | Records a grade for a specific snapshot |
| `ResetSearchSnapshot` | Resets feedback for a snapshot |
| `ResetUserFeedback` | Resets a specific user's feedback |
| `RecalculateMetrics` | Recalculates metric values for an evaluation |
| `ExportSearchEvaluation` | Exports evaluation data |
| `AutoRestartSearchEvaluation` | Handles auto-restart logic for completed evaluations |

### 8.2 Endpoint Actions (`app/Actions/Endpoints/`)

| Action | Description |
|---|---|
| `CreateSearchEndpoint` | Creates a new search endpoint |
| `UpdateSearchEndpoint` | Updates endpoint configuration |
| `DeleteSearchEndpoint` | Deletes an endpoint |
| `ToggleSearchEndpointActive` | Activates/deactivates an endpoint |

### 8.3 Judge Actions (`app/Actions/Judges/`)

| Action | Description |
|---|---|
| `CreateJudge` | Creates a new LLM judge with prompts and settings |
| `UpdateJudge` | Updates judge configuration (preserves API key if blank) |
| `DeleteJudge` | Deletes a judge |
| `ToggleJudgeActive` | Archives/unarchives a judge |

### 8.4 Model Actions (`app/Actions/Models/`)

| Action | Description |
|---|---|
| `CreateSearchModel` | Creates a new search model |
| `UpdateSearchModel` | Updates model configuration |
| `DeleteSearchModel` | Deletes a search model |

---

## 9. Jobs (Queue)

All evaluation jobs are in `app/Jobs/Evaluations/` and implement `ShouldQueue` and `ShouldBeUnique`.

### 9.1 `StartEvaluationJob`
- Dispatches evaluation start via `StartSearchEvaluation` action
- Unique by evaluation ID
- On failure: allows changes to the evaluation

### 9.2 `ProcessKeywordJob`
- Executes a single keyword against the search model endpoint
- Uses `ExecuteModelService` to make HTTP requests and parse results
- Creates `SearchSnapshot` records from parsed documents
- Up to 3 attempts (2 retries)
- Part of a `Bus::batch()` for parallel keyword processing

### 9.3 `RecalculateMetricsJob`
- Triggered after feedback is submitted
- Recalculates metric values for a keyword using all configured scorers
- Creates/updates `KeywordMetric` and `MetricValue` records

### 9.4 `FinishEvaluationJob`
- Finalizes evaluation — sets status to Finished
- Dispatched when all keywords in a batch are processed

### 9.5 `UpdatePreviousValuesJob`
- Updates `previous_value` for metrics when evaluations change status or archive state
- Ensures metric change indicators reflect the correct previous evaluation

### 9.6 `PostStartEvaluationJob`
- Runs after keyword processing starts
- Applies configured reuse strategy
- Dispatches judge processing when matching active judges exist

### 9.7 `ProcessJudgeEvaluationJob`
- Executes AI-judge grading on the dedicated `judges` queue
- Processes judges in round-robin cycles
- Uses DB-level locking to claim feedback slots
- Enforces one grade per judge per snapshot (same as human uniqueness rule)
- Handles mixed lock-expiry scenarios by re-dispatching with delay when needed

### Queue Configuration (Horizon)
- **`supervisor-1`**: Processes `default` and `snapshots-auto` queues (6 workers)
- **`supervisor-2`**: Processes `snapshots-single` queue (1 worker, serial execution)
- **`supervisor-3`**: Processes `judges` queue (2 workers)
- Memory limit: 512 MB per worker
- Timeout: 60 seconds (`supervisor-1/2`), 300 seconds (`supervisor-3`)

---

## 10. Events & Notifications

### 10.1 Events (`app/Events/`)

All events implement `ShouldBroadcast` and broadcast to team-specific private channels:

| Event | Trigger | Channel |
|---|---|---|
| `EvaluationStatusChangedEvent` | Evaluation status changes | `team.{id}` |
| `EvaluationProgressChangedEvent` | Evaluation progress updates | `team.{id}` |
| `EvaluationScaleTypeChangedEvent` | Scale type changes | `team.{id}` |
| `EvaluationArchivedChangedEvent` | Archive status toggles | `team.{id}` |
| `EvaluationFeedbackChangedEvent` | Feedback is submitted | `team.{id}` |
| `EvaluationMetricChangedEvent` | Metric values recalculated | `team.{id}`, `metric.{id}` |
| `BaselineEvaluationChangedEvent` | Team baseline changes | `team.{id}` |
| `UserTagsChangedEvent` | User tag assignments change | `user.{id}` |

### 10.2 Notifications (`app/Notifications/`)

| Notification | Type | Description |
|---|---|---|
| `EvaluationFinishNotification` | Mail/Database | Sent when an evaluation finishes |
| `MessageNotification` | Mail/Database | Team message notification |
| `SystemNotification` | Mail/Database | System-level notification |
| `TeamInvitationNotification` | Mail | Sent when a user is invited to a team |
| `ResetPassword` | Mail | Password reset notification |

All mail notifications implement `MailUnsubscribable` interface for opt-out support.

---

## 11. API Reference

The REST API is available at `/api/v1/` and requires **Sanctum token authentication**.

### 11.1 Evaluations

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/evaluations` | List evaluations (filterable by status, model, search) |
| `GET` | `/api/v1/evaluations/{id}` | Show evaluation details with metrics and keywords |
| `POST` | `/api/v1/evaluations` | Create a new evaluation |
| `POST` | `/api/v1/evaluations/{id}/start` | Start an evaluation |
| `POST` | `/api/v1/evaluations/{id}/stop` | Pause an evaluation |
| `POST` | `/api/v1/evaluations/{id}/finish` | Finish an evaluation |
| `DELETE` | `/api/v1/evaluations/{id}` | Delete an evaluation |
| `GET` | `/api/v1/evaluations/{id}/judgements` | Export evaluation judgments |

### 11.2 Models

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/models` | List search models |
| `GET` | `/api/v1/models/{id}` | Show model details |

### 11.3 Authentication
API requests require an `Authorization: Bearer {token}` header. Tokens are managed via `manage_api_token` permission in team settings.

---

## 12. Web Routes & Livewire Components

### 12.1 Main Web Routes (`routes/web.php`)

| Path | Livewire Component | Description |
|---|---|---|
| `/dashboard` | `Dashboard` | User dashboard with widgets |
| `/evaluations` | `Evaluations` | Evaluation listing |
| `/evaluations/{evaluation}` | `Evaluation` | Evaluation detail view |
| `/judges` | `Judges` | LLM judge management |
| `/judges/logs` | `JudgeLogs` | Global judge request logs with filter-aware JSONL export |
| `/judges/{judge}/logs` | `JudgeLogs` | Per-judge request logs with filter-aware JSONL export |
| `/models` | `Models` | Search model listing |
| `/models/{model}` | `Model` | Model detail view |
| `/endpoints` | `Endpoints` | Endpoint management |
| `/feedback/{evaluationId?}` | `GiveFeedback` | Feedback grading interface |
| `/evaluations/{evaluation}/feedback` | `Feedbacks` | Feedback browsing for evaluation |
| `/leaderboard` | `Leaderboard` | Evaluator leaderboard |
| `/teams` | `Teams` | Team management |
| `/teams/current` | `CurrentTeam` | Team settings |
| `/admin/users` | `Users` | Superuser admin panel |
| `/user/profile` | `UserProfile` | User profile settings |

### 12.2 Key Livewire Components (`app/Livewire/`)

| Component | Description |
|---|---|
| `Dashboard` | Customizable widget dashboard |
| `Evaluations` | Filterable evaluation list with bulk actions |
| `Evaluation` | Full evaluation detail: keywords, snapshots, metrics, feedback |
| `Models` | Search model management with endpoint testing |
| `Model` | Model detail with configuration and evaluation history |
| `Judges` | LLM judge configuration (providers, prompts, batch size, model params, base URL, tags) with reactive status/pairs-judged indicators and segmented status filter (`All/Active/Archived`) |
| `JudgeLogs` | Filtering and inspection of judge provider requests/responses with JSONL export of the current filtered dataset |
| `Endpoints` | CRUD for search endpoints with mapper code editor and segmented status filter (`All/Active/Archived`) |
| `GiveFeedback` | Main evaluator UI for grading search results |
| `Feedbacks` | Admin view of all feedback activity |
| `Leaderboard` | Rankings with `All | Users | Judges` modes and mixed dataset charting |
| `Teams` | Team listing and creation |
| `CurrentTeam` | Team settings, members, invitations, baseline |
| `TeamMemberManager` | Add/remove team members, change roles |
| `TeamsDropdown` | Team switcher dropdown in navbar |
| `DropdownNotifications` | Notification bell dropdown |

### 12.3 Livewire Sub-Components (`app/Livewire/Evaluations/`)
Dedicated sub-components for evaluation management: keywords list, metrics display, snapshot grading, feedback overview, settings panels, export dialogs, etc.

### 12.4 Widget Components (`app/Livewire/Widgets/`)
- `GiveFeedbackWidget` — quick access to ungraded feedback
- `LeaderboardWidget` — mini leaderboard display
- `TeamsWidget` — team overview
- `EvaluationWidget` — evaluation summary card
- `EvaluationProgressWidget` — progress tracking card

### 12.5 Forms (`app/Livewire/Forms/`)
Livewire form objects for validation and persistence: `EndpointForm`, `ModelForm`, `EvaluationForm`, `JudgeForm`.

### 12.6 Traits (`app/Livewire/Traits/`)
Reusable traits for Livewire components: pagination, filtering, sorting, team scoping, tag management, judge editing, etc. (15 traits total)

---

## 13. Permissions & Roles

### 13.1 Permissions (`app/Policies/Permissions`)

| Permission Constant | Key | Description |
|---|---|---|
| `PERMISSION_MANAGE_TEAM` | `manage_team` | Edit team, manage members |
| `PERMISSION_MANAGE_API_TOKEN` | `manage_api_token` | Create/revoke API tokens |
| `PERMISSION_VIEW_TEAM_SUBSCRIPTION` | `view_team_subscription` | View subscription info |
| `PERMISSION_SEND_TEAM_MESSAGES` | `send_team_messages` | Send messages to team |
| `PERMISSION_MANAGE_SEARCH_ENDPOINTS` | `manage_search_endpoints` | CRUD endpoints |
| `PERMISSION_MANAGE_SEARCH_MODELS` | `manage_search_models` | CRUD models |
| `PERMISSION_MANAGE_SEARCH_EVALUATIONS` | `manage_search_evaluations` | CRUD evaluations |
| `PERMISSION_MANAGE_USER_FEEDBACK` | `manage_user_feedback` | Admin feedback management |
| `PERMISSION_GIVE_USER_FEEDBACK` | `give_user_feedback` | Grade search results |
| `PERMISSION_VIEW_LEADERBOARD` | `view_leaderboard` | View leaderboard |
| `PERMISSION_MANAGE_JUDGES` | `manage_judges` | CRUD LLM judges |

### 13.2 Roles (`app/Policies/Roles`)

| Role | Key | Permissions |
|---|---|---|
| **Administrator** | `admin` | All permissions |
| **Evaluator** | `evaluator` | `give_user_feedback`, `view_leaderboard` only |

Team **owners** have all permissions implicitly. **Super admins** (`super_admin` flag on User) have platform-wide access.

---

## 14. Broadcasting & Real-Time Updates

### 14.1 WebSocket Server
- **Laravel Reverb** runs on port 8080 (configurable)
- Frontend connects via **Laravel Echo** + WebSocket driver

### 14.2 Broadcast Channels (`routes/channels.php`)

| Channel | Authorization |
|---|---|
| `App.Models.User.{id}` | User ID match |
| `team.{teamId}` | User belongs to team |
| `metric-value.{metricId}` | User belongs to team of metric's evaluation |
| `search-evaluation.{evaluationId}` | User belongs to team of evaluation |

### 14.3 Auto-Broadcasting
The `TeamBroadcastableModel` base class automatically broadcasts model CRUD events to team channels. Models extending this class:
- `SearchEvaluation`
- `SearchEndpoint`
- `SearchModel`
- `Judge`

`MetricValue` uses Laravel's built-in `BroadcastsEvents` trait for metric value chart updates.

---

## 15. Scoring & Metrics System

### 15.1 Evaluation Flow

```
1. Evaluation Created (Pending)
      │
2. Admin Starts Evaluation
      │
      ├─► StartEvaluationJob
      │     ├─► Creates EvaluationKeyword records
      │     ├─► Creates EvaluationMetric records (from configured scorers)
      │     ├─► Dispatches ProcessKeywordJob batch
      │     └─► Applies ReuseStrategy (if configured)
      │
      ├─► ProcessKeywordJob (per keyword, parallel)
      │     ├─► ExecuteModelService calls API
      │     ├─► DotArrayMapper extracts documents
      │     ├─► Creates SearchSnapshot records
      │     └─► Creates UserFeedback slots
      │
      ├─► Batch Completion
      │     ├─► FinishEvaluationJob (or Auto-Restart)
      │     └─► RecalculateMetricsJob
      │
3. Evaluators Grade Results
      │
      ├─► UserFeedbackService.fetch()  (assigns feedback)
      ├─► GradeSearchSnapshot action   (records grade)
      ├─► RecalculateMetricsJob        (updates metrics)
      └─► EvaluationFeedbackChangedEvent (broadcasts)
      │
4. Admin Finishes Evaluation
      │
      └─► FinishSearchEvaluation action
            └─► UpdatePreviousValuesJob
```

### 15.2 Metric Calculations

Each scorer implements the `calculate(EvaluationKeyword, int $limit): ?float` method:

- **Precision@K** — fraction of relevant documents in top-K results
- **Average Precision (AP)** — average of precision at each relevant position
- **Reciprocal Rank (RR)** — 1/position of first relevant result
- **Cumulative Gain (CG)** — sum of relevance grades in top-K
- **Discounted CG (DCG)** — CG with logarithmic position discounting
- **Normalized DCG (NDCG)** — DCG / ideal DCG

Metrics that use Graded scale (`cg`, `dcg`, `ndcg`) and Detail scale (`cg_d`, `dcg_d`, `ndcg_d`) use grade averages. Binary metrics (`precision`, `ap`, `rr`) use majority vote from multiple feedback grades.

---

## 16. Scales & Transformers

### 16.1 Scale System

Evaluations must choose a **scale type** that determines how evaluators grade results:

| Scale | Values | Use Case |
|---|---|---|
| **Binary** | Irrelevant (0), Relevant (1) | Simple binary relevance |
| **Graded** | Poor (0), Fair (1), Good (2), Perfect (3) | 4-level relevance |
| **Detail** | 1 through 10 | Fine-grained 10-point scale |

### 16.2 Transformer System

Since different metrics require different scales, **Transformers** allow cross-scale grade conversion. For example, if an evaluation uses Binary scale but includes an NDCG metric (which requires Graded scale), the transformer maps:

```
Binary → Graded:
  Irrelevant (0) → Poor (0)
  Relevant (1)   → Perfect (3)
```

Default transformation rules are provided for all 6 scale combinations. Rules are customizable per evaluation.

---

## 17. Mapper System

The mapper system translates raw search API responses into structured `Document` objects.

### 17.1 DotArrayMapper

The primary mapper uses a **YAML-like syntax** with dot-notation paths and Symfony ExpressionLanguage:

```
data: response.hits.*
id: data.id
name: data.title
image: data.images.0
category: data.category_name
price: data.price
```

**How it works:**
1. The JSON response is flattened using Laravel's `Arr::dot()`
2. `data` defines the array wildcard pattern
3. Each attribute maps to an expression that resolves against the flattened data
4. Required attributes: `id` and `name`
5. Documents without valid `id` and `name` are filtered out
6. Documents are limited to the configured `num_results` count

---

## 18. Widgets System

The dashboard supports **customizable widgets** per user per team:

### Default Widgets
1. **GiveFeedbackWidget** — shows ungraded feedback count with quick-access link
2. **LeaderboardWidget** — mini evaluator ranking
3. **TeamsWidget** — overview of user's teams

### User-Attachable Widgets
4. **EvaluationWidget** — summary of a specific evaluation's metrics
5. **EvaluationProgressWidget** — real-time progress of an evaluation

Widgets support:
- **Positioning** — drag-and-drop reordering
- **Visibility** — show/hide toggle
- **Settings** — widget-specific configuration (e.g., evaluation ID)
- **Auto-creation** — default widgets are created on first dashboard visit

---

## 19. Tags System

Tags provide flexible categorization across multiple entity types:

### Entity Types Supporting Tags
- **Evaluations** (`evaluation_tags` pivot)
- **Models** (`model_tags` pivot)
- **Users** (`user_tags` pivot)
- **Judges** (`judge_tags` pivot)

### Tag Properties
- Team-scoped (each team manages its own tags)
- Named with a configurable **color** (predefined color palette with CSS classes)

### Tag-Based Features
- **Evaluation Filtering** — filter evaluation lists by tags
- **Feedback Routing** — when an evaluation has tags, only users with matching tags can provide feedback
- **Reuse Strategy Filtering** — only reuses grades from users with matching tags

---

## 20. Testing

### Test Structure
```
tests/
├── CreatesApplication.php
├── TestCase.php
├── Feature/                              # 40 feature tests
│   ├── Actions/
│   │   ├── Evaluations/                  # 7 evaluation action tests
│   │   └── Users/                        # 1 user action test
│   ├── Http/Controllers/Api/             # 2 API controller tests
│   ├── Livewire/                         # 4 Livewire component tests
│   ├── Services/                         # 10+ service tests
│   ├── AuthenticationTest.php
│   ├── RegistrationTest.php
│   ├── PasswordResetTest.php
│   ├── PasswordConfirmationTest.php
│   ├── Create/Delete/Update TeamTest.php
│   ├── ApiToken*Test.php
│   └── ...                               # Other Jetstream/auth tests
└── Unit/                                 # 29 unit tests
    ├── Actions/Evaluations/              # Auto-restart tests
    ├── DTO/                              # DTO tests
    ├── Models/                           # Judge, SearchEvaluation tests
    ├── Rules/                            # Validation rule tests
    └── Services/                         # Scorers, mapper, transformers, etc.
```

### Running Tests
```bash
# Via Makefile (recommended — clears config cache first)
cd devops
make test

# Via Docker directly
cd devops
docker compose run --rm artisan test

# Specific test file
docker compose run --rm artisan test --filter=AuthenticationTest

# Test suite
docker compose run --rm artisan test --testsuite=Unit
docker compose run --rm artisan test --testsuite=Feature
```

> **Note:** If `artisan config:cache` has been run (e.g., via `make bootstrap`), the cached config overrides `phpunit.xml` env variables. Use `make test` or run `artisan config:clear` before running tests directly.

---

## 21. Development Environment

### 21.1 Prerequisites
- Docker & Docker Compose
- Git

### 21.2 Setup Steps

```bash
# 1. Clone the repository
git clone https://github.com/afedukov/searchtweak.git
cd searchtweak

# 2. Copy environment file
cp .env.dist .env

# 3. Add to /etc/hosts
# 127.0.0.1 searchtweak.local

# 4. Start and bootstrap
cd devops
make                    # runs `make start` then `make bootstrap` (destructive fresh bootstrap)

# Alternative safe bootstrap (keeps existing local DB data)
make bootstrap-up

# 5. Start Vite dev server (separate terminal)
make vite
```

### 21.3 Docker Services

| Service | Description | Internal Host |
|---|---|---|
| **traefik** | Reverse proxy, routes `searchtweak.local` | — |
| **nginx** | Web server | `searchtweak-nginx` |
| **php** | PHP-FPM 8.3 | `searchtweak-php` |
| **db** | MySQL 8.0 | `searchtweak-db` |
| **phpmyadmin** | DB admin UI (port 8082) | `searchtweak-phpmyadmin` |
| **redis** | Redis server | `searchtweak-redis` |
| **crontab** | Laravel scheduler | — |
| **queue** | Horizon queue workers | — |
| **reverb** | WebSocket server (port 8080) | `searchtweak-reverb` |
| **mailhog** | Email testing (port 8025) | `searchtweak-mailhog` |

### 21.4 Makefile Commands

```bash
make start      # Start all Docker services
make stop       # Stop all Docker services
make bootstrap  # Destructive bootstrap: composer install, key generate, migrate:fresh --seed, assets/docs build
make bootstrap-up # Safe bootstrap: composer install, key generate, migrate --seed, assets/docs build
make bootstrap-fresh # Explicit destructive bootstrap (same as make bootstrap)
make test       # Clear config cache and run tests
make seed       # Reset database with fresh migrations and seeders
make jobs       # Start Horizon (queue worker)
make queue-reload # Reload Horizon in running queue container (horizon:terminate)
make reverb     # Start Reverb (WebSocket server)
make vite       # Start Vite dev server (hot reload)
make vite-prod  # Build production assets
make docs       # Start VitePress docs dev server (port 3001)
make docs-install # Install docs dependencies
make docs-build # Build docs site
make docs-preview # Preview docs build (port 3001)
make docs-publish # Build and publish docs into public/docs
```

Bootstrap script behavior by environment:
- In `APP_ENV=production`, Laravel caches are warmed (`route/config/view:cache`) and Horizon is terminated to reload workers.
- In non-production environments, caches are cleared but not warmed, and Horizon terminate is skipped.

### 21.5 Database Seeders

The `make seed`, `make bootstrap`, and `make bootstrap-fresh` commands run seeders in this order:

1. **SearchDataSeeder** — Creates Metro Markets endpoint and Baseline Search model with keywords
2. **EvaluationSeeder** — Creates a graded evaluation with P@10, CG@10, DCG@10 metrics
3. **JudgeSeeder** — Creates 3 LLM judges (OpenAI gpt-4o, Anthropic Claude Sonnet, Google Gemini Flash)

Default login: `admin@searchtweak.com` / `12345678`

### 21.6 Helper Docker Commands

```bash
# Composer
docker compose -f devops/docker-compose.yml run --rm composer install

# Artisan
docker compose -f devops/docker-compose.yml run --rm artisan migrate

# NPM
docker compose -f devops/docker-compose.yml run --rm npm install
```

### 21.7 Access Points

| URL | Service |
|---|---|
| `http://searchtweak.local` | Application |
| `http://searchtweak.local/docs` | VitePress Documentation (static, served by Nginx) |
| `http://searchtweak.local:8888` | phpMyAdmin |
| `http://searchtweak.local:8025` | MailHog |
| `http://searchtweak.local/horizon` | Horizon Dashboard |

---

## 22. Helper Functions

### `unique_key(): string`
Defined in `app/helpers.php`. Generates a unique string identifier using `uniqid()` with extra entropy. Used for Livewire component keys, cache entries, and other non-security-critical identifiers.

> **Note**: Not cryptographically secure — do not use for passwords, tokens, or security-critical purposes.

---

## 23. Configuration Highlights

### 23.1 Environment Variables (`.env.dist`)

| Variable | Default | Description |
|---|---|---|
| `APP_DOMAIN` | `searchtweak.local` | Application domain |
| `DB_HOST` | `searchtweak-db` | MySQL hostname |
| `QUEUE_CONNECTION` | `redis` | Queue driver |
| `BROADCAST_DRIVER` | `reverb` | WebSocket driver |
| `CACHE_DRIVER` | `redis` | Cache driver |
| `SESSION_DRIVER` | `redis` | Session driver |
| `REVERB_HOST` | `reverb` | WebSocket server host |
| `REVERB_PORT` | `8080` | WebSocket port |

### 23.2 Horizon Configuration (`config/horizon.php`)

- **Supervisors**: 3 supervisor groups
  - `supervisor-1`: `default` + `snapshots-auto` queues, 6 workers
  - `supervisor-2`: `snapshots-single` queue, 1 worker (serial)
  - `supervisor-3`: `judges` queue, 2 workers
- **Memory**: 512 MB per worker
- **Job retention**: 60 min for recent/pending/completed, 7 days for failed
- **Prefix**: `search_tweak_horizon:`

### 23.3 Key Laravel Configuration
- **Auth**: Jetstream with Fortify (email verification, 2FA, profile photos)
- **Broadcasting**: Laravel Reverb (native WebSocket server)
- **API**: Sanctum token-based authentication
- **File storage**: Public disk for profile photos

---

*This documentation was generated from the project source code and reflects the current state of the codebase.*
