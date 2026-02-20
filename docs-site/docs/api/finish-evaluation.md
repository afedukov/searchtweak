# Finish Evaluation

Finishes an evaluation immediately.

## Endpoint

```http
POST /api/v1/evaluations/{id}/finish
```

## Request Example

```bash
curl --request POST \
  --url 'https://searchtweak.local/api/v1/evaluations/307/finish' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
{
  "status": "OK",
  "message": "Evaluation finished"
}
```

## Error Responses

- `401` — unauthorized
- `404` — evaluation not found in current team
- `422` — cannot finish in current state
