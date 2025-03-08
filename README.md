# PartTimePro - Part-Time Job Portal

PartTimePro is a specialized job portal designed to connect part-time job seekers (students, homemakers, and individuals looking for flexible work) with employers offering part-time opportunities. The platform focuses on roles such as shop keeping, security, data entry, tutoring, and similar positions that are often advertised through traditional means like pamphlets.

## Features

### For Job Seekers
- Create and manage professional profiles
- Upload resumes (Word/PDF formats)
- Search and filter job listings
- Apply for jobs directly through the platform
- Message employers about job opportunities
- Track application status

### For Employers
- Post part-time job opportunities
- View and manage job applications
- Access candidate profiles and resumes
- Direct messaging with potential candidates
- Manage job listings (open/close positions)

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (recommended for local development)

## Installation

1. Clone the repository to your local machine:
```bash
git clone [repository-url]
```

2. Import the database schema:
- Create a new database named 'parttimepro_db' in MySQL
- Import the schema from `database/schema.sql`

3. Configure the database connection:
- Open `config/database.php`
- Update the database credentials if needed (default uses XAMPP settings)

4. Set up the web server:
- If using XAMPP, place the project in the `htdocs` directory
- Configure virtual host if needed

5. Start the server and visit the application in your web browser

## Project Structure

```
parttimepro/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
├── auth/
│   ├── login.php
│   ├── jobseeker-register.php
│   └── employer-register.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── employer/
│   └── dashboard.php
├── jobseeker/
│   └── dashboard.php
└── index.php
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for SQL queries
- Input validation and sanitization
- Session management
- CSRF protection

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please email [support@parttimepro.com] 