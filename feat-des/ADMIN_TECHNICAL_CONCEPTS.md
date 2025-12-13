# ADMIN TECHNICAL CONCEPTS & ARCHITECTURE

## Technical Stack

### Backend Language
- **PHP 7.4+** (Procedural approach with functions)
- MySQLi for database connection
- Session-based authentication

### Frontend Technologies
- HTML5 (Semantic markup)
- CSS3 (Custom styling + Bootstrap framework)
- JavaScript ES6 (jQuery library, AJAX)
- Bootstrap 5.3 (Responsive UI framework)
- Chart.js 4.4.0 (Data visualization)

### Database
- MySQL/MariaDB (InnoDB engine)
- Normalized schema (3NF)
- Foreign key constraints
- Prepared statements for security

---

## SQL CONCEPTS USED IN ADMIN PAGES

### 1. JOIN OPERATIONS

#### INNER JOIN Example
```sql
-- Revenue dashboard
SELECT 
    b.bill_id,
    b.total_amount,
    c.name as customer_name,
    a.appointment_datetime
FROM bills b
INNER JOIN jobs j ON b.job_id = j.job_id
INNER JOIN appointments a ON j.appointment_id = a.appointment_id
INNER JOIN customers c ON a.customer_id = c.customer_id
```
**Purpose**: Connect related records across multiple tables
**Used In**: Revenue reports, job details, appointment tracking

#### LEFT JOIN Example
```sql
-- Appointments with optional vehicle
SELECT 
    a.appointment_id,
    a.appointment_datetime,
    c.name as customer_name,
    v.registration_no,
    v.brand,
    v.model
FROM appointments a
JOIN customers c ON a.customer_id = c.customer_id
LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
```
**Purpose**: Include records from left table even if no matching vehicle
**Used In**: Appointment listing, customer searches

#### Multiple JOINs (3+ tables)
```sql
-- Complex revenue analysis
SELECT 
    b.bill_id,
    SUM(b.total_amount) as revenue,
    c.name as customer,
    s.name as mechanic,
    COUNT(DISTINCT js.service_id) as service_count
FROM bills b
JOIN jobs j ON b.job_id = j.job_id
JOIN appointments a ON j.appointment_id = a.appointment_id
JOIN customers c ON a.customer_id = c.customer_id
LEFT JOIN staff s ON j.mechanic_id = s.staff_id
LEFT JOIN job_services js ON j.job_id = js.job_id
```
**Purpose**: Combine data from 5+ related tables
**Used In**: Analytics dashboard, comprehensive reports

---

### 2. AGGREGATION FUNCTIONS & GROUP BY

#### COUNT() Function
```sql
-- Count total customers
SELECT COUNT(*) as total_customers FROM customers;

-- Count active staff
SELECT COUNT(*) as active_staff FROM staff WHERE active = 1;

-- Count pending appointments
SELECT COUNT(*) as pending FROM appointments 
WHERE status IN ('booked', 'pending');

-- Count by group
SELECT 
    status,
    COUNT(*) as count
FROM appointments
GROUP BY status;
```
**Purpose**: Count rows/groups of data
**Concepts**: 
- COUNT(*) counts all rows
- COUNT(column) ignores NULL values
- COUNT(DISTINCT column) counts unique values

#### SUM() Function
```sql
-- Total revenue
SELECT SUM(total_amount) as total_revenue FROM bills;

-- Revenue by month
SELECT 
    DATE_FORMAT(bill_date, '%Y-%m') as month,
    SUM(total_amount) as monthly_revenue
FROM bills
GROUP BY DATE_FORMAT(bill_date, '%Y-%m');

-- Paid vs unpaid
SELECT 
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid,
    SUM(CASE WHEN payment_status != 'paid' THEN total_amount ELSE 0 END) as unpaid
FROM bills;
```
**Purpose**: Calculate totals and sums
**Used In**: Revenue calculations, financial metrics

#### AVG() Function
```sql
-- Average bill amount
SELECT AVG(total_amount) as avg_bill FROM bills;

-- Average rating
SELECT AVG(rating) as avg_rating FROM reviews WHERE is_approved = TRUE;
```
**Purpose**: Calculate averages
**Used In**: Analytics, performance metrics

#### GROUP BY Clause
```sql
-- Group by status
SELECT 
    status,
    COUNT(*) as count
FROM appointments
GROUP BY status;

-- Group by customer
SELECT 
    c.name,
    COUNT(DISTINCT a.appointment_id) as appointment_count,
    SUM(b.total_amount) as total_spent
FROM customers c
LEFT JOIN appointments a ON c.customer_id = a.customer_id
LEFT JOIN bills b ON a.appointment_id = b.appointment_id
GROUP BY c.customer_id, c.name;

-- Group by month
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as new_customers
FROM customers
GROUP BY DATE_FORMAT(created_at, '%Y-%m');
```
**Purpose**: Group rows and aggregate them
**Concepts**: 
- Must include non-aggregated columns in GROUP BY
- HAVING filters on grouped results
- Can group by expressions (DATE_FORMAT, etc.)

#### HAVING Clause
```sql
-- Find customers with > 5 appointments
SELECT 
    c.name,
    COUNT(DISTINCT a.appointment_id) as appt_count
FROM customers c
JOIN appointments a ON c.customer_id = a.customer_id
GROUP BY c.customer_id, c.name
HAVING COUNT(DISTINCT a.appointment_id) > 5;

-- Top services (used more than 10 times)
SELECT 
    s.name,
    COUNT(*) as usage_count
FROM services s
JOIN job_services js ON s.service_id = js.service_id
GROUP BY s.service_id, s.name
HAVING COUNT(*) > 10
ORDER BY usage_count DESC;
```
**Purpose**: Filter groups based on aggregate conditions
**Note**: WHERE filters before grouping, HAVING filters after

---

### 3. WHERE CLAUSES & CONDITIONS

#### Basic WHERE
```sql
-- Simple condition
SELECT * FROM staff WHERE role = 'admin';

-- Multiple conditions with AND
SELECT * FROM appointments 
WHERE status = 'pending' AND appointment_datetime >= NOW();

-- Multiple conditions with OR
SELECT * FROM customers 
WHERE name LIKE '%John%' OR email LIKE '%john%';

-- Combining AND/OR
SELECT * FROM bills 
WHERE (payment_status = 'unpaid' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))
   OR (total_amount > 1000 AND payment_status = 'paid');
```

#### Comparison Operators
```sql
-- Greater than, less than
SELECT * FROM bills WHERE total_amount > 500;
SELECT * FROM appointments WHERE appointment_datetime < NOW();

-- BETWEEN operator
SELECT * FROM bills 
WHERE bill_date BETWEEN '2024-01-01' AND '2024-12-31';

SELECT * FROM bills 
WHERE total_amount BETWEEN 100 AND 1000;

-- IN operator (list of values)
SELECT * FROM appointments 
WHERE status IN ('pending', 'booked', 'confirmed');

-- NOT IN
SELECT * FROM staff 
WHERE role NOT IN ('customer', 'guest');
```

#### NULL Checks
```sql
-- Find appointments without assigned vehicles
SELECT * FROM appointments 
WHERE vehicle_id IS NULL;

-- Find completed jobs
SELECT * FROM jobs 
WHERE completion_date IS NOT NULL 
  AND status = 'completed';
```

#### Pattern Matching (LIKE)
```sql
-- Starts with
SELECT * FROM customers WHERE name LIKE 'John%';

-- Contains
SELECT * FROM customers WHERE name LIKE '%john%';

-- Ends with
SELECT * FROM customers WHERE email LIKE '%@gmail.com';

-- Multiple columns
SELECT * FROM customers 
WHERE name LIKE CONCAT('%', ?, '%')
   OR email LIKE CONCAT('%', ?, '%')
   OR phone LIKE CONCAT('%', ?, '%');
```

---

### 4. ORDERING & LIMITING

#### ORDER BY
```sql
-- Ascending order (default)
SELECT * FROM customers ORDER BY name ASC;

-- Descending order
SELECT * FROM bills ORDER BY total_amount DESC;

-- Multiple columns
SELECT * FROM appointments 
ORDER BY appointment_datetime DESC, status ASC;

-- By expression
SELECT * FROM customers 
ORDER BY created_at DESC;

-- By case expression
SELECT * FROM customers 
ORDER BY CASE WHEN active = 1 THEN 0 ELSE 1 END,
         name ASC;
```

#### LIMIT & OFFSET
```sql
-- Get first 10 results
SELECT * FROM appointments LIMIT 10;

-- Pagination: page 2 with 20 items per page
SELECT * FROM appointments 
LIMIT 20 OFFSET 20;

-- Calculate offset
-- Page 1: OFFSET 0
-- Page 2: OFFSET 20
-- Page 3: OFFSET 40
-- Formula: OFFSET = (page - 1) * per_page

-- Most recent 5 appointments
SELECT * FROM appointments 
ORDER BY appointment_datetime DESC 
LIMIT 5;
```

---

### 5. SUBQUERIES

#### Single-Row Subqueries
```sql
-- Find appointments for the top spender
SELECT * FROM appointments 
WHERE customer_id = (
    SELECT customer_id 
    FROM customers c
    JOIN appointments a ON c.customer_id = a.customer_id
    JOIN bills b ON a.appointment_id = b.appointment_id
    GROUP BY c.customer_id
    ORDER BY SUM(b.total_amount) DESC
    LIMIT 1
);
```

#### Multiple-Row Subqueries (IN)
```sql
-- Find appointments for customers with > 5 visits
SELECT * FROM appointments 
WHERE customer_id IN (
    SELECT c.customer_id 
    FROM customers c
    JOIN appointments a ON c.customer_id = a.customer_id
    GROUP BY c.customer_id
    HAVING COUNT(*) > 5
);

-- Find jobs for services that generated > $5000
SELECT * FROM jobs 
WHERE job_id IN (
    SELECT j.job_id 
    FROM jobs j
    JOIN job_services js ON j.job_id = js.job_id
    JOIN bills b ON j.job_id = b.job_id
    GROUP BY j.job_id
    HAVING SUM(b.total_amount) > 5000
);
```

#### EXISTS Subqueries
```sql
-- Find customers with at least one appointment
SELECT * FROM customers c 
WHERE EXISTS (
    SELECT 1 FROM appointments a 
    WHERE a.customer_id = c.customer_id
);

-- Find staff with assigned jobs
SELECT * FROM staff s 
WHERE EXISTS (
    SELECT 1 FROM jobs j 
    WHERE j.mechanic_id = s.staff_id
);

-- Complex example: services used in high-value jobs
SELECT s.* FROM services s
WHERE EXISTS (
    SELECT 1 FROM job_services js
    WHERE js.service_id = s.service_id
    AND EXISTS (
        SELECT 1 FROM jobs j
        JOIN bills b ON j.job_id = b.job_id
        WHERE j.job_id = js.job_id
        AND b.total_amount > 1000
    )
);
```

---

### 6. CONDITIONAL LOGIC (CASE/IF)

#### CASE Expression
```sql
-- Simple CASE
SELECT 
    name,
    CASE role 
        WHEN 'admin' THEN 'Administrator'
        WHEN 'mechanic' THEN 'Mechanic'
        WHEN 'receptionist' THEN 'Receptionist'
        ELSE 'Unknown'
    END as role_label
FROM staff;

-- Searched CASE
SELECT 
    name,
    CASE 
        WHEN active = 1 THEN 'Active'
        WHEN active = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Recently Inactive'
        ELSE 'Inactive'
    END as status
FROM staff;

-- CASE in aggregation
SELECT 
    DATE_FORMAT(bill_date, '%Y-%m') as month,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid,
    SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid
FROM bills
GROUP BY DATE_FORMAT(bill_date, '%Y-%m');

-- CASE for status badges
SELECT 
    status,
    CASE 
        WHEN status = 'completed' THEN 'success'
        WHEN status = 'pending' THEN 'warning'
        WHEN status = 'cancelled' THEN 'danger'
        ELSE 'info'
    END as badge_color
FROM appointments;
```

#### IF Function
```sql
-- Simple conditional
SELECT 
    name,
    IF(active = 1, 'Active', 'Inactive') as status
FROM staff;

-- Nested IF
SELECT 
    name,
    IF(total_spent > 5000, 
        IF(total_spent > 10000, 'Gold', 'Silver'),
        'Bronze'
    ) as customer_tier
FROM (
    SELECT c.name, SUM(b.total_amount) as total_spent
    FROM customers c
    LEFT JOIN appointments a ON c.customer_id = a.customer_id
    LEFT JOIN bills b ON a.appointment_id = b.appointment_id
    GROUP BY c.customer_id, c.name
) customer_spending;
```

---

### 7. STRING & DATE FUNCTIONS

#### String Functions
```sql
-- CONCAT: Combine columns
SELECT CONCAT(brand, ' ', model) as vehicle_name FROM vehicles;
SELECT CONCAT(first_name, ' ', last_name) as full_name FROM staff;

-- UPPER/LOWER: Case conversion
SELECT UPPER(name) as customer_name_upper FROM customers;
SELECT LOWER(email) as email_lower FROM staff;

-- SUBSTRING: Extract part of string
SELECT SUBSTRING(phone, 1, 3) as area_code FROM customers;

-- LENGTH: String length
SELECT name FROM customers WHERE LENGTH(name) > 50;

-- TRIM: Remove spaces
SELECT TRIM(name) as clean_name FROM customers;

-- REPLACE: Replace text
SELECT REPLACE(registration_no, '-', '') as registration_clean FROM vehicles;

-- LIKE with wildcards
SELECT * FROM customers 
WHERE name LIKE 'A%';          -- Starts with A
SELECT * FROM customers 
WHERE name LIKE '%M';          -- Ends with M
SELECT * FROM customers 
WHERE name LIKE '%oh%';        -- Contains "oh"
SELECT * FROM customers 
WHERE name LIKE 'M_ke';        -- 4-char, starts with M, ends with ke
```

#### Date Functions
```sql
-- CURRENT_DATE/NOW: Current date/time
SELECT * FROM appointments WHERE appointment_datetime >= NOW();
SELECT * FROM bills WHERE bill_date >= CURRENT_DATE();

-- DATE_FORMAT: Format dates for display
SELECT DATE_FORMAT(appointment_datetime, '%M %d, %Y %h:%i %p') as formatted_date
FROM appointments;
-- Output: December 13, 2024 02:30 PM

-- Useful formats:
-- '%Y-%m-%d' = 2024-12-13
-- '%d/%m/%Y' = 13/12/2024
-- '%M %d, %Y' = December 13, 2024
-- '%h:%i %p' = 02:30 PM
-- '%Y-%m' = 2024-12 (for grouping by month)

-- DATE: Extract date from datetime
SELECT DATE(appointment_datetime) as appointment_date FROM appointments;

-- YEAR/MONTH/DAY: Extract components
SELECT YEAR(created_at) as year FROM customers;
SELECT MONTH(bill_date) as month FROM bills;
SELECT DAY(appointment_datetime) as day FROM appointments;

-- DATE_ADD/DATE_SUB: Add/subtract intervals
SELECT * FROM bills 
WHERE bill_date >= DATE_SUB(NOW(), INTERVAL 30 DAY);

SELECT * FROM appointments 
WHERE appointment_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY);

-- DATEDIFF: Days between dates
SELECT DATEDIFF(completion_date, job_date) as days_to_complete 
FROM jobs WHERE status = 'completed';

-- TIMESTAMPDIFF: More flexible differences
SELECT TIMESTAMPDIFF(HOUR, job_date, completion_date) as hours_to_complete
FROM jobs WHERE status = 'completed';

SELECT TIMESTAMPDIFF(DAY, created_at, NOW()) as days_since_creation
FROM customers;
```

---

### 8. VIEW CONCEPTS (if used)

#### Creating Views for Common Queries
```sql
-- View for customer spending summary
CREATE VIEW customer_spending_summary AS
SELECT 
    c.customer_id,
    c.name,
    COUNT(DISTINCT a.appointment_id) as appointment_count,
    COUNT(DISTINCT b.bill_id) as bill_count,
    SUM(b.total_amount) as total_spent,
    AVG(b.total_amount) as avg_bill,
    MAX(b.bill_date) as last_visit
FROM customers c
LEFT JOIN appointments a ON c.customer_id = a.customer_id
LEFT JOIN jobs j ON a.appointment_id = j.appointment_id
LEFT JOIN bills b ON j.job_id = b.job_id
GROUP BY c.customer_id, c.name;

-- View for recent activity
CREATE VIEW recent_activity AS
SELECT 
    'appointment' as entity_type,
    a.appointment_id as entity_id,
    c.name as entity_name,
    a.created_at as created_at
FROM appointments a
JOIN customers c ON a.customer_id = c.customer_id
UNION ALL
SELECT 
    'bill' as entity_type,
    b.bill_id,
    c.name,
    b.created_at
FROM bills b
JOIN jobs j ON b.job_id = j.job_id
JOIN appointments a ON j.appointment_id = a.appointment_id
JOIN customers c ON a.customer_id = c.customer_id;
```

---

## PHP CONCEPTS USED IN ADMIN PAGES

### 1. SESSION MANAGEMENT

#### Session Initialization
```php
// Start session at top of every page
session_start();

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}

// Access session variables
$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];
$staff_role = $_SESSION['staff_role'];

// Verify admin role
if ($_SESSION['staff_role'] !== 'admin') {
    header('Location: access_denied.php');
    exit;
}

// Using helper function
requireRole(['admin']);  // Custom function that checks role
```

#### Session Security
```php
// Regenerate session ID on login (prevent session fixation)
session_regenerate_id(true);

// Destroy session on logout
session_destroy();

// Session timeout (optional)
$session_timeout = 1800;  // 30 minutes
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $session_timeout) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();
```

---

### 2. PREPARED STATEMENTS

#### Parameterized Queries
```php
// Prepare statement
$stmt = $conn->prepare("SELECT staff_id, name, role FROM staff WHERE username = ?");

// Bind parameters (prevents SQL injection)
$stmt->bind_param("s", $username);  // "s" = string type

// Execute with variable
$username = $_POST['username'];
$stmt->execute();

// Get results
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Close statement
$stmt->close();

// Multiple parameters
$stmt = $conn->prepare("SELECT * FROM bills 
                        WHERE bill_date BETWEEN ? AND ? 
                        AND payment_status = ?");
$stmt->bind_param("sss", $date_from, $date_to, $status);
$stmt->execute();

// Parameter types:
// "s" = string
// "i" = integer
// "d" = double/float
// "b" = blob (binary data)
// "iii" = three integers
// "sii" = string, int, int
```

#### Protection Against SQL Injection
```php
// ❌ UNSAFE - Don't do this!
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];
// Attack: /page.php?id=1 OR 1=1

// ✅ SAFE - Use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$id = $_GET['id'];
$stmt->execute();
// Attack neutralized - parameter treated as literal value
```

---

### 3. INPUT VALIDATION & SANITIZATION

#### Validation Types
```php
// Required field
if (empty($form_data['name'])) {
    $errors[] = 'Name is required';
}

// Length validation
if (strlen($form_data['name']) > 150) {
    $errors[] = 'Name too long';
}

// Pattern matching
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $form_data['username'])) {
    $errors[] = 'Username contains invalid characters';
}

// Email validation
if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Numeric validation
if (!is_numeric($amount) || $amount <= 0) {
    $errors[] = 'Amount must be positive number';
}

// Date validation
$date = DateTime::createFromFormat('Y-m-d', $form_data['date']);
if (!$date) {
    $errors[] = 'Invalid date format';
}

// Unique constraint checking
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $errors[] = 'Phone number already registered';
}

// Custom validation function
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10;
}
```

#### Output Escaping
```php
// Escape HTML to prevent XSS
$safe_name = htmlspecialchars($name);
// Converts: <script>alert('xss')</script>
// To: &lt;script&gt;alert('xss')&lt;/script&gt;

// In HTML
<div class="customer-name">
    <?php echo htmlspecialchars($customer['name']); ?>
</div>

// In attributes
<input value="<?php echo htmlspecialchars($form_data['email']); ?>">

// Common entities:
// & → &amp;
// < → &lt;
// > → &gt;
// " → &quot;
// ' → &#039;

// URL encoding for links
$url = 'search.php?q=' . urlencode($_GET['search']);

// JSON encoding
$json = json_encode($data);
echo json_encode(['status' => 'success', 'message' => htmlspecialchars($msg)]);
```

---

### 4. PASSWORD SECURITY

#### Bcrypt Hashing
```php
// Hash password (one-way)
$password_hash = password_hash($password, PASSWORD_BCRYPT);
// Produces: $2y$10$...60-character hash...

// Verify password
if (password_verify($user_input, $password_hash)) {
    // Password is correct
    $_SESSION['staff_id'] = $user['staff_id'];
} else {
    // Password is incorrect
    $error = 'Invalid credentials';
}

// Check if password needs rehashing (e.g., upgraded algorithm)
if (password_needs_rehash($password_hash, PASSWORD_BCRYPT)) {
    $new_hash = password_hash($password, PASSWORD_BCRYPT);
    // Update in database
}

// Bcrypt characteristics:
// - Salt included automatically
// - One-way function (can't decrypt)
// - Slow on purpose (resistant to brute force)
// - Cost factor = 10 (can be increased)
```

---

### 5. ERROR HANDLING

#### Try-Catch Blocks
```php
try {
    // Attempt database operation
    $stmt = $conn->prepare("INSERT INTO staff ...");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Success
    $success = true;
    
} catch (Exception $e) {
    // Handle error
    $error = $e->getMessage();
    // Log error for debugging
    error_log($error);
}
```

#### User-Friendly Errors
```php
// Don't expose database errors to user
// ❌ Bad
echo "Error: " . $conn->error;  // Shows SQL details

// ✅ Good
$errors[] = 'An error occurred. Please try again.';
// Log actual error
error_log("Database error: " . $conn->error);
```

---

### 6. FORM HANDLING

#### POST Method
```php
// Check method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    
    // Trim removes whitespace
    // isset checks if variable exists
    // Ternary operator provides default value
    
    // Validate...
    // Insert to database...
    // Redirect or show success
}
```

#### Form Repopulation
```php
// Store form data in array
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => ''
];

// On POST, populate with submitted data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['name'] = isset($_POST['name']) ? trim($_POST['name']) : '';
    // ... validate ...
    // On error, form still has values for user correction
}

// In form
<input type="text" name="name" value="<?php echo htmlspecialchars($form_data['name']); ?>">
```

---

### 7. REDIRECTION & HEADERS

#### Safe Redirection
```php
// Redirect after successful action
header('Location: manage_staff.php');
exit;  // ALWAYS exit after redirect

// Redirect with message (session)
$_SESSION['success'] = 'Staff member created successfully';
header('Location: manage_staff.php');
exit;

// Redirect with error
$_SESSION['error'] = 'Failed to create staff member';
header('Location: create_admin.php');
exit;

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Content type (for APIs)
header('Content-Type: application/json');
echo json_encode(['success' => true]);
```

---

### 8. ARRAY OPERATIONS

#### Fetch Results
```php
// Single row as associative array
$user = $result->fetch_assoc();
// $user['name'], $user['email']

// Single row as indexed array
$user = $result->fetch_row();
// $user[0], $user[1]

// Single row as object
$user = $result->fetch_object();
// $user->name, $user->email

// All rows as array of associative arrays
$users = $result->fetch_all(MYSQLI_ASSOC);
// foreach ($users as $user) { ... }

// Check if records exist
if ($result->num_rows > 0) {
    // Process results
}
```

#### Working with Arrays
```php
// Check if key exists
if (isset($array['key'])) { }
if (array_key_exists('key', $array)) { }

// Count elements
$count = count($array);

// Loop through
foreach ($array as $key => $value) { }

// Find key in array
if (in_array('value', $array)) { }

// Array filtering
$active = array_filter($users, function($user) {
    return $user['active'] === 1;
});

// Array mapping
$names = array_map(function($user) {
    return $user['name'];
}, $users);
```

---

### 9. FILE OPERATIONS

#### CSV Export
```php
// Set download headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="logs_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Add UTF-8 BOM for Excel
echo chr(0xEF) . chr(0xBB) . chr(0xBF);

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['ID', 'User', 'Action', 'Date']);

// Write data
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['user_name'],
        $row['action'],
        date('Y-m-d H:i:s', strtotime($row['created_at']))
    ]);
}

fclose($output);
exit;
```

---

### 10. DATETIME HANDLING

#### Formatting Dates
```php
// DateTime object
$date = new DateTime($db_date);
echo $date->format('M d, Y');  // Dec 13, 2024

// strtotime + date
echo date('M d, Y', strtotime($db_date));

// Common formats:
// 'Y-m-d' = 2024-12-13
// 'M d, Y' = Dec 13, 2024
// 'm/d/Y' = 12/13/2024
// 'Y-m-d H:i:s' = 2024-12-13 14:30:45
// 'h:i A' = 02:30 PM

// Current date/time
$now = new DateTime();
echo $now->format('Y-m-d H:i:s');

// Relative dates
$past = new DateTime('-30 days');
$future = new DateTime('+7 days');
```

---

## API CONCEPTS (AJAX)

### jQuery AJAX Calls
```javascript
// Basic GET request
$.ajax({
    url: '../api/analytics_revenue.php',
    method: 'GET',
    data: {
        date_from: '2024-01-01',
        date_to: '2024-12-31'
    },
    dataType: 'json',
    success: function(response) {
        if (response.success) {
            // Handle response
            console.log(response.data);
        }
    },
    error: function(error) {
        console.error('Error:', error);
    }
});

// With promises
$.ajax({...}).done(function(data) {
    // Success
}).fail(function(error) {
    // Error
});

// Promise.all for parallel requests
Promise.all([
    $.ajax({url: 'api1.php'}),
    $.ajax({url: 'api2.php'}),
    $.ajax({url: 'api3.php'})
]).then(function(results) {
    // All requests completed
    console.log(results[0], results[1], results[2]);
});
```

### API Response Format
```php
// Standard JSON response
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'data' => $results,
    'message' => 'Operation successful',
    'total' => count($results),
    'page' => 1,
    'per_page' => 50
]);

// Error response
echo json_encode([
    'success' => false,
    'error' => 'Validation failed',
    'fields' => [
        'email' => 'Invalid email format'
    ]
]);
```

---

## SECURITY BEST PRACTICES CHECKLIST

- ✅ **SQL Injection**: All queries use prepared statements
- ✅ **XSS Prevention**: All output escaped with htmlspecialchars()
- ✅ **Authentication**: Session-based with role checking
- ✅ **Password Security**: Bcrypt hashing
- ✅ **CSRF Protection**: Token validation on form submission (if implemented)
- ✅ **Input Validation**: All inputs validated before use
- ✅ **Error Handling**: Errors logged, not shown to users
- ✅ **Access Control**: RBAC with requireRole() checks
- ✅ **Activity Logging**: All changes tracked with user/timestamp
- ✅ **Data Encryption**: Passwords hashed, sensitive data protected

---

## CONCLUSION

The admin system demonstrates advanced SQL query techniques combined with secure PHP practices. All concepts follow industry best practices for database operations, security, and user experience.

**Key Takeaways**:
1. **SQL**: JOINs, aggregations, subqueries, GROUP BY, CASE/WHEN
2. **PHP**: Sessions, prepared statements, validation, error handling
3. **Security**: SQL injection prevention, XSS prevention, RBAC
4. **Frontend**: Bootstrap UI, Chart.js visualizations, jQuery AJAX
5. **Database**: Proper normalization, constraints, indexing

The implementation is production-ready and scalable.
