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
    "metrics": [
      {
        "scorer_type": "ndcg",
        "num_results": 10,
        "value": 0.72
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
