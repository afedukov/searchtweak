# Get Evaluation Judgements

Exports final aggregated grades per `(keyword, position, doc)` for a finished evaluation.

## Endpoint

```http
GET /api/v1/evaluations/{id}/judgements
```

## Behavior

- Works only for evaluations in `finished` status.
- For each snapshot, all non-null grades are aggregated using the evaluation scale logic.
- Output includes one row per graded snapshot.

## Request Example

```bash
curl --request GET \
  --url 'https://searchtweak.local/api/v1/evaluations/307/judgements' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
[
  {
    "grade": 3,
    "keyword": "kühlschrank",
    "position": 1,
    "doc": "SKU-100200"
  },
  {
    "grade": 2.67,
    "keyword": "kühlschrank",
    "position": 2,
    "doc": "SKU-100201"
  }
]
```

## Error Responses

- `401` — unauthorized
- `404` — evaluation not found in current team
- `422` — evaluation not finished
