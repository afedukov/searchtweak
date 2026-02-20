# API Overview

SearchTweak API is a token-based REST API for managing search models and evaluations.

## Base URL

Use your deployment host:

```text
https://<your-host>/api/v1
```

Examples in this section use:

```text
https://searchtweak.local/api/v1
```

## Authentication

All endpoints require a team API token via Laravel Sanctum.

```http
Authorization: Bearer <API_TOKEN>
Accept: application/json
```

### How to Get API Token

Generate/revoke token in the web UI:

1. Open `Current Team`.
2. Click `API`.
3. Click `Generate New Token` (or revoke existing token and generate a new one).
4. Copy the token and store it securely (password manager / secret storage).

Important notes:

- The token is shown in plain text only right after generation.
- If token is lost, generate a new one and update your integrations.
- API access is team-scoped and depends on the current team context in UI.

## Response Format

- Collections are returned as plain JSON arrays (no `data` wrapper).
- Resource objects are returned as plain JSON objects.
- Timestamps are ISO-8601 strings.

## Common HTTP Statuses

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Created |
| `401` | Missing/invalid token |
| `404` | Entity not found in current team scope |
| `422` | Validation error or business rule violation |

Typical business-rule error payload:

```json
{
  "message": "Evaluation is not pending"
}
```

Typical validation error payload (Laravel default):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "scale_type": [
      "The selected scale type is invalid."
    ]
  }
}
```

## Endpoints

### Models

- `GET /models` — list models in current team
- `GET /models/{id}` — get model details

### Evaluations

- `GET /evaluations` — list evaluations with optional filters
- `GET /evaluations/{id}` — get evaluation details
- `POST /evaluations` — create evaluation
- `POST /evaluations/{id}/start` — queue start job
- `POST /evaluations/{id}/stop` — queue stop job
- `POST /evaluations/{id}/finish` — finish evaluation immediately
- `DELETE /evaluations/{id}` — delete evaluation
- `GET /evaluations/{id}/judgements` — export aggregated final grades

## Quickstart Flow

1. `GET /models` and choose `model_id`.
2. `POST /evaluations`.
3. `POST /evaluations/{id}/start`.
4. Poll `GET /evaluations/{id}` until `status = finished`.
5. Export with `GET /evaluations/{id}/judgements`.

## Execution Model

- `POST /evaluations/{id}/start` and `POST /evaluations/{id}/stop` are asynchronous (queue jobs are dispatched).
- `POST /evaluations/{id}/finish` is synchronous (request returns when finish action is completed).

## Notes

- API is team-scoped: IDs from another team return `404`.
- Judge management and judge logs are currently available only in the web UI.
