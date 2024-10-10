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
```
