# Tags

Tags are used for segmentation, routing, and access control.

## Where Tags Are Used

- **Users**: defines what evaluations they can grade.
- **Search Models**: acts as default tag set for new evaluations.
- **Search Evaluations**: controls eligible evaluators and reuse scope.
- **Judges**: can be segmented by tag strategy.

## Matching Logic

Evaluation access uses **AND logic** for tags:

- evaluator must have all tags assigned to the evaluation

## Why Tags Matter

- separate domains/languages/markets
- route work to specialized evaluators
- improve reuse consistency within relevant cohorts
- reduce accidental cross-domain grading

## Practical Tag Strategy

Prefer stable dimensions such as:

- locale (`de`, `en`, `fr`)
- catalog segment (`electronics`, `fashion`)
- business lane (`b2b`, `b2c`)

Avoid overly granular one-off tags that fragment assignment pools.
