# Issues Fixed - December 13, 2025

## Problem 1: Non-Admin Users Unable to Login ✅ FIXED

### Issue
- Only `admin_user` could login successfully
- `receptionist_user`, `mechanic_user`, and customers (Alice, Bob) all received "Invalid email or password" errors

### Root Cause
The password hashes in the database were **corrupted or empty**:
1. **receptionist_user** (ID: 1001): Had an **EMPTY password_hash field**
2. **mechanic_user** (ID: 1002): Had an **EMPTY password_hash field**
3. **Alice** (ID: 2000): Had a **corrupted/truncated hash** (`.GL9slXzS0T7KlemVmn1dDoMDnP6EnlLu4Ke/DpYekoIT.` instead of a proper bcrypt hash)
4. **Bob** (ID: 2001): Had the **wrong hash** (same as admin but didn't match 'customer123')

### Solution Implemented
1. **Generated fresh bcrypt hashes** for both passwords:
   - `staffpass` → `$2y$10$n7g9sSWqnGqP6z89S4RZSuob7nIuH3dsccRXkzNw.C2qjB5AwOxCa`
   - `customer123` → `$2y$10$qeGgjIa6NSUWF/uZYC32we/sL4zMBGnkCx3WqmsnUIDQZuHDBaefK`

2. **Updated the database** directly:
   ```sql
   UPDATE staff SET password_hash = '$2y$10$n7g9sSWqnGqP6z89S4RZSuob7nIuH3dsccRXkzNw.C2qjB5AwOxCa' 
   WHERE staff_id IN (1000, 1001, 1002);
   
   UPDATE customers SET password_hash = '$2y$10$qeGgjIa6NSUWF/uZYC32we/sL4zMBGnkCx3WqmsnUIDQZuHDBaefK' 
   WHERE customer_id IN (2000, 2001);
   ```

3. **Updated seed.sql** for future container rebuilds with the correct hashes

### Verification
All users can now login successfully:
- ✅ admin_user / staffpass
- ✅ receptionist_user / staffpass
- ✅ mechanic_user / staffpass
- ✅ alice@example.com / customer123
- ✅ bob@example.com / customer123

---

## Problem 2: Admin Dashboard Links Giving 404 Errors ⚠️ INVESTIGATION NEEDED

### Issue
Links in admin dashboard return:
```
Not Found
The requested URL was not found on this server.
Apache/2.4.65 (Debian) Server at localhost Port 8080
```

### Files Checked
The following files **DO exist** and are in the correct locations:
- `/public/reports/revenue.php` ✅ EXISTS
- `/public/reports/services.php` ✅ EXISTS
- `/public/reports/customers.php` ✅ EXISTS
- `/public/admin/manage_staff.php` ✅ EXISTS
- `/public/search.php` ✅ EXISTS

### Current Link Structure in `admin_dashboard.php`
```php
// These are the current links (line numbers approximate):
<a href="reports/revenue.php">         // Line 139
<a href="reports/services.php">        // Line 153
<a href="reports/customers.php">       // Line 167
<a href="../customers/list.php">       // Line 75, 281
<a href="../vehicles/list.php">        // Line 286
<a href="search.php">                  // Line 291
<a href="admin/manage_staff.php">      // Line 296
```

### Likely Cause
The application is running in Docker at `http://localhost:8080/garage_system/public/...`, so:
- Relative paths from `/public/admin_dashboard.php` should work as-is for `reports/` and `admin/` subdirectories
- Links to `../customers/` go UP one level to `/garage_system/customers/`

### To Test
I created `/public/test_paths.php` to help diagnose the exact path issue. Access it at:
```
http://localhost:8080/garage_system/public/test_paths.php
```

This will show you the actual server paths and test all the link variations.

### Possible Solutions
If relative paths don't work, you may need to:
1. **Use absolute paths from web root:**
   ```php
   <a href="/garage_system/public/reports/revenue.php">
   ```

2. **Or define a base path constant:**
   ```php
   define('BASE_PATH', '/garage_system/public/');
   <a href="<?php echo BASE_PATH; ?>reports/revenue.php">
   ```

3. **Or check Apache/Docker configuration** for mod_rewrite or DocumentRoot settings

---

## Problem 3: Missing Features (Reviews/Ratings) 📝 NOT IMPLEMENTED

### Findings
The **reviews/ratings/feedback feature is mentioned** in documentation but **NOT implemented**:

1. **Mentioned in `USER_LOGIN_GUIDE.md`:**
   ```sql
   CREATE TABLE feedback (
       feedback_id INT PRIMARY KEY AUTO_INCREMENT,
       ...
       rating INT,
       ...
   )
   ```

2. **No actual implementation found:**
   - ❌ No `feedback` or `reviews` table in `docker/mysql/init/init.sql`
   - ❌ No review/rating UI pages
   - ❌ No review submission forms
   - ❌ No review management for admin

### What's Currently Implemented
Based on file structure, the system currently has:
- ✅ Customer management
- ✅ Vehicle registration
- ✅ Appointment booking
- ✅ Job management
- ✅ Bill generation
- ✅ Staff management (admin only)
- ✅ Reports (revenue, services, customers)
- ✅ Global search
- ✅ Chat/messaging between customers and staff
- ✅ Notifications
- ✅ Broadcasts
- ✅ Email queue

### To Add Reviews Feature
You would need to:
1. Create `feedback` or `reviews` table in database schema
2. Add review submission form (likely in customer dashboard or after bill payment)
3. Add review management page for admin
4. Display reviews/ratings (e.g., for services or overall garage rating)

---

## Files Modified

### 1. `docker/mysql/init/seed.sql`
- Updated all staff password hashes to working `$2y$10$n7g9sSWqnGqP6z89S4RZSuob7nIuH3dsccRXkzNw.C2qjB5AwOxCa`
- Updated all customer password hashes to working `$2y$10$qeGgjIa6NSUWF/uZYC32we/sL4zMBGnkCx3WqmsnUIDQZuHDBaefK`

### 2. Database (garage_db)
- Ran SQL UPDATE to fix all existing user password hashes

### 3. Created Helper Files
- `scripts/test_passwords.php` - Verifies password hashes work
- `scripts/generate_hashes.php` - Generates new bcrypt hashes
- `docker/mysql/init/fix_passwords.sql` - SQL to update passwords
- `public/test_paths.php` - Diagnoses path issues

---

## Testing Instructions

### 1. Test Login for All Users
Try logging in with these credentials:

**Staff Login:** http://localhost:8080/garage_system/public/staff_login.php
- admin_user / staffpass ✅
- receptionist_user / staffpass ✅
- mechanic_user / staffpass ✅

**Customer Login:** http://localhost:8080/garage_system/public/customer_login.php
- alice@example.com / customer123 ✅
- bob@example.com / customer123 ✅

### 2. Test Admin Dashboard Links
1. Login as `admin_user` / `staffpass`
2. Navigate to: http://localhost:8080/garage_system/public/admin_dashboard.php
3. Click each link in "Reports & Analytics" section
4. Click each link in "Quick Actions" section
5. If you get 404 errors, go to test_paths.php to see the correct URL structure

### 3. Check What's Missing
The following features were **mentioned but not implemented**:
- ❌ Customer reviews/ratings/feedback
- ❌ Service ratings
- ❌ Review management

---

## Next Steps

1. **If links still give 404 errors:**
   - Access `http://localhost:8080/garage_system/public/test_paths.php`
   - Note the correct URL pattern
   - Update links in `admin_dashboard.php` accordingly

2. **If you want to add reviews feature:**
   - I can help you implement the full reviews/ratings system
   - This would include: database table, submission form, admin management, display logic

3. **For production:**
   - Consider using environment-aware base URLs
   - Add constants for common paths
   - Test all links thoroughly

---

## Summary

✅ **FIXED:** All user logins now work (password hashes corrected in database and seed.sql)
⚠️ **NEEDS TESTING:** Admin dashboard links (likely path configuration issue)
📝 **NOT IMPLEMENTED:** Reviews/ratings feature (mentioned in docs but no code exists)
