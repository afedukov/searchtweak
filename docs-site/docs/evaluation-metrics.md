# Evaluation Metrics

This section explains both the formulas and the exact calculation logic used in SearchTweak.

## Why This Matters

The same metric name can be implemented differently across tools (especially around missing grades and multi-feedback aggregation). This page documents SearchTweak's behavior explicitly.

## Notation

- `k`: cutoff depth (`@k`)
- `i`: rank position (`1..k`)
- `q`: keyword index
- `Q`: number of keywords with a computable value
- `r_i`: binary relevance at position `i` (`0` or `1`)
- `g_i`: gain at position `i` (graded/detail numeric value)

## Two-Stage Aggregation in SearchTweak

When strategy is `Multiple (3)`, each snapshot can have up to 3 feedback slots.

Stage 1, per-snapshot aggregation:

- Binary scale:
  - majority vote (`1` if relevant votes > irrelevant votes, `0` if opposite)
  - tie becomes `null` (for example `1,0` or `1,0,null`)
- Graded/detail scale:
  - arithmetic mean of non-null grades

Stage 2, metric computation:

- Metrics are computed over aggregated snapshot values at ranks `1..k`.
- For full evaluation, SearchTweak stores mean across keywords with non-null metric values.

```text
EvaluationMetric = (1 / Q) * sum_{q=1..Q}(Metric(q))
```

## Missing Grades and `null` Handling

- If all positions used by a metric are ungraded, metric value is `null`.
- For `P@k`, denominator is count of graded positions (not always `k`).
- For `CG`/`DCG`/`nDCG`, ungraded positions contribute `0` in summation, but if everything is ungraded result is `null`.
- For binary metrics with ties in strategy `3`, ties produce `null` and affect the metric as above.

## Binary Metrics

### Precision@k (P@k)

```text
P@k = relevant_graded / graded_count
```

Where:

- `relevant_graded` is number of positions with aggregated binary value `1`
- `graded_count` is number of positions with non-null aggregated value

Use `P@k` when you need a simple "share of relevant among judged".

### Average Precision@k (AP@k)

```text
AP@k = (1 / R) * sum_{i=1..k}(r_i * P@i)
```

Where `R` is number of relevant positions (`r_i = 1`) in top-`k`.

SearchTweak behavior:

- returns `null` if nothing is graded at all
- returns `0` if graded data exists but no relevant result exists

### Reciprocal Rank@k (RR@k)

```text
RR@k = 1 / rank(first relevant)
```

SearchTweak behavior:

- returns `null` if nothing is graded
- returns `0` if graded data exists but no relevant result exists

## Graded Metrics

### Cumulative Gain@k (CG@k)

```text
CG@k = sum_{i=1..k}(g_i)
```

No positional discount.

### Discounted Cumulative Gain@k (DCG@k)

```text
DCG@k = sum_{i=1..k}(g_i / log2(i + 1))
```

Earlier relevant documents contribute more.

### Normalized DCG@k (nDCG@k)

```text
nDCG@k = DCG@k / IDCG@k
```

`IDCG@k` is DCG for the same gains sorted descending.

SearchTweak behavior:

- returns `null` if nothing is graded
- returns `0` if `IDCG@k = 0`

## Detail-Scale Variants

For detail scale (`1..10`), formulas are identical:

- `CG(d)@k`
- `DCG(d)@k`
- `nDCG(d)@k`

Only gain range differs.

## Multi-Keyword Metrics

For evaluations with multiple keywords, SearchTweak uses mean over keyword metrics with non-null values:

- `MP@k` for precision
- `MAP@k` for average precision
- `MRR@k` for reciprocal rank

This avoids contaminating averages with keywords that still have no computable signal.

## Transformers and Mixed Scale Metrics

If an evaluation uses one grading scale but selected metrics require another scale, transformer rules are applied before metric calculation.

Example:

- evaluation scale `detail`
- metric `P@10` (binary)
- detail grades are first mapped to binary via transformer, then `P@10` is computed

## Metric Selection Guide

- `P@k`: quick relevance ratio
- `AP@k` / `MAP@k`: ranking quality sensitivity across the list
- `RR@k` / `MRR@k`: first-hit experience
- `nDCG@k`: graded ranking quality with position awareness
- detail variants: finer gain control when `0..3` is too coarse

## Interpretation Checklist

1. Keep same keyword set and same cutoff (`@k`) for comparisons.
2. Check grading coverage before trusting small changes.
3. Keep grading guidelines stable over time.
4. When using strategy `3`, monitor tie rate (binary) because ties reduce computable signal.

## Recommended Baseline Metric Set

For most teams:

- `MAP@10`
- `MRR@10`
- `nDCG@10`

Add domain-specific metrics only after baseline process is stable.
