# RealEstateHub — Corrected XAMPP Project

## Location
Copy the `realestate` folder to:
`C:\xampp\htdocs\RealEstate`

## Database
1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin`.
3. Import `database/realestatehub.sql`.
4. The database name is `realestatehub`.

Default local database connection:
- Host: localhost
- User: root
- Password: empty

If your XAMPP MySQL password is different, edit `config/database.php`.

## Login
Admin:
- Email: `admin@realestatehub.com`
- Password: `password`

## Main URLs
- Home: `http://localhost/RealEstate/`
- Login: `http://localhost/RealEstate/auth/login.php`
- Register: `http://localhost/RealEstate/auth/register.php`
- Properties: `http://localhost/RealEstate/page/properties.php`
- Admin: `http://localhost/RealEstate/admin/dashboard.php`

## Structure
The project uses one canonical API layout under `api/`. Legacy nested API folders were removed.
The database schema is unified to support the existing PHP pages and APIs, including users, agents, properties, favorites, enquiries, visits, messages, notifications, settings, images and reporting support.

## Verification performed
- PHP syntax checked across all PHP files.
- Local href/src file targets checked for missing static files.
- Database connection code exposes both PDO (`$pdo`) and mysqli (`$conn`) because the existing application contains both styles.
- Placeholder image assets are included so the UI does not show broken local image links.
