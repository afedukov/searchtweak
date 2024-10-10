# Search Evaluations

- [Concept](#concept)
- [Components of a Search Evaluation](#components-of-a-search-evaluation)
- [Starting an Evaluation](#starting-an-evaluation)
- [Managing Search Evaluations](#managing-search-evaluations)
- [Evaluation Details](#evaluation-details)

<a name="concept"></a>
## Concept

A **Search Evaluation** is the core concept entity in Search Tweak, around which the entire platform is built. It is a detailed process used to measure and analyze the performance of search models. By configuring evaluation parameters, metrics, and keywords, Search Evaluations assess the effectiveness and relevancy of search results, providing crucial insights for optimization.

<a name="components-of-a-search-evaluation"></a>
## Components of a Search Evaluation

#### Name
The name of the search evaluation. This should be a descriptive title that clearly indicates the purpose or focus of the evaluation.

#### Description (Optional)
A detailed description of what the search evaluation does, including its intended use cases and any specific features or configurations.

#### Associated Model
The search model that is associated with this evaluation. This defines the configuration and parameters used for the search requests.

#### Metrics
A list of metrics to measure by this evaluation. All metrics must be of the same scale type (`Binary Scale` or `Graded Scale`). Metrics can include:
- `P@10` (Precision at 10)
- `AP@10` (Average Precision at 10)
- `RR@10` (Reciprocal Rank at 10)
- `CG@10` (Cumulative Gain at 10)
- `DCG@10` (Discounted Cumulative Gain at 10)
- `nDCG@10` (Normalized Discounted Cumulative Gain at 10)
- ... or any other `@k`

<img src="/images/docs/scorers.png" alt="Metrics" style="width: 100%; max-width: 403px; height: auto;">

If there are more than one keyword, the metrics will be calculated for each keyword separately and then averaged (Mean Average Precision, Mean Average Recall, etc.).

Read more about [Evaluation Metrics](/{{route}}/{{version}}/evaluation-metrics).

#### Keywords List
A list of keywords, one per line, used for the evaluation. If the associated search model has a predefined set of keywords, this list will initially be prefilled and shown with a closed lock icon <i class="fas fa-lock"></i>, indicating it is in a read-only state. However, you can still modify the keywords by clicking on the lock icon to unlock it for editing.

#### Advanced Settings (Optional)
Advanced settings for fine-tuning the evaluation process:
- **Feedback Strategy**:
    - **Single**: Only one feedback is needed for each query/document pair. Provides quick results but may sacrifice quality.
    - **Multiple**: Allows for up to three feedbacks per query/document pair, resulting in higher quality assessments albeit requiring more effort.

<img src="/images/docs/feedback-strategy.png" alt="Feedback Strategy" style="width: 100%; max-width: 568px; height: auto; margin-left: 60px">

- **Show Position**: Specify whether to reveal the position or rank of a document returned in the search results to the Evaluator. Options are `yes` or `no`. Normally, you wouldn't want to reveal this information, but it may be necessary when comparing against a pre-existing search engine results page.

<img src="/images/docs/show-position.png" alt="Show Position" style="width: 100%; max-width: 567px; height: auto; margin-left: 60px">

- **Auto-Restart**: Specify whether to automatically create and start a new evaluation with the same settings when the current evaluation is completed. Options are `yes` or `no`.

<img src="/images/docs/auto-restart.png" alt="Auto-Restart" style="width: 100%; max-width: 567px; height: auto; margin-left: 60px">

- **Assigned Tags**: Team tags assigned to this evaluation. Only Search Evaluator users with those tags will be able to evaluate this search evaluation. Read more about [Team Management](/{{route}}/{{version}}/team-management) and [Tags](/{{route}}/{{version}}/tags).

<img src="/images/docs/evaluation-tags.png" alt="Evaluation Tags" style="width: 100%; max-width: 567px; height: auto; margin-left: 60px">

- **Re-use Grades Strategy**: Specify whether to re-use grades from previous evaluations. Options are:
    - **No**: Do not re-use grades from previous evaluations.
    - **Query/Doc**: Re-use the grade for the same query/document pair.
    - **Query/Doc/Position**: Re-use the grade for the same query/document pair and position.

<img src="/images/docs/reuse-strategy.png" alt="Re-use Grades Strategy" style="width: 100%; max-width: 567px; height: auto; margin-left: 60px">

**Note**: If tags are defined for a search evaluation, the reuse strategy will respect these tags and reuse only the grades provided by users who are permitted by the search evaluation tags.

**Note**: Reuse strategy cannot be combined with the `Auto-Restart` option.

<a name="starting-an-evaluation"></a>
## Starting an Evaluation

When an evaluation is started for the first time, requests to the search endpoint are made, and search snapshots for all provided keywords are created and stored. This ensures that all evaluators see and assess the same search results, maintaining consistency and accuracy in the evaluation process.

<a name="managing-search-evaluations"></a>
## Managing Search Evaluations

<img src="/images/docs/evaluations.png" alt="Search Evaluation Interface" style="width: 100%; max-width: 1433px; height: auto;">

The Search Evaluations interface provides the following functionalities:

- **List Evaluations**: View all existing search evaluations.
- **Create Evaluation**: Add a new search evaluation with the required configuration details.
- **Edit Evaluation**: Modify the details of an existing search evaluation.
- **Clone Evaluation**: Duplicate an existing search evaluation to create a new one with similar settings.
- **Delete Evaluation**: Remove a search evaluation that is no longer needed.
- **Start/Pause/Finish Evaluation**: Manage the status of your evaluations.
- **Export Evaluation**: Create a judgment list and export the evaluation data.
- **Go to User Feedback**: View the list of user feedback for this evaluation.
- **Go to Give Feedback**: Access the search evaluator interface to assess this evaluation.
- **Filter by Tag**: Quickly filter evaluations based on assigned tags.
- **Filter by Status**: Filter evaluations by their current status (e.g., active, paused, completed).

These features ensure that you have full control over your search evaluations, making it easy to maintain and optimize your search configurations.

For more details and to manage your search evaluations, visit the [Search Evaluations](/evaluations) in the application.

<a name="evaluation-details"></a>
## Evaluation Details

On the evaluation detailed view page, there are graphics for each of the metrics, which show the last 20 values of the metric. Each metric graph can be added to the dashboard as a widget, and the evaluation itself can also be added to the dashboard as a widget.

Additionally, all search snapshots for each keyword and all evaluators' assessments are available on this page. These snapshots and assessments can be observed and reset by an Admin user.

<img src="/images/docs/evaluations2.png" alt="Search Evaluation Interface" style="width: 100%; max-width: 1415px; height: auto;">

---

Feel free to explore other sections of the documentation to get a better understanding of how to set up and use Search Tweak effectively.
