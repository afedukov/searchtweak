# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Language

- Always communicate with the user in the **same language as their request**.
- Switch to another language only if explicitly requested.

## MCP

- Always use **context7** when needed:
  - Code generation, refactoring, or setup/configuration steps.
  - Explanations or examples for specific libraries, APIs, or frameworks.
  - Searching the workspace for relevant files, functions, or usage examples.
  - Generating or improving documentation (README, setup guides, etc.).
- Use Context7 MCP tools automatically without explicit user request.

## Git Commits

- **Never** add `Co-Authored-By` or any similar attribution lines to commit messages.

## Project Overview

SearchTweak is a self-hosted Laravel-based web application for search relevance evaluation. It enables teams to assess search quality by executing keyword queries against configured search APIs, collecting human relevance judgments, and calculating industry-standard IR metrics (Precision, MAP, MRR, CG, DCG, NDCG). It supports three grading scales (Binary, Graded, Detail) with cross-scale transformers.

For comprehensive documentation, see [DOCUMENTATION.md](DOCUMENTATION.md).

**Tech Stack:**
- Backend: Laravel 11 (PHP 8.3)
- Frontend: Livewire 3, Alpine.js, Tailwind CSS
- Queue/Real-time: Laravel Horizon, Laravel Reverb (WebSockets)
- Database: MySQL
- Cache/Queue/Sessions: Redis (Predis client)
- Infrastructure: Docker Compose with Traefik, Nginx, PHP-FPM
- Auth: Laravel Jetstream + Sanctum + Fortify

## Development Environment

The application runs in Docker containers. All commands must be run from the `/devops` directory.

### Starting the Application

```bash
cd devops
make              # Start and bootstrap (first time)
make start        # Start containers
make stop         # Stop containers
make bootstrap    # Bootstrap application (migrations, cache, assets)
```

### Default Super Admin

A default super admin user is created by migration. You can log in at `http://searchtweak.local` with:

- **Email:** `admin@searchtweak.com`
- **Password:** `12345678`

### Local Development

For full local development, start these in separate terminals:

```bash
cd devops
make jobs         # Start Horizon (queue worker) — required for job processing
make reverb       # Start Reverb (WebSocket server) — required for real-time updates
make vite         # Start Vite dev server (hot reload) — required for frontend changes
```

### Frontend Production Build

```bash
cd devops
make vite-prod    # Build for production
```

### Running Artisan Commands

```bash
cd devops
docker compose run --rm artisan <command>

# Examples:
docker compose run --rm artisan migrate
docker compose run --rm artisan tinker
docker compose run --rm artisan queue:work
docker compose run --rm artisan horizon:terminate
```

### Running Composer Commands

```bash
cd devops
docker compose run --rm composer <command>

# Examples:
docker compose run --rm composer install
docker compose run --rm composer update
docker compose run --rm composer require package/name
```

### Running Tests

```bash
cd devops
docker compose run --rm artisan test                    # Run all tests
docker compose run --rm artisan test --filter TestName  # Run specific test
```

## Architecture

### Core Domain Concepts

1. **Search Endpoints** (`app/Models/SearchEndpoint.php`)
   - Define how to connect to search APIs (URL, HTTP method, headers)
   - Configured with mapper code to extract data from JSON responses
   - Can be active or archived; team-scoped

2. **Mapper Code** (`app/Services/Mapper/`)
   - `DotArrayMapper` uses dot notation and Symfony ExpressionLanguage to extract documents from JSON responses
   - **Required attributes**: `id`, `name`. Optional: `image`, custom attributes
   - Syntax: `data: response.items.*`, `id: data.product_id`, `name: data.title`
   - Uses `*` wildcard for array iteration in JSON paths
   - Core implementation: `DotArrayMapper.php`, interface: `MapperInterface.php`

3. **Search Models** (`app/Models/SearchModel.php`)
   - Represent specific search configurations linked to an endpoint
   - Define headers, query parameters (`params`), and request body
   - Support `#query#` placeholder for keyword substitution in params and body
   - Team-scoped; implement `TaggableInterface`

4. **Search Evaluations** (`app/Models/SearchEvaluation.php`)
   - Status lifecycle: **Pending** (0) → **Active** (1) → **Finished** (2)
   - Execute keyword queries via `ProcessKeywordJob` batch
   - Collect human relevance feedback via `UserFeedback` records
   - Support **scale types**: `binary`, `graded`, `detail`
   - Settings: feedback strategy, reuse strategy, auto-restart, scoring guidelines, transformers
   - Can be archived, pinned, or set as team baseline
   - Extend `TeamBroadcastableModel` for real-time broadcasting

5. **Evaluation Metrics** (`app/Services/Scorers/`)
   - Calculate search quality metrics via `Scorer` subclasses
   - 9 scorer types: `precision`, `ap`, `rr`, `cg`, `dcg`, `ndcg`, `cg_d`, `dcg_d`, `ndcg_d`
   - Factory pattern: `ScorerFactory::create('ndcg')`
   - Support 3 scales: Binary (majority vote), Graded (4-level average), Detail (10-point average)
   - Cross-scale transformation via `Transformers` class

6. **Search Snapshots** (`app/Models/SearchSnapshot.php`)
   - Store individual search result documents per keyword
   - Contain `doc_id`, `name`, `image`, `position`, and full `doc` JSON
   - Created from `Document` DTOs via `createFromDocument()`

7. **User Feedback** (`app/Models/UserFeedback.php`)
   - Human relevance judgments for snapshots
   - Assignment lock timeout: 5 minutes (`UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES`)
   - Global pool (all team evaluations) and evaluation-specific pools
   - Tag-based filtering (only matching-tag users can provide feedback)

### Actions Pattern

Business logic is encapsulated in Action classes (`app/Actions/`):

- **Evaluations** (`app/Actions/Evaluations/`): 16 actions covering full lifecycle
  - Key: `StartSearchEvaluation`, `FinishSearchEvaluation`, `GradeSearchSnapshot`, `RecalculateMetrics`
- **Endpoints** (`app/Actions/Endpoints/`): CRUD + toggle active
- **Models** (`app/Actions/Models/`): CRUD operations

### Job Queue Architecture

Laravel Horizon manages queues with 2 supervisors:
- **supervisor-1**: `default` + `snapshots-auto` queues, 6 workers
- **supervisor-2**: `snapshots-single` queue, 1 worker (serial execution)

Key jobs (`app/Jobs/Evaluations/`):
- `StartEvaluationJob`: Creates keywords, metrics, dispatches ProcessKeyword batch
- `ProcessKeywordJob`: Executes search API call, creates snapshots (3 attempts max)
- `RecalculateMetricsJob`: Recalculates metrics after feedback changes
- `FinishEvaluationJob`: Finalizes evaluation status
- `UpdatePreviousValuesJob`: Updates baseline comparison values

All jobs implement `ShouldBeUnique` (unique by entity ID).

### Key Services

- `ExecuteModelService` (`app/Services/Models/`): HTTP client for search API execution via GuzzleHTTP
- `UserFeedbackService` (`app/Services/Evaluations/`): Feedback assignment with lock-based pooling
- `ReuseStrategyService` (`app/Services/Evaluations/`): Reuses historical judgments (query-doc or query-doc-position)
- `WidgetsService` (`app/Services/`): Dashboard widget management
- `ScorerFactory` (`app/Services/Scorers/`): Creates scorer instances
- `ScaleFactory` (`app/Services/Scorers/Scales/`): Creates scale instances
- `MapperFactory` (`app/Services/Mapper/`): Creates mapper instances

### Livewire Components

UI is built with Livewire 3 components in `app/Livewire/`:
- `Dashboard.php`: Customizable widget dashboard
- `Evaluations.php` / `Evaluation.php`: Evaluation list and detail
- `Models.php` / `Model.php`: Search model management
- `Endpoints.php`: Search endpoint configuration
- `GiveFeedback.php`: Evaluator grading interface
- `Feedbacks.php`: Admin feedback overview
- `Leaderboard.php`: Evaluator performance ranking
- `Teams.php` / `CurrentTeam.php`: Team management
- Sub-components in `app/Livewire/Evaluations/` (19 components)
- Widget components in `app/Livewire/Widgets/` (8 components)
- Reusable traits in `app/Livewire/Traits/` (14 traits)

### Real-time Updates

Laravel Reverb handles WebSocket connections for real-time updates:
- Models extending `TeamBroadcastableModel` auto-broadcast CRUD events
- `MetricValue` broadcasts to metric-specific channels for live chart updates
- Events in `app/Events/` (8 events): status, progress, scale, archive, feedback, metric, baseline, user tags
- Broadcast channels defined in `routes/channels.php`: user, team, metric-value, search-evaluation

### Permissions & Roles

Team-based access control:
- **Roles** (`app/Policies/Roles.php`):
  - **Administrator** (`admin`): All permissions
  - **Evaluator** (`evaluator`): `give_user_feedback` + `view_leaderboard` only
  - Team **Owner**: All permissions (implicit)
  - **Super Admin**: Platform-wide access (`super_admin` flag on User model)
- **Permissions** (`app/Policies/Permissions.php`): 10 permission constants
  - `manage_team`, `manage_api_token`, `view_team_subscription`, `send_team_messages`
  - `manage_search_endpoints`, `manage_search_models`, `manage_search_evaluations`
  - `manage_user_feedback`, `give_user_feedback`, `view_leaderboard`

### Tags System

Tags organize evaluations, models, and users:
- Team-scoped with configurable colors
- Pivot tables: `evaluation_tags`, `model_tags`, `user_tags`
- Used for feedback routing (tag-matching) and reuse strategy filtering
- Tag management: `app/Livewire/Tags/` components, `SyncTagsService`

## Database

Migrations in `database/migrations/`. Key tables:
- `search_endpoints`: API endpoint configurations (URL, method, headers, mapper code)
- `search_models`: Search configurations (headers, params, body, settings)
- `search_evaluations`: Evaluation runs (status, progress, scale_type, settings)
- `evaluation_keywords`: Keywords per evaluation (keyword text, execution results)
- `search_snapshots`: Search result documents (doc_id, name, image, position, doc JSON)
- `evaluation_metrics`: Metric results (scorer_type, value, previous_value, num_results)
- `keyword_metrics`: Per-keyword metric values
- `metric_values`: Metric value history (for timeline charts)
- `user_feedback`: Relevance judgments (user_id, snapshot_id, grade)
- `teams`: Team configuration (baseline_evaluation_id)
- `user_widgets`: Dashboard widgets (UUID PK, widget_class, position, settings)
- `tags`, `evaluation_tags`, `model_tags`, `user_tags`: Tagging system

## API

REST API with Sanctum token authentication at `/api/v1/`:
- Controllers: `app/Http/Controllers/Api/EvaluationsController.php`, `ModelsController.php`
- Routes: `routes/api.php`

Endpoints:
- `GET /api/v1/evaluations` — list (filterable by status, model, search)
- `GET /api/v1/evaluations/{id}` — show with metrics and keywords
- `POST /api/v1/evaluations` — create new evaluation
- `POST /api/v1/evaluations/{id}/start` — start evaluation
- `POST /api/v1/evaluations/{id}/stop` — pause evaluation
- `POST /api/v1/evaluations/{id}/finish` — finish evaluation
- `DELETE /api/v1/evaluations/{id}` — delete evaluation
- `GET /api/v1/evaluations/{id}/judgements` — export judgments
- `GET /api/v1/models` — list models
- `GET /api/v1/models/{id}` — show model

## Helper Functions

Global helpers in `app/helpers.php`:
- `unique_key(): string` — Generates a unique string identifier using `uniqid()` with extra entropy. Used for Livewire component keys and cache entries. **Not cryptographically secure.**

## Notifications

6 notification classes in `app/Notifications/`:
- `EvaluationFinishNotification`: Sent when evaluation finishes
- `MessageNotification`: Team messages
- `SystemNotification`: System-level alerts
- `TeamInvitationNotification`: Team invitations
- `ResetPassword`: Password reset
- All mail notifications support unsubscribe via `MailUnsubscribable`

## Documentation

- Comprehensive docs: `DOCUMENTATION.md` (project root)
- User docs: `resources/docs/` (rendered with LaRecipe)
- API docs: `resources/docs/1.0/api/`

## Important Notes

- All code comments must be in English
- PHP 8.3 features are used throughout (typed constants, typed properties)
- Field names use class constants pattern (`FIELD_*`)
- Evaluation jobs are queued and processed asynchronously — check Horizon dashboard at `/horizon`
- WebSocket connections require Reverb service to be running
- Mapper code uses Symfony ExpressionLanguage — see `DotArrayMapper.php`
- Models extending `TeamBroadcastableModel` auto-broadcast CRUD events on team channels
- `UserFeedback` assignments expire after 5 minutes if not graded
- Queue queues: `default`, `snapshots-auto` (parallel), `snapshots-single` (serial)
