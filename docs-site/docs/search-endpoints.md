# Search Endpoints

A Search Endpoint defines **how SearchTweak talks to your search API**.

## What You Configure

- `Name`: human-readable label.
- `Description`: optional business context.
- `URL`: full API endpoint URL.
- `Method`: `GET`, `POST`, or `PUT`.
- `Mapper Code`: rules to extract documents from response payload.
- `Custom Headers`: optional key-value headers.
- `Advanced: Multi-threading`:
  - `Auto`: parallel snapshot jobs (`snapshots-auto`).
  - `Single`: serialized snapshot jobs (`snapshots-single`).

## Practical Setup Checklist

1. Start from a real API call that already works outside SearchTweak.
2. Add minimal required mapper fields (`id`, `name`).
3. Validate with at least one query that returns results.
4. Add optional fields (`image`, `brand`, etc.) only after base mapping works.
5. Keep authentication headers stable and secret-safe.

## Endpoint Behavior Notes

- Endpoint activation controls whether it can be used for new models.
- Existing models/evaluations are not automatically deleted when endpoint is deactivated.
- Deleting an endpoint may be blocked by unfinished evaluations linked through models.
- Endpoints list supports segmented status filter: `All | Active | Archived`.

## Common Mistakes

- Missing `id` or `name` in mapper output.
- Wrong method/body/header combination (`GET` with body assumptions).
- Mapping path does not match real response shape.
- Over-aggressive parallelism against strict upstream rate limits.

## When to Use `Single` Queue Mode

Use `Single` when upstream API has strict rate limits, unstable latency, or anti-burst behavior.

## Next Step

After endpoint is stable, continue with [Search Models](/search-models).
