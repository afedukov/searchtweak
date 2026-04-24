# Get Evaluation Details

Returns a single evaluation in the authenticated team.

## Endpoint

```http
GET /api/v1/evaluations/{id}
```

## Path Parameters

| Name | Type | Required | Description |
|---|---|---|---|
| `id` | integer | yes | Evaluation ID |

## Request Example

```bash
curl --request GET \
  --url 'https://searchtweak.local/api/v1/evaluations/307' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
{
  "id": 307,
  "model_id": 42,
  "scale_type": "graded",
  "status": "active",
  "progress": 33.33,
  "name": "Q1 relevance check",
  "description": "",
  "settings": {
    "strategy": 3,
    "position": true,
    "reuse": 1,
    "auto_restart": false,
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
    },
    "scoring_guidelines": ""
  },
  "metrics": [
    {
      "scorer_type": "precision",
      "num_results": 10,
      "value": 0.72
    },
    {
      "scorer_type": "ndcg",
      "num_results": 10,
      "value": 0.81
    }
  ],
  "tags": [
    {
      "id": 3,
      "name": "Retail"
    }
  ],
  "keywords": [
    {
      "keyword": "kühlschrank",
      "metrics": [
        { "scorer_type": "precision", "num_results": 10, "value": 0.80 },
        { "scorer_type": "ndcg", "num_results": 10, "value": 0.85 }
      ]
    },
    {
      "keyword": "mini fridge",
      "metrics": [
        { "scorer_type": "precision", "num_results": 10, "value": 0.64 },
        { "scorer_type": "ndcg", "num_results": 10, "value": null }
      ]
    }
  ],
  "created_at": "2026-02-20T09:10:00+00:00",
  "finished_at": null
}
```

### Per-keyword metrics

`keywords[].metrics` follows the same contract as the top-level `metrics`: the `(scorer_type, num_results)` pairs match and appear in the same order for every keyword. `value` is the metric calculated for that single keyword (not an aggregate across all of them). If the value for a `(keyword, metric)` pair has not been computed yet (for example, the evaluation is still `pending` or no feedback has arrived), `value` is `null`.

## Error Responses

- `401` — unauthorized
- `404` — evaluation not found in current team
