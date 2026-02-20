# Search Models

A Search Model defines **what request is sent** to an endpoint.

## Model Components

- `Name`, `Description`
- `Endpoint` (required)
- `Params` (query string map)
- `Body` and `Body Type`
- `Custom Headers` (override/extend endpoint headers)
- Optional default `Keywords`
- Optional assigned `Tags` (default tags for new evaluations)

## Query Placeholder

Use `#query#` in params/body to inject each evaluation keyword at runtime.

Examples:

```yaml
q: #query#
lang: de
```

```json
{
  "query": "#query#",
  "limit": 50
}
```

## Request Composition Order

1. Endpoint URL + method
2. Endpoint headers
3. Model headers override/extend
4. Params/body with `#query#` substitution

## Test Model Flow

Before using in evaluations:

1. Set temporary keyword replacing `#query#` with real text.
2. Run test request.
3. Confirm mapper output includes valid documents.
4. Check at least `id` and `name` extracted.

## Model Metrics View

Model detail page aggregates latest finished evaluation metrics and trend history.

Use it for:

- tracking long-term ranking quality
- comparing before/after relevance changes
- spotting regressions across releases

## Common Pitfalls

- Placeholder missing in request template.
- Wrong body type for upstream API.
- Header override accidentally removing required auth header.
- Unbounded result size causing noisy evaluations.
