# Get Evaluation Details

- [Endpoint](#endpoint)
- [Request Example](#request-example)
- [Response Example](#response-example)

<a name="endpoint"></a>
## Endpoint

`GET /api/v1/evaluations/{id}`

Retrieve details of a specific evaluation by its ID.

<a name="request-example"></a>
## Request Example

```bash
curl -X GET "https://searchtweak.com/api/v1/evaluations/139" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json"
```

<a name="response-example"></a>
## Response Example

```json
{
  "id": 139,
  "model_id": 1,
  "scale_type": "detail",
  "status": "pending",
  "progress": 0,
  "name": "Sample Evaluation",
  "description": "This is a sample evaluation with detail scale.",
  "settings": {
    "reuse": 0,
    "position": true,
    "strategy": 1,
    "auto_restart": false,
    "transformers": {
      "rules": {
        "binary": {
          "1": 0,
          "2": 0,
          "3": 0,
          "4": 0,
          "5": 0,
          "6": 1,
          "7": 1,
          "8": 1,
          "9": 1,
          "10": 1
        },
        "graded": {
          "1": 0,
          "2": 1,
          "3": 1,
          "4": 1,
          "5": 2,
          "6": 2,
          "7": 2,
          "8": 3,
          "9": 3,
          "10": 3
        }
      },
      "scale_type": "detail"
    },
    "scoring_guidelines": "Scoring guidelines for the evaluation."
  },
  "metrics": [
    {
      "scorer_type": "precision",
      "num_results": 10,
      "value": null
    },
    {
      "scorer_type": "ndcg",
      "num_results": 10,
      "value": null
    },
    {
      "scorer_type": "ndcg_d",
      "num_results": 5,
      "value": null
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
  "created_at": "2024-11-03T07:50:54+00:00",
  "finished_at": null
}
```
