# Evaluation Metrics

- [Overview](#overview)
- [Precision at 10 (P@10)](#p10)
- [Average Precision at 10 (AP@10)](#ap10)
- [Reciprocal Rank at 10 (RR@10)](#rr10)
- [Cumulative Gain at 10 (CG@10)](#cg10)
- [Discounted Cumulative Gain at 10 (DCG@10)](#dcg10)
- [Normalized Discounted Cumulative Gain at 10 (nDCG@10)](#ndcg10)
- [Mean Average Precision (MAP)](#map)
- [Mean Reciprocal Rank (MRR)](#mrr)

<a name="overview"></a>
## Overview

Search Tweak provides a suite of metrics to evaluate and optimize the performance of search models. These metrics are essential for understanding the relevance and effectiveness of search results, allowing for detailed analysis and improvement.

<a name="p10"></a>
## Precision at 10 (P@10)

**Precision at 10 (P@10)** measures the proportion of relevant documents in the top 10 search results.

### Calculation
<img src="/images/docs/metrics/p.png" alt="P@10" style="width: 100%; max-width: 436px; height: auto;">

### Pros
- Simple to calculate and understand.
- Direct measure of search quality for top results.

### Cons
- Does not consider the order of relevant documents.

<a name="ap10"></a>
## Average Precision at 10 (AP@10)

**Average Precision at 10 (AP@10)** evaluates the average precision scores at the ranks where relevant documents are found up to the 10th result.

### Calculation
<img src="/images/docs/metrics/ap.png" alt="AP@10" style="width: 100%; max-width: 566px; height: auto;">

### Pros
- Considers the order of relevant documents.
- Provides a balanced view of precision across different ranks.

### Cons
- Sensitive to the position of relevant documents within the top 10.

<a name="rr10"></a>
## Reciprocal Rank at 10 (RR@10)

**Reciprocal Rank at 10 (RR@10)** measures the reciprocal of the rank of the first relevant document within the top 10 search results.

### Calculation
<img src="/images/docs/metrics/rr.png" alt="RR@10" style="width: 100%; max-width: 338px; height: auto;">

### Pros
- Highlights the importance of the first relevant document.
- Simple interpretation.

### Cons
- Focuses only on the first relevant result.
- Ignores the relevance of subsequent documents.

<a name="cg10"></a>
## Cumulative Gain at 10 (CG@10)

**Cumulative Gain at 10 (CG@10)** sums the relevance scores of documents up to the 10th position.

### Calculation
<img src="/images/docs/metrics/cg.png" alt="CG@10" style="width: 100%; max-width: 459px; height: auto;">

### Pros
- Simple to calculate.
- Reflects the total relevance of top results.

### Cons
- Does not account for the position of relevant documents.
- Can be skewed by highly relevant documents lower in the ranking.

<a name="dcg10"></a>
## Discounted Cumulative Gain at 10 (DCG@10)

**Discounted Cumulative Gain at 10 (DCG@10)** is a variation of CG@10 that discounts the relevance scores based on their position.

### Calculation
<img src="/images/docs/metrics/dcg.png" alt="DCG@10" style="width: 100%; max-width: 228px; height: auto;">

### Pros
- Accounts for the position of relevant documents.
- Penalizes lower-ranked relevant documents.
- More realistic measure of user satisfaction.

### Cons
- Sensitive to the position of relevance scores.
- Requires relevance scores.

<a name="ndcg10"></a>
## Normalized Discounted Cumulative Gain at 10 (nDCG@10)

**Normalized Discounted Cumulative Gain at 10 (nDCG@10)** normalizes DCG@10 by the ideal DCG (IDCG) at 10, making it easier to compare across different queries.

### Calculation
<img src="/images/docs/metrics/ndcg.png" alt="nDCG@10" style="width: 100%; max-width: 210px; height: auto;">

### Pros
- Normalizes the scores, allowing for comparison.
- Provides a standard measure of ranking quality.

### Cons
- More complex to calculate.
- Requires ideal ranking computation.

<a name="map"></a>
## Mean Average Precision (MAP)

**Mean Average Precision (MAP)** is the mean of the average precision scores for a set of queries, providing a single measure of quality across multiple queries.

### Calculation
<img src="/images/docs/metrics/map.png" alt="MAP" style="width: 100%; max-width: 183px; height: auto;">

### Pros
- Aggregates precision across multiple queries.
- Comprehensive measure of search performance.

### Cons
- Requires multiple queries.
- Sensitive to the presence of relevant documents.

<a name="mrr"></a>
## Mean Reciprocal Rank (MRR)

**Mean Reciprocal Rank (MRR)** averages the reciprocal ranks of the first relevant document for a set of queries.

### Calculation
<img src="/images/docs/metrics/mrr.png" alt="MRR" style="width: 100%; max-width: 456px; height: auto;">

### Pros
- Simple to interpret.
- Emphasizes the importance of early relevant results.

### Cons
- Ignores subsequent relevant results.
- Can be skewed by outliers.

---

Feel free to explore other sections of the documentation to get a better understanding of how to set up and use Search Tweak effectively.
