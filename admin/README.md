# BlueHaven Admin Panel

This directory contains the admin panel for the BlueHaven website. The admin panel allows administrators to manage users, products, and purchases.

## Features

- **User Management**: View, edit, and manage user accounts
- **Product Management**: Add, edit, and delete products
- **Purchase Tracking**: View and manage purchase records
- **Role-Based Access Control**: Different access levels for different user roles

## User Roles

The system supports three user roles:

1. **User**: Regular users with no admin access
2. **Admin**: Can access the admin panel and manage most aspects of the site
3. **Superadmin**: Has full access to all features, including the ability to create other admins

## Setting Up Admin Access

To set up the first admin user:

1. Create a regular user account through the signup process
2. Connect to your Supabase database
3. Run the following SQL command to promote the user to superadmin:

```sql
UPDATE public.profiles 
SET role = 'superadmin' 
WHERE id = 'your-user-id';
```

Replace `your-user-id` with the UUID of the user you want to make a superadmin.

## File Structure

- `index.php`: Main admin dashboard
- `edit_user.php`: Form to edit user details
- `set_admin.php`: Script to change user roles
- `add_product.php`: Form to add new products (to be implemented)
- `edit_product.php`: Form to edit product details (to be implemented)
- `view_purchase.php`: Page to view purchase details (to be implemented)

## Security

The admin panel is protected by role-based access control. Only users with the 'admin' or 'superadmin' role can access the admin panel. This is enforced at the beginning of each admin page:

```php
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../index.php?error=You do not have permission to access this page');
    exit;
}
```

## Role Hierarchy

- **Superadmin**: Can create/remove other admins and superadmins
- **Admin**: Can manage users and content, but cannot create superadmins
- **User**: No admin access

## Future Enhancements

- Complete product management functionality
- Enhanced purchase tracking and reporting
- User activity logs
- Admin action audit trail
