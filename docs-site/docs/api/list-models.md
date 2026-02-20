# List Models

Returns all search models available in the authenticated team.

## Endpoint

```http
GET /api/v1/models
```

## Query Parameters

None.

## Request Example

```bash
curl --request GET \
  --url 'https://searchtweak.local/api/v1/models' \
  --header 'Authorization: Bearer <API_TOKEN>' \
  --header 'Accept: application/json'
```

## Response `200`

```json
[
  {
    "id": 42,
    "name": "Marketplace Search",
    "description": "Main catalog model",
    "endpoint": {
      "id": 8,
      "name": "Catalog API",
      "method": "GET",
      "url": "https://api.example.com/search"
    },
    "headers": {
      "Accept-Language": "de"
    },
    "params": {
      "q": "#query#"
    },
    "body": "",
    "body_type": null,
    "settings": {
      "keywords": [
        "kühlschrank",
        "staubsauger"
      ]
    },
    "tags": [
      {
        "id": 3,
        "name": "Retail"
      }
    ],
    "created_at": "2026-02-20T09:10:00+00:00"
  }
]
```

## Error Responses

- `401` — unauthorized
