# Staff Directory Application v1.0

## Overview
This Staff Directory application is a web-based system designed to manage and display an organization's staff members. It includes both an administrative interface for managing staff data and a public-facing interface for viewing the staff directory.

## Purpose
The primary purpose of this application is to provide organizations with a simple yet effective way to maintain and showcase their staff information. It allows for easy management of staff profiles, including personal details, department affiliations, job titles, and profile pictures.

## Tech Stack
The application is built using the following technologies:

### Backend
- **PHP 8.x**: Server-side scripting language
- **Laravel 12.x**: PHP web application framework
- **MySQL**: Relational database management system

### Frontend
- **HTML5/CSS3**: Structure and styling
- **Bootstrap 5.x**: CSS framework for responsive design
- **JavaScript**: Client-side interactivity
- **Blade**: Laravel's templating engine

### Server
- **Apache**: Web server (recommended)
- **MySQL Server**: Database server

## Project Structure
The application follows Laravel's standard MVC (Model-View-Controller) architecture:

```
staff-directory/
├── app/                        # Application code
│   ├── Http/
│   │   ├── Controllers/        # Controllers that handle HTTP requests
│   │   │   └── StaffController.php  # Staff management controller
│   ├── Models/                 # Database models
│   │   └── Staff.php           # Staff model for database interaction
├── database/
│   ├── migrations/             # Database migration files
│   └── seeders/                # Database seeders
├── public/                     # Publicly accessible files
│   ├── images/                 # Default images
│   └── uploads/                # Uploaded profile pictures
├── resources/
│   ├── views/                  # Application views (Blade templates)
│   │   ├── admin/              # Admin dashboard views
│   │   │   ├── dashboard.blade.php  # Staff list and management
│   │   │   ├── create.blade.php     # Add new staff form
│   │   │   └── edit.blade.php       # Edit staff form
│   │   ├── layouts/            # Layout templates
│   │   └── staff/              # Public staff directory views
│   │       └── index.blade.php  # Public staff listing
├── routes/                     # Route definitions
│   └── web.php                 # Web routes
```

## Features

### Admin Dashboard
- Secure login system for administrators
- Add new staff members with detailed information
- Upload and manage staff profile pictures
- Edit existing staff information
- Delete staff members
- View all staff in a tabular format

### Public Directory
- Grid view of all staff members
- Profile cards with staff details and images
- Organized display of staff information

## Installation

### Prerequisites
- PHP 8.0 or higher
- Composer
- MySQL Server
- Apache Server (or another web server with PHP support)
- Node.js and NPM (for asset compilation)

### Setup Instructions

1. **Clone the Repository**
   ```
   git clone <repository-url>
   cd staff-directory
   ```

2. **Install PHP Dependencies**
   ```
   composer install
   ```

3. **Install JavaScript Dependencies**
   ```
   npm install
   ```

4. **Compile Frontend Assets**
   ```
   npm run dev
   ```

5. **Configure Environment Variables**
   Create a `.env` file from the example:
   ```
   cp .env.example .env
   ```

   Update the following variables in your `.env` file:
   ```
   APP_NAME="Staff Directory"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=staff_directory
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

6. **Generate Application Key**
   ```
   php artisan key:generate
   ```

7. **Run Migrations and Seed the Database**
   ```
   php artisan migrate
   php artisan db:seed
   ```

## Running the Application

### Development Server
```
php artisan serve
```
This will start a development server at `http://localhost:8000`

### Production Deployment
For production, configure Apache or Nginx to serve the application. The document root should be set to the `public` directory of the project.

## Default Admin Account
After seeding the database, a default admin account is created:
- **Email**: admin@example.com
- **Password**: password

**Important**: Change these credentials immediately in a production environment!

## Directory Structure Configuration
For Apache, ensure that the `DocumentRoot` directive in your Apache configuration points to the `public` directory of your Laravel application.

## License
MIT License

Copyright (c) 2025 Jensen SIU

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Contributors
Jensen SIU <contact@jensen-siu.net>

## To Do
- Remove the login from the Public staff directory views
- Add email address to the staff model
- Setup a functional registering confirmation email or remove this option
- Test the password reset page
- Add an administrator profile page
- Add public page and dashboard multilingual support
- Enhance the public page UI/UX
