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
- `scale_type` (string) <span style="color: red">required</span>: The scale type of the evaluation. Possible values are `binary`, `graded`, `detail`.
- `metrics` (array of objects) <span style="color: red">required</span>: A list of metrics in the format `{"scorer_type": "ap", "num_results": 10}`.
  - `scorer_type` (string) <span style="color: red">required</span>: The type of scorer. Possible values are `precision`, `ap`, `rr`, `cg`, `dcg`, `ndcg`, `cg_d`, `dcg_d`, `ndcg_d`.
  - `num_results` (integer) <span style="color: red">required</span>: The number of results to be evaluated.
- `keywords` (array of strings) <span style="color: red">required</span>: A list of keywords for the evaluation.
- `tags` (array of objects) <span style="color: grey">optional</span>: A list of tags for the evaluation in the format `{"id": 1}`.
  - `id` (integer) <span style="color: red">required</span>: The ID of the tag.
- `setting_feedback_strategy` (integer) <span style="color: red">required</span>: The feedback strategy. Possible values are `1` (Single), `3` (Multiple).
- `setting_show_position` (boolean) <span style="color: red">required</span>: Whether to show the position of the results.
- `setting_reuse_strategy` (integer) <span style="color: red">required</span>: The reuse strategy for grades. Possible values are `0` (No), `1` (Query/Doc), `2` (Query/Doc/Position).
- `setting_auto_restart` (boolean) <span style="color: red">required</span>: Whether to auto-restart the evaluation.
- `setting_scoring_guidelines` (string) <span style="color: grey">optional</span>: The scoring guidelines for the evaluation. You can use **Markdown** syntax in this field. For more information, see the <a href="https://www.markdownguide.org/basic-syntax/" target="_blank" class="text-blue-600 dark:text-blue-500 hover:underline">Markdown Guide</a> 
- `transformers` (object) <span style="color: red">required</span>: The transformers for the evaluation.
  - `scale_type` (string) <span style="color: red">required</span>: The source scale type of the transformers. Possible values are `binary`, `graded`, `detail`. Must match the `scale_type` of the evaluation.
  - `rules` (object) <span style="color: red">required</span>: The rules for the transformers. Must contain rules for each required scale type. If transformers are not required, set to an empty object.

<a name="request-example"></a>
## Request Example

```bash
curl -X POST "https://searchtweak.com/api/v1/evaluations" \ 
    -H "Authorization: Bearer YOUR_API_TOKEN" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{
          "model_id": 1,
          "name": "Sample Evaluation",
          "description": "This is a sample evaluation.",
          "keywords": [
            "apple",
            "dyson"
          ],
          "scale_type": "detail",
          "metrics": [
            { "scorer_type": "precision", "num_results": 10 },
            { "scorer_type": "ndcg", "num_results": 10 },
            { "scorer_type": "ndcg_d", "num_results": 5 }
          ],
          "transformers": {
            "scale_type": "detail",
            "rules": {
              "binary": {
                "1": 0,
                "2": 0,
                "3": 0,
                "4": 0,
                "5": 0,
                "6": 1,
                "7": 1,
                "8": 1,
                "9": 1,
                "10": 1
              },
              "graded": {
                "1": 0,
                "2": 1,
                "3": 1,
                "4": 1,
                "5": 2,
                "6": 2,
                "7": 2,
                "8": 3,
                "9": 3,
                "10": 3
              }
            }
          },
          "tags": [
            { "id": 9 }
          ],
          "setting_feedback_strategy": 1,
          "setting_reuse_strategy": 0,
          "setting_show_position": true,
          "setting_auto_restart": false,
          "setting_scoring_guidelines": "Scoring guidelines for the evaluation. You can use **Markdown** syntax in this field."
        }'
```
 
<a name="response-example"></a>
## Response Example
```json
{
  "id": 307,
  "model_id": 1,
  "scale_type": "detail",
  "status": "pending",
  "progress": 0,
  "name": "Sample Evaluation",
  "description": "This is a sample evaluation.",
  "settings": {
    "reuse": 0,
    "position": true,
    "strategy": 1,
    "auto_restart": false,
    "transformers": {
      "rules": {
        "binary": {
          "1": 0,
          "2": 0,
          "3": 0,
          "4": 0,
          "5": 0,
          "6": 1,
          "7": 1,
          "8": 1,
          "9": 1,
          "10": 1
        },
        "graded": {
          "1": 0,
          "2": 1,
          "3": 1,
          "4": 1,
          "5": 2,
          "6": 2,
          "7": 2,
          "8": 3,
          "9": 3,
          "10": 3
        }
      },
      "scale_type": "detail"
    },
    "setting_scoring_guidelines": "Scoring guidelines for the evaluation. You can use **Markdown** syntax in this field."
  },
  "metrics": [
    {
      "scorer_type": "precision",
      "num_results": 10,
      "value": null
    },
    {
      "scorer_type": "ndcg",
      "num_results": 10,
      "value": null
    },
    {
      "scorer_type": "ndcg_d",
      "num_results": 5,
      "value": null
    }
  ],
  "tags": [
    {
      "id": 9,
      "name": "All"
    }
  ],
  "keywords": [
    "apple",
    "dyson"
  ],
  "created_at": "2024-11-03T08:05:39+00:00",
  "finished_at": null
}
```
