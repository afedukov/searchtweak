# Administration

The Administration section is available to **Super Admins** only. It provides platform-wide management capabilities that go beyond team-level settings.

Super Admin access is indicated by a **Super Admin** badge next to the user's name in the Users list.

## Users

The Users page (`Administration → Users`) gives full visibility and control over all registered users across the platform.

### Search

Use the search box to find users by name or email.

### Create User

Click **Create User** to open a form with the following fields:

- **Name** — the user's display name.
- **Email** — must be unique across the platform.
- **Password** — enter manually or switch to the **Generate Password** tab to auto-generate a random 16-character password. Use the copy button inside the input field to copy the generated password to the clipboard.

Users created by a Super Admin are automatically email-verified and assigned a personal team.

### User Actions

Each user row has a context menu (three-dot icon) with the following actions:

- **Impersonate** — log in as the selected user to see the platform from their perspective. Available for all users except yourself.
- **Grant / Revoke Super Admin** — toggle the Super Admin role for the selected user. Requires confirmation. You cannot change your own Super Admin status.
- **Delete** — permanently remove the user and their data. Requires confirmation.

### Email Verification

The **Verified** column shows whether a user has verified their email:

- A **blue badge** with a timestamp indicates a verified email.
- A **red "Not verified"** button allows you to manually verify the user's email with one click (requires confirmation).

## Settings

The Settings page (`Administration → Settings`) controls platform-wide authentication features. Changes take effect immediately for all users.

### Authentication

| Setting | Description | Default |
|---------|-------------|---------|
| **Registration** | Allow new users to register accounts. When disabled, the registration page and "Sign Up" links are hidden. New users can only be created by a Super Admin. | Enabled |
| **Password Reset** | Allow users to reset their passwords via email. When disabled, the "Forgot Password?" link is hidden from the login page. | Enabled |
| **Email Verification** | Require users to verify their email address before accessing the platform. Unverified users are redirected to a verification prompt. | Disabled |
| **Two-Factor Authentication** | Allow users to enable two-factor authentication (TOTP) on their accounts. When disabled, users cannot set up or use 2FA. | Enabled |

Each setting is a toggle switch. A confirmation toast appears after each change.

::: tip
When **Registration** is disabled, you can still add users via the **Create User** button on the Users page.
:::

::: warning
Enabling **Email Verification** will immediately require all unverified users to verify their email before they can access the platform. Make sure existing users have verified emails before turning this on.
:::
