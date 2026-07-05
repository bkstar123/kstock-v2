# KStock - Financial Statement Management System

## Overview
KStock is a web-based application designed to manage and track financial statements of securities symbols. Built with Laravel 5.8, this system provides a robust platform for investors and financial institutions to monitor and analyze financial reports of listed companies.

## Features

### Financial Statement Management
- Pull financial statements for specific stock symbols
- View and manage a list of financial statements
- Detailed view of individual financial statements
- Bulk and single deletion capabilities
- Quarterly and yearly tracking
- Historical data storage

### User Management & Security
- Role-based access control (SUPERADMINS and regular users)
- User-specific data visibility
- Authentication and detailed authorization system
- Request throttling to prevent system overload
- Asynchronous processing with job queues
- Real-time notifications

### Admin Interface
- Intuitive dashboard for overview
- System settings management
- Search and filter capabilities
- Paginated results
- AdminLTE 3.0 based interface

## Technical Stack

### Backend
- PHP 
- Laravel
- MySQL
- Pusher for real-time features

### Frontend  
- Bootstrap  
- Highchart  
- jQuery
- AdminLTE 
- jQuery DateTime Picker  

## Installation

1. Clone the repository:
```bash
git clone https://github.com/bkstar123/kstock.git
```

2. Install PHP dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

## Usage

1. Access the application through your web browser
2. Login with your admin credentials
3. Navigate to the dashboard
4. Start managing financial statements:
   - Pull new financial statements
   - View existing statements
   - Manage and analyze data

## Security
- All routes are protected with authentication
- Role-based access control
- Request throttling implemented

## Author
- **Name**: Hoang Anh Tuan

## License
This project is licensed under the MIT License - see the LICENSE file for details.


