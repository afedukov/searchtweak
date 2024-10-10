@component('mail::message')
Search evaluation {{ $evaluation->name }} has been completed. You can access the detailed evaluation report by clicking the button below:

@component('mail::button', ['url' => route('evaluation', $evaluation)])
	View Evaluation
@endcomponent

## Next Steps

With the evaluation results at your disposal, you can now:

- **Analyze Performance**: Dive into the metrics and feedback to understand how your search model is performing.
- **Identify Improvements**: Pinpoint areas where search relevance and user satisfaction can be enhanced.
- **Refine Model**: Use the insights gained to tweak and optimize your search model for better accuracy and efficiency.
- **Export Judgment List**: Export the judgment list to train your own ML models or perform advanced analytics.

To review the search model associated with this evaluation, click the button below:

@component('mail::button', ['url' => route('model', $evaluation->model)])
	View Search Model
@endcomponent

We encourage you to take full advantage of the evaluation results to continue enhancing your search quality. Should you have any questions or need further assistance, please do not hesitate to contact us.
@endcomponent
