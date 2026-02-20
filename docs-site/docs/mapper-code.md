# Mapper Code

Mapper Code transforms API response JSON into normalized snapshot fields used by evaluations.

## Minimum Required Fields

Every mapped document must include:

- `id`
- `name`

Optional but common:

- `image`
- `price`
- `brand`
- `url`
- any custom scalar or scalar-array fields

## Basic Syntax

```yaml
id: data.items.*.id
name: data.items.*.title
image: data.items.*.images.0.url
```

### Rules

- `data` is the root document context.
- `*` iterates list elements.
- Dot notation navigates nested objects/arrays.
- Array indexes are zero-based (`images.0.url`).

## Expressions

You can use simple arithmetic and concatenation:

```yaml
price: data.items.*.price / 100
full_name: data.items.*.brand ~ ' ' ~ data.items.*.title
```

## End-to-End Example

Input response:

```json
{
  "items": [
    {"id": "A1", "title": "Dyson V10", "price": 58830, "images": [{"url": "https://img/a1.webp"}]},
    {"id": "A2", "title": "Dyson V8", "price": 45093, "images": [{"url": "https://img/a2.webp"}]}
  ]
}
```

Mapper:

```yaml
id: data.items.*.id
name: data.items.*.title
price: data.items.*.price / 100
image: data.items.*.images.0.url
```

Result snapshot payload:

```json
[
  {"id": "A1", "name": "Dyson V10", "price": 588.3, "image": "https://img/a1.webp"},
  {"id": "A2", "name": "Dyson V8", "price": 450.93, "image": "https://img/a2.webp"}
]
```

## Troubleshooting

- No snapshots created:
  - Verify root path and wildcard section.
- `id`/`name` missing:
  - Check mapper keys and source payload keys.
- Type surprises:
  - Ensure expression outputs scalar values.
- Inconsistent arrays:
  - Prefer defensive paths with existing indexes.

## Best Practices

- Keep mapper minimal and deterministic.
- Avoid business logic in mapper when possible.
- Version mapper changes together with model/evaluation notes.
