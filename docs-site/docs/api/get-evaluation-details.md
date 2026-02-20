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
    }
  ],
  "tags": [
    {
      "id": 3,
      "name": "Retail"
    }
  ],
  "keywords": [
    "kühlschrank",
    "mini fridge"
  ],
  "created_at": "2026-02-20T09:10:00+00:00",
  "finished_at": null
}
```

## Error Responses

- `401` — unauthorized
- `404` — evaluation not found in current team
