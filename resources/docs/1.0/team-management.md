# Team Management

- [Concept](#concept)
- [Roles and Permissions](#roles)
- [Interface](#interface)

<a name="concept"></a>
## Concept

Team Management in Search Tweak empowers users to efficiently create and oversee teams. Users can form teams and invite others, assigning them specific roles. There are two roles available: `Administrator` and `Search Evaluator`. Administrators have complete access to all features, while Search Evaluators are limited to evaluating search results. Administrators can also change user roles and remove users from the team if necessary.

All resources, including search endpoints, models, and evaluations, are created within the team context, ensuring they are accessible and manageable by the team.

<a name="roles"></a>
## Roles and Permissions

- **Owner**
    - The user who creates the team.
    - Same permissions as an Administrator.

- **Administrator**
    - Full access to all features, including creating and managing search endpoints, models, and search evaluations; starting, stopping, and finishing search evaluations; managing user feedback.
    - Ability to invite and manage team members.
    - Authority to change user roles within the team.
    - Ability to manage team tags.
    - Ability to manage assigned tags for team members.
    - Capability to remove users from the team.

- **Search Evaluator**
    - Limited to evaluating search results.

<a name="interface"></a>
## Interface

<img src="/images/docs/team.png" alt="Team Management" style="width: 100%; max-width: 1167px; height: auto;">

The Team Management interface offers the following functionalities:

### Team Member List

View all team members, their roles, and their assigned tags.

### Add User

Invite new users to join the team. You can also see pending invitations — users who have been invited but have not yet accepted.

<img src="/images/docs/invite.png" alt="Add User" style="width: 100%; max-width: 450px; height: auto;">

### Send Message

A dialog to send notifications to selected recipients:

- **All**: Message all team members.
- **Administrator**: Message all Administrators.
- **Evaluator**: Message all Evaluators.

<img src="/images/docs/send-message.png" alt="Send Message" style="width: 100%; max-width: 450px; height: auto;">

---

Feel free to explore other sections of the documentation to get a better understanding of how to set up and use Search Tweak effectively.
