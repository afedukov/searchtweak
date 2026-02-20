# Delete Evaluation

Deletes an evaluation.

## Endpoint

```http
DELETE /api/v1/evaluations/{id}
```

## Request Example

```bash
curl --request DELETE \
  --url 'https://searchtweak.local/api/v1/evaluations/307' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
{
  "status": "OK",
  "message": "Evaluation deleted"
}
```

## Error Responses

- `401` — unauthorized
- `404` — evaluation not found in current team
- `422` — deletion blocked by business rules
