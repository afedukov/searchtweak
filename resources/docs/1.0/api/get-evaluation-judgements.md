# Get Evaluation Judgements

- [Endpoint](#endpoint)
- [Request Example](#request-example)
- [Response Example](#response-example)

<a name="endpoint"></a>
## Endpoint

`GET /api/v1/evaluations/{id}/judgements`

Retrieve the judgements for a specific evaluation.

<a name="request-example"></a>
## Request Example

```bash
curl -X GET "https://searchtweak.com/api/v1/evaluations/32/judgements" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json"
```

<a name="response-example"></a>
## Response Example

```json
[
  {
    "grade": 0,
    "keyword": "dyson",
    "position": 1,
    "doc": "881c6499-d4b4-455b-a7d9-cd5ed380c4c4"
  },
  {
    "grade": 2,
    "keyword": "dyson",
    "position": 2,
    "doc": "7151b7a7-3827-4f55-a8a5-275f7b40ae3a"
  },
  {
    "grade": 0,
    "keyword": "dyson",
    "position": 3,
    "doc": "43aae203-4f0b-46d0-a617-b0b53a4f81a2"
  },
  {
    "grade": 2,
    "keyword": "dyson",
    "position": 4,
    "doc": "dfa21f62-a37d-4683-bd27-acbec7ee6a04"
  },
  {
    "grade": 3,
    "keyword": "dyson",
    "position": 5,
    "doc": "44807a54-1f4d-4450-969e-05c34ee13698"
  },
  {
    "grade": 3,
    "keyword": "grill",
    "position": 1,
    "doc": "28c874e9-32a5-493b-a0d3-88439a1cc317"
  },
  {
    "grade": 2,
    "keyword": "grill",
    "position": 2,
    "doc": "d8f30866-f615-4372-a1ac-01d5f91a8c15"
  },
  {
    "grade": 1,
    "keyword": "grill",
    "position": 3,
    "doc": "258afa60-f877-4f2c-9da3-05191e51a473"
  },
  {
    "grade": 1,
    "keyword": "grill",
    "position": 4,
    "doc": "62d28526-bd9d-454d-8942-8a6ec74e0ad7"
  },
  {
    "grade": 0,
    "keyword": "grill",
    "position": 5,
    "doc": "35212d46-a7c5-4627-affd-04965f446b8a"
  },
  {
    "grade": 1,
    "keyword": "kühlschrank",
    "position": 1,
    "doc": "5414d53d-1113-42d3-a15f-7f27c7a5aa38"
  },
  {
    "grade": 0,
    "keyword": "kühlschrank",
    "position": 2,
    "doc": "a9acb2fc-3e18-498c-8aeb-86ff4ee0f7d4"
  },
  {
    "grade": 0,
    "keyword": "kühlschrank",
    "position": 3,
    "doc": "ee9e7991-add2-4cf9-82af-622f5b33c62e"
  },
  {
    "grade": 1,
    "keyword": "kühlschrank",
    "position": 4,
    "doc": "387c0843-1f3c-47b4-b038-931fbab8d140"
  },
  {
    "grade": 3,
    "keyword": "kühlschrank",
    "position": 5,
    "doc": "b7c29f5c-abbd-41ef-88e7-d2faff8dc92e"
  }
]
```
