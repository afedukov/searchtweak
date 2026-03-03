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

### Type Rules

- `id`: must evaluate to a string.
- `name`: must evaluate to a string.
- `image`: string or `null`.
- custom attributes: `string | array`.

If `id` or `name` is missing/empty after mapping, the document is dropped.

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

## Arrays and Wildcards

Both forms are supported:

```yaml
tags: data.items.*.tags
```

```yaml
tags: data.items.*.tags.*
```

For list fields, both variants map to array values in output.

## End-to-End Example

Input response:

```json
{
  "items": [
    {
      "id": "A1",
      "title": "Dyson V10",
      "price": 58830,
      "images": [
        { "url": "https://img/a1.webp" }
      ]
    },
    {
      "id": "A2",
      "title": "Dyson V8",
      "price": 45093,
      "images": [
        { "url": "https://img/a2.webp" }
      ]
    }
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
  {
    "id": "A1",
    "name": "Dyson V10",
    "price": 588.3,
    "image": "https://img/a1.webp"
  },
  {
    "id": "A2",
    "name": "Dyson V8",
    "price": 450.93,
    "image": "https://img/a2.webp"
  }
]
```

## Troubleshooting

- No snapshots created:
  - Verify root path and wildcard section.
- `id`/`name` missing:
  - Check mapper keys and source payload keys.
- Some fields are silently missing:
  - Check expression syntax; invalid expressions are skipped at runtime.
- Type surprises:
  - Ensure expression outputs scalar values.
- Inconsistent arrays:
  - Prefer defensive paths with existing indexes.

## Runtime Notes

- Mapper ignores non-JSON or non-object/array payloads.
- Variable extraction skips empty source values (`null`, `''`, `0`, `false`, empty array).
- Document list is limited after invalid documents are removed.
- Positions are reassigned sequentially from `1`.

## Best Practices

- Keep mapper minimal and deterministic.
- Avoid business logic in mapper when possible.
- Version mapper changes together with model/evaluation notes.
- Test mapper against a real sample response before starting long evaluations.
