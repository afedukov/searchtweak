# Start Evaluation

Queues evaluation start.

## Endpoint

```http
POST /api/v1/evaluations/{id}/start
```

## Rules

- Evaluation must be in `pending` status.
- Evaluation must not be in temporary "changes blocked" state.

## Request Example

```bash
curl --request POST \
  --url 'https://searchtweak.local/api/v1/evaluations/307/start' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
{
  "status": "OK",
  "message": "Evaluation start job dispatched"
}
```

## Error Responses

- `401` — unauthorized
- `404` — evaluation not found in current team
- `422` — invalid state (example: `Evaluation is not pending`)
