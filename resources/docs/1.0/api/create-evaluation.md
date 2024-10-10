# Create Evaluation

- [Endpoint](#endpoint)
- [Request Body](#request-body)
- [Request Example](#request-example)
- [Response Example](#response-example)

<a name="endpoint"></a>
## Endpoint

`POST /api/v1/evaluations`

Create a new evaluation for the authenticated team.

<a name="request-body"></a>
## Request Body

The request body must be a JSON object with the following properties:

- `name` (string) <span style="color: red">required</span>: The name of the evaluation.
- `description` (string) <span style="color: grey">optional</span>: A description of the evaluation.
- `model_id` (integer) <span style="color: red">required</span>: The ID of the search model to be evaluated.
- `metrics` (array of objects) <span style="color: red">required</span>: A list of metrics in the format `{"scorer_type": "ap", "num_results": 10}`.
  - `scorer_type` (string) <span style="color: red">required</span>: The type of scorer. Possible values are `precision`, `ap`, `rr`, `cg`, `dcg`, `ndcg`.
  - `num_results` (integer) <span style="color: red">required</span>: The number of results to be evaluated.
- `keywords` (array of strings) <span style="color: red">required</span>: A list of keywords for the evaluation.
- `tags` (array of objects) <span style="color: grey">optional</span>: A list of tags for the evaluation in the format `{"id": 1}`.
  - `id` (integer) <span style="color: red">required</span>: The ID of the tag.
- `setting_feedback_strategy` (integer) <span style="color: red">required</span>: The feedback strategy. Possible values are `1` (Single), `3` (Multiple).
- `setting_show_position` (boolean) <span style="color: red">required</span>: Whether to show the position of the results.
- `setting_reuse_strategy` (integer) <span style="color: red">required</span>: The reuse strategy for grades. Possible values are `0` (No), `1` (Query/Doc), `2` (Query/Doc/Position).
- `setting_auto_restart` (boolean) <span style="color: red">required</span>: Whether to auto-restart the evaluation.

<a name="request-example"></a>
## Request Example

```bash
curl -X POST "https://searchtweak.com/api/v1/evaluations" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{
          "model_id": 45,
          "name": "Sample Evaluation",
          "description": "This is a sample evaluation.",
          "keywords": [
            "apple",
            "dyson"
          ],
          "metrics": [
            { "scorer_type": "precision", "num_results": 10 },
            { "scorer_type": "ap", "num_results": 10 }
          ],
          "tags": [
            { "id": 62 }
          ],
          "setting_feedback_strategy": 1,
          "setting_reuse_strategy": 0,
          "setting_show_position": true,
          "setting_auto_restart": false
        }'
```
 
<a name="response-example"></a>
## Response Example
```json
{
  "id": 213,
  "model_id": 45,
  "scale_type": "binary",
  "status": "pending",
  "progress": 0,
  "name": "Sample Evaluation",
  "description": "This is a sample evaluation.",
  "metrics": [
    {
      "scorer_type": "precision",
      "num_results": 10,
      "value": null
    },
    {
      "scorer_type": "ap",
      "num_results": 10,
      "value": null
    }
  ],
  "tags": [
    {
      "id": 62,
      "name": "Green"
    }
  ],
  "keywords": [
    "apple",
    "dyson"
  ],
  "created_at": "2024-07-16T11:29:05+00:00",
  "finished_at": null
}
```
