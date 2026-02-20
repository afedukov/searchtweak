# Overview

SearchTweak is a platform for measuring and improving search relevance with **human feedback** and **AI judges**.

## Who This Documentation Is For

- Product managers who need reliable relevance KPIs.
- Search engineers who tune ranking and query logic.
- QA/relevance teams who provide graded feedback.
- Team admins who manage roles, tags, and AI judges.

## Core Concept

SearchTweak helps you run repeatable relevance experiments:

1. Define how to call your search API (`Search Endpoint`).
2. Define request templates (`Search Model`).
3. Create an evaluation with keywords, scale, and metrics (`Search Evaluation`).
4. Collect grades from humans and/or AI judges.
5. Compare metric trends, baselines, and productivity.

## System Workflow

```text
╭─────────────────╮   ╭───────────────╮   ╭───────────────────╮
│ Search Endpoint │──▶│  Search Model │──▶│ Search Evaluation │
╰─────────────────╯   ╰───────────────╯   ╰───────────────────╯
                              │
                              ▼
             ╭─────────────────────────────────╮
             │  Keywords + Results (Snapshots) │
             ╰─────────────────────────────────╯
                              │
                              ▼
             ╭─────────────────────────────────╮
             │   Human/AI Grades (Feedback)    │
             ╰─────────────────────────────────╯
                              │
                              ▼
             ╭─────────────────────────────────╮
             │        Metrics + Exports        │
             ╰─────────────────────────────────╯
```

## Main Sections

- [Search Endpoints](/search-endpoints): connect SearchTweak to your API.
- [Mapper Code](/mapper-code): extract `id`, `name`, and optional fields from API responses.
- [Search Models](/search-models): define query params/body templates.
- [Search Evaluations](/search-evaluations): configure strategy, scale, and metric set.
- [Judges (AI)](/judges): configure LLM judges, prompts, and monitoring.
- [Leaderboard](/leaderboard): compare throughput of users and AI judges.
- [Evaluation Metrics](/evaluation-metrics): formulas, interpretation, and caveats.
- [Team Management](/team-management): roles, membership, and permissions.
- [Tags](/tags): routing and access segmentation.
- [API Reference](/api/overview): automate evaluations via HTTP API.

## Quick Start (Non-Technical)

1. Create one endpoint using your production/staging search API URL.
2. Create one model and test with 2-3 sample queries.
3. Create one evaluation with 20-50 representative keywords.
4. Start with `Single` strategy for speed.
5. Add AI judges after baseline human validation.
6. Review metric trends before changing ranking logic.

## Terminology

- **Snapshot**: one query-document pair at a fixed rank position.
- **Feedback slot**: one grade cell for a snapshot.
- **Strategy Single (1)**: one slot per snapshot.
- **Strategy Multiple (3)**: up to three slots per snapshot.
- **Reuse**: copy compatible grades from earlier evaluations.
- **Baseline**: reference evaluation for metric comparison.

## Recommended Documentation Reading Order

1. [Search Endpoints](/search-endpoints)
2. [Mapper Code](/mapper-code)
3. [Search Models](/search-models)
4. [Search Evaluations](/search-evaluations)
5. [Evaluation Metrics](/evaluation-metrics)
6. [Judges (AI)](/judges)
