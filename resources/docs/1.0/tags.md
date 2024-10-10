# Tags

- [Concept](#concept)
- [Tags Management](#management)
- [Assigning Tags](#assigning)
- [Tag Structure](#structure)
- [Interface](#interface)
- [Example](#example)

<a name="concept"></a>
## Concept

Tags in Search Tweak provide a powerful way to control which Evaluators are responsible for evaluating specific Search Evaluations and Models. Tags help in organizing and managing evaluations more effectively by ensuring that the right evaluators are assigned to the right tasks.

<a name="management"></a>
## Tags Management

Tags can be created and managed in the Team Management interface. Tags are team-wide, meaning they are available to all members of the team and can be assigned to users, search models, and search evaluations.

<img src="/images/docs/tags.png" alt="Tags" style="width: 100%; max-width: 584px; height: auto;">

<a name="assigning"></a>
## Assigning Tags

Tags can be assigned to the following entities:

- **User**: Assigned tags determine which search evaluations a user can evaluate. Only evaluations with matching tags will be available for the user to assess.
- **Search Model**: Assigned tags set the default tags for all search evaluations created under this model. This helps in maintaining consistency across evaluations derived from the same model.
- **Search Evaluation**: Assigned tags specify which users are eligible to evaluate the search evaluation. Only users with matching tags will be able to assess the evaluation.

<a name="structure"></a>
## Tag Structure

A tag is a combination of a color and a label. This allows for easy identification and categorization of evaluations and evaluators.

<a name="interface"></a>
## Interface

The Tags interface provides the following functionalities:

- **Create Tag**: Add new tags with specific colors and labels.
- **Assign Tags**: Allocate tags to users, search models, and search evaluations.
- **Manage Tags**: View and edit existing tags.

<img src="/images/docs/create-tag.png" alt="Tags" style="width: 100%; max-width: 585px; height: auto;">

<a name="example"></a>
## Example

Here is an example of how tags can be used in different contexts:

- **User Tag**: A user with the `German` tag can only evaluate search evaluations that are also tagged with `German`.
- **Search Model Tag**: A search model tagged with `b2b` will have all its evaluations tagged with `b2b` by default.
- **Search Evaluation Tag**: A search evaluation tagged with `High Priority` will only be available to users who have the `High Priority` tag.

---

Feel free to explore other sections of the documentation to get a better understanding of how to set up and use Search Tweak effectively.
