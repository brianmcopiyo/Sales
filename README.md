# Phone Distribution and Sales System

A comprehensive Laravel-based phone distribution and sales management system with stock management from head branch to regional branches, including a ticket management system for customer support.

## Features

- **Stock Management**: Transfer stock from head branch to regional branches
- **Sales Management**: Complete sales system with multiple items per sale
- **Branch Management**: Manage head and regional branches
- **Product Management**: Add, edit, and manage phone products
- **Ticket Management**: Customer support ticket system with replies
- **User Roles**: Admin, Head Branch Manager, Regional Branch Manager, Staff, and Customer
- **Dashboard**: Overview of sales, transfers, tickets, and stock levels

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Configure your database in `.env` file
6. Run migrations:
   ```bash
   php artisan migrate
   ```
7. Fix storage permissions (Linux/Unix servers):
   ```bash
   # Set ownership (replace www-data with your web server user if different)
   sudo chown -R www-data:www-data storage bootstrap/cache
   
   # Set directory permissions
   sudo chmod -R 775 storage bootstrap/cache
   
   # Or if you're running as your user (not www-data):
   sudo chmod -R 775 storage bootstrap/cache
   sudo chown -R $USER:www-data storage bootstrap/cache
   ```
8. Seed the database:
   ```bash
   php artisan db:seed
   ```
9. Create storage link:
   ```bash
   php artisan storage:link
   ```
10. Start the development server:
   ```bash
   php artisan serve
   ```

## Default Login Credentials

After seeding, you can login with:

- **Admin**: admin@company.com / password
- **Head Branch Manager**: headmanager@company.com / password
- **Regional Manager**: regional1@company.com / password
- **Staff**: staff1@company.com / password
- **Customer**: customer1@example.com / password

## Color Scheme

- Primary Color: #006F78
- Secondary Color: #E48A22

## Technologies Used

- Laravel 10
- Tailwind CSS (via CDN)
- MySQL Database
- Blade Templates

## User Roles

- **Admin**: Full system access
- **Head Branch Manager**: Can manage products, view all branches, create stock transfers
- **Regional Branch Manager**: Can manage their branch, create sales, view their stock
- **Staff**: Can create sales, view stock, manage tickets
- **Customer**: Can view their purchases, create support tickets

## License

MIT
