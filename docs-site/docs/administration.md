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

### Single Sign-On (SSO)

SearchTweak supports authentication via an external OpenID Connect identity provider (Keycloak, Azure AD, Okta, or any OIDC-compatible service) using the `socialiteproviders/keycloak` package.

#### Configuration

Before enabling SSO, set the following environment variables in your `.env` file:

| Variable | Description | Example |
|----------|-------------|---------|
| `OIDC_CLIENT_ID` | OAuth client ID from your identity provider | `searchtweak-app` |
| `OIDC_CLIENT_SECRET` | OAuth client secret | `your-secret` |
| `OIDC_BASE_URL` | Base URL of your identity provider (without `/realms/...`) | `https://idp.example.com` |
| `OIDC_REALM` | Realm name (Keycloak-specific, defaults to `master`) | `searchtweak` |
| `OIDC_BUTTON_LABEL` | Label for the SSO button on the login page | `Sign in with SSO` |

If `OIDC_CLIENT_ID` and `OIDC_BASE_URL` are not configured, the SSO toggles in Settings will be disabled with a warning message.

#### SSO Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **SSO (OpenID Connect)** | Enable the "Sign in with SSO" button on the login page. Users can authenticate via the configured identity provider. | Disabled |
| **SSO Only Mode** | Hide the email/password login form entirely. Users must authenticate via SSO. Can only be enabled when SSO is on. | Disabled |

#### How SSO Login Works

1. User clicks **Sign in with SSO** on the login page.
2. User is redirected to the identity provider for authentication.
3. After successful authentication, the user is redirected back to SearchTweak.
4. If a user with the same email already exists, they are logged in and their account is linked to the OIDC provider.
5. If no user exists with that email, a new account is created automatically with a verified email and a personal team.

::: tip
SSO users receive a random password on creation and cannot use the email/password login form. They must always authenticate via the identity provider.
:::

#### SSO Only Mode

When **SSO Only Mode** is enabled:

- The email/password form is hidden from the login page.
- The registration page and "Get Started" links are hidden.
- Only the SSO button is displayed.

**Fallback access**: Super Admins can always access the email/password form by appending `?fallback=1` to the login URL (e.g., `https://your-domain.com/login?fallback=1`). This is useful if the identity provider is temporarily unavailable.

#### Logout Behavior

- **SSO users** are redirected to the identity provider's logout endpoint, which ends both the SearchTweak session and the IdP session.
- **Non-SSO users** are redirected to the standard login page.

::: warning
Disabling **SSO** automatically disables **SSO Only Mode** to prevent users from being locked out of the platform.
:::
