# Kantayoktok Sporting and Cockpit Arena

A daily monitoring system for **Kantayoktok Sporting and Cockpit Arena**. This project combines a PHP-based web application with an Electron desktop wrapper so staff can manage daily attendance, income, expenses, and end-of-day reporting from a single interface.

## Features

- **Guard gate monitoring** for first entries, re-entries, and exits
- **Live headcount tracking** for people inside the venue
- **Cashier / finance workflow** for recording daily income and expenses
- **Admin dashboard** for reviewing daily operational and financial summaries
- **Day closing controls** to lock completed daily records
- **Demo data reset / start demo day** actions for testing and training
- **Desktop wrapper** via Electron for a kiosk-style app experience

## Project Structure

- `main.js` — Electron entry point
- `index.php` — main application landing page
- `guard_gate.php` — gate entry screen for guards
- `cashier_finance.php` — finance entry screen for cashier use
- `admin_dashboard.php` — admin summary dashboard
- `api.php` — JSON API for attendance and finance actions
- `config.php` — database and session configuration

## Requirements

- PHP 8+ with MySQLi enabled
- MySQL/MariaDB database
- Node.js 18+ (for Electron development and packaging)
- Electron dependencies installed through npm
- A local web server such as Apache or XAMPP/WAMP/Laragon

## Setup

1. Clone the repository.
2. Install Node dependencies:

   ```bash
   npm install
   ```

3. Create the database used by the app.
4. Update database settings in `config.php` if needed:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'sabong_new');
   ```

5. Make sure the PHP files are served by your local web server.
6. Start the Electron wrapper:

   ```bash
   npm start
   ```

## Build

To build the desktop application:

```bash
npm run build
```

## Notes

- The Electron app loads the PHP system from `http://localhost/sabong-new/index.php`.
- If your local folder or server path is different, update the URL in `main.js`.
- The app uses role-based access control for guard, cashier, and admin users.

## License

ISC
