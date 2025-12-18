# Profile Feature Setup Guide

## Overview
The profile feature allows all users (admin, faculty, and employees) to:
- View and update their profile information
- Upload and change profile photos
- Change their password securely

## Files Created

### 1. API Endpoint
- **File**: `api/profile.php`
- **Actions**:
  - `get`: Fetch user profile data
  - `update`: Update profile information
  - `change_password`: Change user password
  - `upload_photo`: Upload profile photo

### 2. View Page
- **File**: `app/views/profile.php`
- **Features**:
  - Profile photo upload with preview
  - Personal information form (name, email, mobile, gender, DOB, address)
  - Password change form with visibility toggle
  - Responsive design

### 3. Database Migration
- **File**: `sql/migrations/20251218_add_profile_columns_to_users.sql`
- **Columns Added**:
  - `photo` VARCHAR(255) - Profile photo URL
  - `address` TEXT - User address
  - `date_of_birth` DATE - Date of birth
  - `gender` ENUM - Gender (male/female/other)

### 4. Supporting Files
- `scripts/add_profile_columns.php` - Migration runner script
- `public/assets/images/default-avatar.svg` - Default avatar placeholder
- `public/uploads/profiles/` - Directory for uploaded photos

## Installation Steps

### Step 1: Run the Migration
You have two options:

**Option A - Using PHP Script (Recommended):**
```bash
php scripts/add_profile_columns.php
```

**Option B - Using MySQL Command:**
```bash
mysql -u root campuslite_erp < sql/migrations/20251218_add_profile_columns_to_users.sql
```

### Step 2: Verify Directory Permissions
Ensure the uploads directory is writable:
```bash
chmod 755 public/uploads/profiles
```

### Step 3: Access the Profile Page
1. Log in to the application
2. Click on your name in the top-right corner
3. Select "My Profile" from the dropdown

## Features

### Profile Information
- **Full Name**: Required field
- **Email**: Required field, must be unique
- **Mobile Number**: Optional
- **Gender**: Optional (Male/Female/Other)
- **Date of Birth**: Optional
- **Address**: Optional text area
- **Role**: Display only (cannot be changed)

### Profile Photo
- **Supported Formats**: JPG, PNG, GIF
- **Max File Size**: 5MB
- **Upload Process**: 
  1. Click "Change Photo" button
  2. Select image file
  3. Photo uploads automatically
  4. Old photo is deleted from server
- **Default Avatar**: SVG placeholder if no photo uploaded

### Password Change
- **Requirements**:
  - Current password must be correct
  - New password must be at least 6 characters
  - Confirmation must match new password
- **Security**: Passwords are hashed using bcrypt
- **Validation**: Real-time password visibility toggle

## Security Features

1. **Authentication Check**: All API calls verify user is logged in
2. **User Isolation**: Users can only view/edit their own profile
3. **Password Verification**: Current password required before change
4. **File Upload Validation**:
   - File type checking
   - File size limits
   - Unique filename generation
5. **Email Uniqueness**: Prevents duplicate emails
6. **SQL Injection Protection**: Prepared statements used throughout

## Navigation Integration

Profile link is available in the user dropdown menu:
- Location: Top-right corner → User menu
- Label: "My Profile"
- Icon: User circle icon
- Accessible to: All roles (admin, faculty, employee)

## API Response Format

### Success Response
```json
{
  "status": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "status": false,
  "message": "Error description"
}
```

## Troubleshooting

### "Unauthorized" Error
- Ensure user is logged in
- Check session is valid
- Verify `$_SESSION['user']['id']` exists

### Photo Upload Fails
- Check directory permissions (755)
- Verify upload directory exists
- Check file size and type
- Ensure PHP upload limits are sufficient

### Password Change Fails
- Verify current password is correct
- Ensure new password meets requirements
- Check password confirmation matches

### Migration Errors
- Ensure MySQL server is running
- Verify database connection credentials
- Check if columns already exist (safe to re-run)

## File Upload Limits

To increase PHP upload limits, edit `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

## Database Schema

```sql
users table:
- id (INT, PK, AUTO_INCREMENT)
- branch_id (INT, NULL)
- role (ENUM)
- name (VARCHAR(150))
- email (VARCHAR(150), UNIQUE)
- password (VARCHAR(255))
- mobile (VARCHAR(20))
- photo (VARCHAR(255))         -- NEW
- address (TEXT)                -- NEW
- date_of_birth (DATE)          -- NEW
- gender (ENUM)                 -- NEW
- is_part_time (TINYINT)
- status (TINYINT)
- created_at (DATETIME)
```

## Future Enhancements

Potential improvements:
1. Image cropping/resizing on upload
2. Password strength meter
3. Two-factor authentication
4. Email verification for changes
5. Activity log for profile changes
6. Social media links
7. Profile completion percentage
8. Avatar background color customization
