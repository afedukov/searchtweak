# List Evaluations

Returns evaluations in the authenticated team.

## Endpoint

```http
GET /api/v1/evaluations
```

## Query Parameters

| Name | Type | Required | Allowed Values | Description |
|---|---|---|---|---|
| `model_id` | integer | no | any integer | Filter by model ID |
| `status` | string | no | `pending`, `active`, `finished` | Filter by evaluation status |
| `scale_type` | string | no | `binary`, `graded`, `detail` | Filter by scale |

## Request Example

```bash
curl --request GET \
  --url 'https://searchtweak.local/api/v1/evaluations?status=active&scale_type=graded' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
[
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
        "value": null
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
]
```

## Error Responses

- `401` — unauthorized
- `422` — invalid query parameter value
