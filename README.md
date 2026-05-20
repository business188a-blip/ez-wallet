# EZ Wallet Secure Financial Transaction

A complete PHP 8 + MySQL web application for secure wallet-style financial transactions.

## Features

- Public pages: Home, About, Contact
- User authentication: register, login, logout, forgot/reset password
- User dashboard with wallet balance, notifications, recent activity
- Add money simulation
- Send money between registered users
- Bill payment
- Mobile recharge
- Transaction history with filters and printable detail page
- In-app notifications
- Profile management with password change and avatar upload
- Admin dashboard
- User management
- Transaction monitoring
- Bill category and recharge operator management
- Contact message review
- Reports and analytics
- Security helpers: CSRF, prepared statements, output escaping, authorization guards

## Environment

- PHP 8.0+
- MySQL / MariaDB
- Apache (XAMPP / Laragon)

## Setup

1. Copy the folder `ez_wallet_secure` into your `htdocs` or `www` directory.
2. Create a database named `ez_wallet_secure`.
3. Import `database/ez_wallet_secure.sql`.
4. Update DB credentials in `config/app.php`.
5. Open `http://localhost/ez_wallet_secure/public/`

## Test credentials

### Admin
- Email: `admin@ezwallet.local`
- Password: `Admin@12345`

### Users
- Email: `ali@ezwallet.local`
- Password: `User@12345`
- Email: `sara@ezwallet.local`
- Password: `User@12345`
