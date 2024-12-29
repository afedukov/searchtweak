# List Evaluations

- [Endpoint](#endpoint)
- [Request Example](#request-example)
- [Query Parameters](#query-parameters)
- [Response Example](#response-example)

<a name="endpoint"></a>
## Endpoint 

`GET /api/v1/evaluations`

Retrieve a list of all evaluations for the authenticated team.

<a name="request-example"></a>
## Request Example

```bash
curl -X GET "https://searchtweak.com/api/v1/evaluations" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json" \
    -d "model_id"="42" \
    -d "status"="active" \
    -d "scale_type"="graded"
```

<a name="query-parameters"></a>
## Query Parameters

- `model_id` <span style="color: grey">optional</span>: Filter evaluations by model ID.
- `status` <span style="color: grey">optional</span>: Filter evaluations by status. Possible values are `active`, `pending`, `finished`.
- `scale_type` <span style="color: grey">optional</span>: Filter evaluations by scale type. Possible values are `binary`, `graded`, `detail`.

<a name="response-example"></a>
## Response Example

```json
[
  {
    "id": 144,
    "model_id": 42,
    "scale_type": "graded",
    "status": "active",
    "progress": 10,
    "name": "Demo Graded 3",
    "description": "Demo evaluation with graded scale",
    "settings": {
      "reuse": 0,
      "position": false,
      "strategy": 1,
      "auto_restart": false,
      "transformers": {
        "rules": {
          "binary": [
            0,
            1,
            1,
            1
          ],
          "detail": [
            1,
            4,
            7,
            10
          ]
        },
        "scale_type": "graded"
      },
      "scoring_guidelines": "Scoring guidelines for the evaluation."
    },
    "metrics": [
      {
        "scorer_type": "precision",
        "num_results": 10,
        "value": 0.72
      },
      {
        "scorer_type": "cg",
        "num_results": 10,
        "value": 12
      },
      {
        "scorer_type": "cg_d",
        "num_results": 10,
        "value": 36
      }
    ],
    "tags": [
      {
        "id": 56,
        "name": "Priority"
      },
      {
        "id": 58,
        "name": "Green"
      }
    ],
    "keywords": [
      "apple",
      "dyson"
    ],
    "created_at": "2024-06-24T08:07:42+00:00",
    "finished_at": null
  },
  {
    "id": 139,
    "model_id": 42,
    "scale_type": "binary",
    "status": "finished",
    "progress": 100,
    "name": "Demo Binary 1",
    "description": "Demo evaluation with binary scale",
    "settings": {
      "reuse": 0,
      "position": false,
      "strategy": 1,
      "auto_restart": false,
      "transformers": {
        "rules": [],
        "scale_type": "binary"
      },
      "scoring_guidelines": "Scoring guidelines for the evaluation."
    },
    "metrics": [
      {
        "scorer_type": "precision",
        "num_results": 10,
        "value": 0.35
      },
      {
        "scorer_type": "ap",
        "num_results": 10,
        "value": 0.5
      }
    ],
    "tags": [
      {
        "id": 56,
        "name": "Priority"
      },
      {
        "id": 57,
        "name": "Purple"
      }
    ],
    "keywords": [
      "apple",
      "dyson"
    ],
    "created_at": "2024-06-24T06:52:39+00:00",
    "finished_at": "2024-06-24T06:57:03+00:00"
  }
]
```
