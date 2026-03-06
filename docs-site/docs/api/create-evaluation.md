# Create Evaluation

Creates a new evaluation in `pending` state.

## Endpoint

```http
POST /api/v1/evaluations
```

## Request Body

| Field | Type | Required | Notes |
|---|---|---|---|
| `model_id` | integer | yes | Must exist in current team |
| `name` | string | yes | max 255 |
| `description` | string | no | max 4000 |
| `scale_type` | string | yes | `binary`, `graded`, `detail` |
| `metrics` | array | yes | Unique pairs of `scorer_type + num_results` are required |
| `metrics[].scorer_type` | string | yes | `precision`, `ap`, `rr`, `cg`, `dcg`, `ndcg`, `err`, `err_018`, `cg_d`, `dcg_d`, `ndcg_d` |
| `metrics[].num_results` | integer | yes | `1..50` |
| `keywords` | array of strings | yes | `1..250` unique non-empty values |
| `tags` | array | no | `[{ "id": <tag_id> }]` |
| `setting_feedback_strategy` | integer | yes | `1` (single) or `3` (multiple) |
| `setting_show_position` | boolean | yes | Show rank position during feedback |
| `setting_reuse_strategy` | integer | yes | `0` (none), `1` (query+doc), `2` (query+doc+position) |
| `setting_auto_restart` | boolean | yes | Must be `false` when reuse strategy is not `0` |
| `setting_scoring_guidelines` | string | no | max 2000 |
| `transformers` | object | yes | Must match `scale_type`; include rules for every required destination scale |

## Request Example

```bash
curl --request POST \
  --url 'https://searchtweak.local/api/v1/evaluations' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "model_id": 42,
    "name": "Q1 relevance check",
    "description": "optional text",
    "scale_type": "graded",
    "metrics": [
      { "scorer_type": "precision", "num_results": 10 },
      { "scorer_type": "ndcg", "num_results": 10 }
    ],
    "keywords": ["kühlschrank", "mini fridge"],
    "tags": [{ "id": 3 }],
    "setting_feedback_strategy": 3,
    "setting_show_position": true,
    "setting_reuse_strategy": 1,
    "setting_auto_restart": false,
    "setting_scoring_guidelines": "",
    "transformers": {
      "scale_type": "graded",
      "rules": {
        "binary": {
          "0": 0,
          "1": 1,
          "2": 1,
          "3": 1
        }
      }
    }
  }'
```

## Response `201`

Returns the same shape as [Get Evaluation Details](/api/get-evaluation-details).

Created evaluation is returned in `pending` status with initial `progress = 0`.

## Error Responses

- `401` — unauthorized
- `422` — validation or business-rule error

Example:

```json
{
  "message": "Auto-restart cannot be enabled when re-use strategy is set."
}
```
