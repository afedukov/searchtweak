# Search Evaluations

Search Evaluation is the execution unit that turns keywords and search results into measurable relevance metrics.

## Evaluation Lifecycle

Status flow:

```text
Pending -> Active -> Finished
```

Key actions:

- `Start`: creates/runs keyword snapshot jobs.
- `Stop/Pause`: pauses active execution.
- `Finish`: closes evaluation and finalizes state.

## Required Configuration

- Model
- Name
- Scale type: `binary`, `graded`, `detail`
- Metric set (one or more)
- Keywords
- Feedback strategy (`1` or `3`)

## Scale Types

- **Binary**: `0` (irrelevant), `1` (relevant)
- **Graded**: `0..3`
- **Detail**: `1..10`

## Metrics and Transformers

You can mix metrics that require different scales. In this case, transformers are required to map grades from evaluation scale to destination metric scales.

Important:

- transformer source scale must match evaluation scale
- transformer rules must cover all required destination scales
- after evaluation starts, scale/transformers are effectively locked by business rules

## Advanced Settings

### Feedback Strategy

- `Single (1)`: one grade slot per snapshot
- `Multiple (3)`: up to three slots per snapshot

Practical trade-off:

- `Single`: fastest and cheapest collection cycle
- `Multiple`: higher agreement quality for ambiguous queries, but more effort

### Show Position

If enabled, evaluators see original rank position while grading.

### Reuse Strategy

- `0`: none
- `1`: reuse by `(query, doc)`
- `2`: reuse by `(query, doc, position)`

Notes:

- Reuse can include both human and AI judge grades.
- Tag constraints are respected.
- Reuse should not be combined with auto-restart.

## Human and AI Collaboration Model

- Human and AI judges use the same feedback-slot mechanism.
- A slot is owned either by `user_id` (human) or `judge_id` (AI).
- When a slot is overwritten by the opposite side, the previous owner field is cleared.
- Under strategy `3`, one AI judge can fill at most one slot per snapshot.

### Auto-Restart

Automatically spawns a new evaluation with same config after completion.

## Evaluation Outputs

- Snapshot-level feedback records
- Metric values per scorer
- Keyword-level metric breakdowns
- Exportable judgements

## Operational Best Practices

- Start with focused keyword sets (20-100) per domain slice.
- Keep one stable baseline evaluation for comparison.
- Use `Single` early, `Multiple` for high-stakes decisions.
- Review unfinished snapshots before finalizing conclusions.
