# BlueHaven Website

A modern website for BlueHaven with authentication using Supabase.

## Setup Instructions

### 1. Supabase Setup

1. Create a Supabase account at [supabase.com](https://supabase.com) if you don't have one already
2. Create a new project
3. Once your project is created, go to the SQL Editor
4. Copy the contents of `supabase_setup.sql` and run it in the SQL Editor
5. Go to Project Settings > API to get your Supabase URL and anon key

### 2. Configure the Website

1. Open `auth/config.php`
2. Replace `YOUR_SUPABASE_URL` with your Supabase URL (e.g., `https://abcdefghijklm.supabase.co`)
3. Replace `YOUR_SUPABASE_ANON_KEY` with your Supabase anon key

### 3. Run the Website

1. Make sure you have PHP installed on your server
2. For local development, you can use PHP's built-in server:
   ```
   php -S localhost:8000
   ```
3. Visit `http://localhost:8000` in your browser

## Features

- Modern, responsive design
- User authentication (signup, login, logout)
- Integration with Supabase for backend functionality
- Product showcase

## File Structure

- `index.php` - Main landing page
- `login.php` - Login page
- `signup.php` - Signup page
- `auth/` - Authentication handlers
  - `config.php` - Supabase configuration and helper functions
  - `login_handler.php` - Processes login form submissions
  - `signup_handler.php` - Processes signup form submissions
  - `logout_handler.php` - Handles user logout
- `supabase_setup.sql` - SQL script for setting up Supabase tables and functions

## Supabase Database Structure

### Tables

1. **profiles** - Extends the auth.users table with additional user information
   - id (UUID, Primary Key)
   - first_name (TEXT)
   - last_name (TEXT)
   - full_name (TEXT)
   - avatar_url (TEXT)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)

2. **products** - Stores product information
   - id (TEXT, Primary Key)
   - name (TEXT)
   - description (TEXT)
   - price (DECIMAL)
   - image_url (TEXT)
   - category (TEXT)
   - is_featured (BOOLEAN)
   - is_active (BOOLEAN)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)

3. **purchases** - Tracks user purchases
   - id (UUID, Primary Key)
   - user_id (UUID, Foreign Key to auth.users)
   - product_id (TEXT)
   - product_name (TEXT)
   - amount (DECIMAL)
   - currency (TEXT)
   - status (TEXT)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)

### Functions

- `handle_new_user()` - Automatically creates a profile when a new user signs up
- `handle_user_update()` - Updates the profile when user metadata changes
- `has_purchased(product_id)` - Checks if a user has purchased a specific product

## Next Steps

1. Create a dashboard page for authenticated users
2. Implement a product details page
3. Add a checkout system for purchasing products
4. Create an admin panel for managing products and users
