# Weightlifting Assistant

A web-based chat application for strength training guidance, built with PHP and MySQL. Users can create accounts, manage multiple conversations, and receive personalized weightlifting advice through an AI-powered assistant.

## Features

- User authentication (registration and login)
- Multi-conversation support
- Real-time message sending via AJAX
- Conversation history tracking
- Role-based access control (user/admin)
- User preference management
- Admin dashboard for user management
- Login attempt tracking and account lockout protection

## Technology Stack

- PHP 8.1+
- MySQL 8
- Vanilla JavaScript
- OpenAI API integration
- PDO for database access

## Project Structure

```
PHP_Chatbot/
├─ public/
│  ├─ index.html           # Landing page
│  ├─ auth/                # Login, registration, and logout
│  ├─ chat/                # Chat interface and message handling
│  ├─ user/                # User profile management
│  ├─ admin/               # Admin dashboard
│  └─ assets/              # CSS and JavaScript files
└─ src/
   ├─ auth.php             # Authentication and authorization
   ├─ conversations.php    # Conversation management
   ├─ messages.php         # Message persistence
   ├─ openai.php           # OpenAI API client
   ├─ env.php              # Environment configuration loader
   ├─ db.php               # Database connection
   └─ LoginAttemptTracker.php  # Security for login attempts
```

## Requirements

- PHP 8.1 or higher
- MySQL 8.0 or higher
- PHP extensions: PDO, pdo_mysql, curl
- Web server (Apache recommended)
- OpenAI API account

## Installation

1. Clone or download this repository to your web server directory

2. Configure your database connection in `src/db.php`

3. Create a `.env` file in the project root with your OpenAI API key:
   ```
   OPENAI_API_KEY=your_key_here
   ```

4. Import the database schema (create tables for users, conversations, messages, preferences, and login_attempts)

5. Configure your web server to serve from the `public/` directory

6. Access the application through your browser and create an account

## Usage

After installation, navigate to the application in your browser. You can:

- Register a new account at `/public/auth/register.php`
- Log in at `/public/auth/login.php`
- Start new conversations from the main interface
- View and manage your conversation history
- Update your profile and preferences
- Access admin features (if you have admin role)

## Security Features

- Password hashing with bcrypt
- Prepared statements for SQL queries
- XSS protection with output escaping
- Login attempt tracking and temporary account lockout
- Session-based authentication
- Role-based access control

## Notes

- All chat interactions require authentication
- Messages are sent asynchronously without page reloads
- The application uses vanilla PHP with no framework dependencies
- Error responses are returned as JSON for AJAX requests
