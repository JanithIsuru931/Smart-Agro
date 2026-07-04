# Smart Agro

<div align="center">

<img width="1712" height="962" alt="Screenshot From 2026-07-04 08-57-07" src="https://github.com/user-attachments/assets/244badf3-03be-42a2-b58a-b551d2164f52" />

</div>

Smart Agro is a Laravel 13 application built with Livewire 4 and Flux UI for managing an agricultural storefront, customer checkout, bulk inquiries, and an internal admin workflow for products, suppliers, employees, attendance, payroll, and orders.

## What This Project Does

- Public storefront with product browsing, cart, checkout, and order confirmation pages.
- PayHere payment flow with success, cancel, and server-to-server notification handling.
- Bulk inquiry form for customers requesting larger or custom orders.
- About page with contact form submission.
- Authenticated dashboard with role-based routing.
- Admin panel for products, suppliers, supplier purchases, employees, attendance, employee payments, orders, and inquiries.
- Fortify-powered authentication for registration, login, password reset, and email verification.
- User settings pages for profile, appearance, and security.

## Tech Stack

- PHP 8.4+
- Laravel 13
- Livewire 4
- Flux UI 2
- Tailwind CSS 4
- Pest 4
- SQLite by default in local development

## Requirements

- PHP 8.4 or newer
- Composer
- Node.js 20+ and npm
- A supported database if you do not want to use the default SQLite setup

## Getting Started

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

Copy the example environment file and generate an application key if needed:

```bash
cp .env.example .env
php artisan key:generate
```

By default, the starter environment uses SQLite, a database-backed cache/session/queue setup, and log-based mail delivery for local development.

### 3. Create the database

If you are using SQLite locally, make sure the database file exists before migrating:

```bash
touch database/database.sqlite
```

Then run migrations:

```bash
php artisan migrate
```

### 4. Start the app

For local development, you can run everything through the bundled Composer script:

```bash
composer dev
```

That starts the Laravel server, queue listener, Pail logs, and Vite in one process.

If you prefer running pieces separately, use:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0
```

## Environment Variables

The most important environment values for this project are:

- `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`
- `DB_CONNECTION`, `DB_DATABASE`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `PAYHERE_MERCHANT_ID`, `PAYHERE_MERCHANT_SECRET`, `PAYHERE_SANDBOX`
- `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_WHATSAPP_FROM`, `OWNER_WHATSAPP_NUMBER`
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` if you use S3

## Available Scripts

### Composer

- `composer setup` installs dependencies, creates `.env`, generates the app key, runs migrations, installs Node dependencies, and builds assets.
- `composer dev` starts the local full-stack development environment.
- `composer lint` formats PHP files with Pint.
- `composer lint:check` checks Pint formatting without changing files.
- `composer test` clears config, checks formatting, and runs the test suite.

### NPM

- `npm run dev` starts Vite in development mode.
- `npm run build` compiles production assets.

## Main Areas

### Public Site

- `/` storefront homepage
- `/cart` shopping cart
- `/checkout` checkout flow
- `/orders/{order}/confirmation` order success page
- `/bulk-orders` bulk inquiry form
- `/about` about page and contact form

### Authentication and Settings

- Fortify handles auth pages for login, registration, password reset, verification, and password confirmation.
- `/settings/profile`
- `/settings/appearance`
- `/settings/security`

### Admin Panel

All admin routes are protected by `auth`, `verified`, and `admin` middleware and are available under `/admin`.

- `/admin` dashboard
- `/admin/products`
- `/admin/suppliers`
- `/admin/supplier-purchases`
- `/admin/employees`
- `/admin/attendance`
- `/admin/employee-payments`
- `/admin/orders`
- `/admin/inquiries`

## Project Structure

- `app/Models` contains the main domain models for products, suppliers, orders, employees, and inquiries.
- `app/Http/Controllers` contains the payment notification controller and supporting controllers.
- `app/Providers` holds framework and Fortify service providers.
- `resources/views/pages` contains the Livewire page views for storefront, admin, auth, and settings screens.
- `resources/views/pdf` contains downloadable PDF templates.
- `routes/web.php` defines the storefront, checkout, admin, and contact routes.
- `routes/settings.php` defines the authenticated settings routes.
- `database/migrations` contains the schema for products, orders, suppliers, employees, attendance, payments, and related fields.

## Testing

This project uses Pest.

Run the full test suite with:

```bash
composer test
```

Or run Laravel tests directly:

```bash
php artisan test
```

Feature tests already cover key areas such as storefront behavior, bulk inquiries, authentication, settings, admin access, product and supplier management, employee attendance, revenue calculations, and PayHere notifications.

## Deployment Notes

- Build assets before deploying with `npm run build`.
- Run migrations on the target environment.
- Ensure queue workers are running if you depend on database-backed jobs or notifications.
- Configure PayHere and Twilio credentials if you need live payment and messaging integrations.
- Update `APP_URL` and mail settings for the production environment.

## License

This project is licensed under the MIT License.
