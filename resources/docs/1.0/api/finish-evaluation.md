# Finish Evaluation

- [Endpoint](#endpoint)
- [Request Example](#request-example)
- [Response Example](#response-example)

<a name="endpoint"></a>
## Endpoint

`POST /api/v1/evaluations/{id}/finish`

Finish the evaluation with the given ID.

<a name="request-example"></a>
## Request Example

```bash
curl -X POST "https://searchtweak.com/api/v1/evaluations/32/finish" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json"
```

<a name="response-example"></a>
## Response Example

```json
{
  "status": "OK",
  "message": "Evaluation finished"
}
```
