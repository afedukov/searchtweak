# SearchTweak

SearchTweak is a web application designed to evaluate and optimize search engine results. It provides comprehensive tools for assessing the relevance and performance of search outcomes, enabling users to enhance their search functionalities effectively. Additionally, SearchTweak can be utilized for labeling data to train machine learning models and for conducting advanced analytics, making it a versatile tool for both search optimization and data-driven decision-making.

## Clone the Repository

```bash
git clone https://github.com/afedukov/searchtweak.git
```

## Setup Environment

Navigate to the `/devops` directory and copy the `.env.dist` file to `.env`:
```bash
cd searchtweak/devops
cp .env.dist .env
```

## Configure Hosts File

Edit your `/etc/hosts` file (or `C:\Windows\System32\drivers\etc\hosts` on Windows) and add the following line:
```bash
127.0.0.1    searchtweak.local traefik.searchtweak.local db.searchtweak.local
```
This will allow you to access the following services:

- App: http://searchtweak.local
- Traefik Dashboard: http://traefik.searchtweak.local
- phpMyAdmin: http://db.searchtweak.local
- Mailhog: http://localhost:8025

## Start and Bootstrap the Application

While in the `/devops` directory, run the following command to start and bootstrap the application:
```bash
make
```

## Other Useful Commands

```bash
make start        # Start the application
make stop         # Stop the application
make bootstrap    # Bootstrap the application
make vite         # Start Vite development server
make vite-prod    # Build Vite for production
```

## Email Setup

By default, **SearchTweak** uses [MailHog](https://github.com/mailhog/MailHog) as the SMTP server. MailHog is a popular email testing tool that captures outgoing emails and provides a web UI for viewing them. It is ideal for development environments where you don't want to send real emails.

### Accessing MailHog

To access the MailHog interface, go to:

- http://localhost:8025

### Configuring Real Email Sending

If you need to send real emails, you can remove the `mailhog` service from the `/devops/docker-compose.yml` file. After removing the MailHog service, you can configure a real SMTP server or any other Laravel mailer (e.g., Amazon SES) by updating the following environment variables in your `.env` file:

#### Example for SMTP
```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-email-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Example for Amazon SES
To use Amazon SES, you need to configure the following environment variables with your AWS credentials:
```dotenv
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_SES_REGION=us-east-1
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Enforcing Email Verification
If you require users to verify their email addresses before logging into their accounts, follow these steps:

### 1. Update the User Model
In the user model `\App\Models\User`, implement the `MustVerifyEmail` interface. This ensures that Laravel handles email verification for users.
```php
class User extends Authenticatable implements TaggableInterface, MustVerifyEmail
{
    // ...
}
```

### 2. Enable Email Verification in Fortify
In the `config/fortify.php` file, uncomment the line that enables email verification:
```php
'features' => [
    // ...
    Features::emailVerification(),
    // ...
],
```

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request with your enhancements or bug fixes.

## License

This project is licensed under the Functional Source License, Version 1.1, with an irrevocable grant to the Apache License, Version 2.0 effective on the second anniversary of the software's release.

### Abbreviation
FSL-1.1-Apache-2.0

### Notice
Copyright 2024 Andrey Fedyukov

### Summary
- You may use, modify, and redistribute the software for any purpose, except in products or services that compete with the software or any other product or service we offer.
- This license includes a patent grant, but that grant ends if you make a patent claim against the software.
- Redistribution requires including a copy of the license and not removing any copyright notices.
- After two years, you may alternatively use the software under the Apache License, Version 2.0.

See the full [LICENSE](LICENSE.md) file for details.
