# Weightlifting Assistant

An authenticated PHP chat interface tailored for strength-training guidance. Logged-in users can spin up new conversations, send messages that are forwarded to OpenAI, and browse their full history. The UI is optimized for a desktop chat workflow with a responsive layout and an inline composer that streams messages without page reloads.

## Directory layout

```
PHP_Chatbot/
├─ public/
│  ├─ index.html              # Landing page with call-to-action buttons
│  ├─ auth/                   # Login and registration forms
│  ├─ chat/                   # Chat views, sender endpoint, history
│  └─ assets/                 # Compiled CSS bundle + shared styles
├─ src/
│  ├─ auth.php                # Registration + session guard helpers
│  ├─ conversations.php       # Conversation CRUD helpers
│  ├─ messages.php            # Message persistence helpers
│  ├─ openai.php              # Lightweight OpenAI API client
│  ├─ env.php                 # Minimal .env loader
│  └─ db.php                  # PDO connection wrapper
└─ .env                       # Environment variables (not committed)
```

## Prerequisites

- PHP 8.1+ with PDO MySQL and cURL enabled
- MySQL 8 (database name defaults to `chatbot` in `src/db.php`)
- Composer is not required; everything is vanilla PHP
- An OpenAI API key stored in `.env`

## Environment variables

Create a `.env` file in the project root:

```
OPENAI_API_KEY=sk-your-key-here
```

The `env` helper loads this file once per request and exposes `env::get('OPENAI_API_KEY')`.

## Database schema

The helper classes auto-create tables if they are missing:

```sql
CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  role ENUM('system','user','assistant') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Add a simple `users` table with `id`, `email`, and `password_hash` columns; the login and registration forms rely on those fields.

## Running locally

1. Start Apache/MySQL via XAMPP (or any PHP-compatible stack).
2. Import the schema above and update `src/db.php` if you use custom credentials.
3. Copy `.env.example` to `.env` and insert your OpenAI key.
4. Visit `public/auth/register.php` to create an account.
5. Start a new chat via `public/index.html` → “Start New Chat”.

## Development notes

- All chat endpoints require authentication; unauthenticated users are redirected to the login page.
- Messages are submitted via `fetch` (`public/js/main.js`) so the chat window never reloads.
- Error messages bubble up to the UI and are returned as JSON when the request is made with `Accept: application/json`.

Feel free to adapt the styling or copy the OpenAI client into another project—everything is plain PHP with no framework dependencies.
