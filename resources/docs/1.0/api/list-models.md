# List Models

- [Endpoint](#endpoint)
- [Request Example](#request-example)
- [Response Example](#response-example)

<a name="endpoint"></a>
## Endpoint 

`GET /api/v1/models`

Retrieve a list of all search models for the authenticated team.

<a name="request-example"></a>
## Request Example

```bash
curl -X GET "https://searchtweak.com/api/v1/models" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json"
```

<a name="response-example"></a>
## Response Example

```json
[
  {
    "id": 42,
    "name": "Basic Search",
    "description": "Basic search with default query parameters",
    "endpoint": {
      "id": 31,
      "name": "Kaufland.de",
      "method": "GET",
      "url": "https://api.cloud.kaufland.de/search/v1/result-product-offers/"
    },
    "headers": {
      "Accept-Language": "de"
    },
    "params": {
      "searchValue": "YOUR_QUERY"
    },
    "body": "",
    "body_type": null,
    "settings": null,
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
    "created_at": "2024-04-15T18:01:37+00:00"
  }
]
```
