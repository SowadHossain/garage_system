# 🚀 Screw Dheela Management System - Local Setup Guide

## 📋 Prerequisites

Before you begin, ensure you have the following installed on your machine:

- **Docker Desktop** (version 20.10 or higher)
  - Download: https://www.docker.com/products/docker-desktop
  - Make sure Docker Desktop is running before proceeding
- **Git** (optional, for cloning the repository)
  - Download: https://git-scm.com/downloads

---

## 🛠️ Quick Start (5 Minutes Setup)

### Step 1: Navigate to Project Directory

Open PowerShell or Command Prompt and navigate to the project folder:

```powershell
cd C:\xampp\htdocs\garage_system
```

### Step 2: Stop Any Running Containers (if restarting)

```powershell
docker compose down -v
```

> **Note:** The `-v` flag removes volumes, giving you a fresh database. Omit it if you want to keep existing data.

### Step 3: Build and Start All Services

```powershell
docker compose up -d --build
```

This command will:
- Build the PHP Apache container
- Start MySQL 8.0 database
- Start phpMyAdmin interface
- Create the network and volumes

**Expected output:**
```
[+] Building ...
[+] Running 5/5
 ✔ Network garage_system_garage_net  Created
 ✔ Volume "garage_system_db_data"    Created
 ✔ Container garage_db               Started
 ✔ Container garage_app              Started
 ✔ Container garage_phpmyadmin       Started
```

### Step 4: Wait for Services to Initialize (15-20 seconds)

Wait for MySQL to fully start. You can check the status with:

```powershell
docker compose ps
```

All containers should show "Up" status.

### Step 5: Create Database

```powershell
docker compose exec db mysql -u root -proot_password_change_me -e "CREATE DATABASE IF NOT EXISTS garage_db;"
```

### Step 6: Load Database Schema and Seed Data

```powershell
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SOURCE /docker-entrypoint-initdb.d/seed.sql;"
```

This loads:
- All database tables (staff, customers, vehicles, appointments, jobs, bills, conversations, messages)
- Sample data with test accounts

---

## 🌐 Access the Application

Once all containers are running, you can access:

### 🏠 Main Application
**URL:** http://localhost:8080/garage_system/public/welcome.php

This is the landing page where you can choose:
- **Staff Portal** (Blue button)
- **Customer Portal** (Green button)

### 👨‍💼 Staff Portal Login
**URL:** http://localhost:8080/garage_system/public/staff_login.php

**Test Credentials:**
- **Username:** `admin_user`
- **Password:** `staffpass`

**Features:**
- Dashboard with statistics
- Customer management
- Appointment scheduling
- Job creation
- Bill generation
- Customer messaging

### 👤 Customer Portal Login
**URL:** http://localhost:8080/garage_system/public/customer_login.php

**Test Credentials (Option 1):**
- **Email:** `alice@example.com`
- **Password:** `staffpass`

**Test Credentials (Option 2):**
- **Email:** `bob@example.com`
- **Password:** `staffpass`

**Features:**
- Personal dashboard
- Vehicle management
- Appointment booking
- Bill viewing
- Staff messaging

### 🗄️ phpMyAdmin (Database Management)
**URL:** http://localhost:8081

**Credentials:**
- **Server:** `db`
- **Username:** `root`
- **Password:** `root_password_change_me`

---

## 📊 Service Ports

| Service | Internal Port | External Port | URL |
|---------|--------------|---------------|-----|
| Apache/PHP | 80 | 8080 | http://localhost:8080 |
| MySQL | 3306 | 3306 | localhost:3306 |
| phpMyAdmin | 80 | 8081 | http://localhost:8081 |

---

## 🔧 Common Commands

### View Running Containers
```powershell
docker compose ps
```

### View Container Logs
```powershell
# All services
docker compose logs

# Specific service
docker compose logs app
docker compose logs db
docker compose logs phpmyadmin

# Follow logs (live updates)
docker compose logs -f app
```

### Stop All Containers
```powershell
docker compose down
```

### Stop and Remove Volumes (Fresh Start)
```powershell
docker compose down -v
```

### Restart Services
```powershell
docker compose restart
```

### Restart Specific Service
```powershell
docker compose restart app
docker compose restart db
```

### Execute MySQL Commands
```powershell
docker compose exec db mysql -u root -proot_password_change_me garage_db
```

### Access Container Shell
```powershell
# PHP/Apache container
docker compose exec app bash

# MySQL container
docker compose exec db bash
```

---

## 🗃️ Database Information

### Connection Details
- **Host:** `localhost` (from your machine) or `db` (from containers)
- **Port:** `3306`
- **Database:** `garage_db`
- **Username:** `root`
- **Password:** `root_password_change_me`

### Pre-loaded Test Data

**Staff Accounts:**
- Username: `admin_user` | Password: `staffpass` | Role: admin

**Customer Accounts:**
- Email: `alice@example.com` | Password: `staffpass`
- Email: `bob@example.com` | Password: `staffpass`

**Sample Data Includes:**
- Vehicles (2 vehicles for testing)
- Appointments (various statuses)
- Jobs (service records)
- Bills (with itemized charges)

---

## 🎯 Testing the System

### 1. Test Customer Registration
1. Go to: http://localhost:8080/garage_system/public/customer_login.php
2. Click "Create New Account"
3. Fill in the registration form
4. You'll be auto-logged in after registration

### 2. Test Vehicle Management
1. Login as customer
2. Navigate to "My Vehicles"
3. Add a new vehicle with registration number, brand, model, etc.
4. Edit or delete vehicles

### 3. Test Appointment Booking
1. Login as customer
2. Click "Book Appointment"
3. Select vehicle, date, time, and problem description
4. View appointments in dashboard

### 4. Test Chat System
1. Login as customer
2. Navigate to Messages
3. Start a new conversation
4. Login as staff in another browser/incognito window
5. Reply to the customer message

### 5. Test Bill Viewing
1. Login as customer
2. Navigate to Bills & Invoices
3. View bill details
4. Test print functionality

---

## 🐛 Troubleshooting

### Issue: Containers Won't Start

**Solution:**
```powershell
# Check Docker Desktop is running
# Restart Docker Desktop
# Clean up and rebuild
docker compose down -v
docker compose up -d --build
```

### Issue: Database Connection Errors

**Solution:**
```powershell
# Wait longer for MySQL to initialize (30 seconds)
Start-Sleep -Seconds 30

# Verify database exists
docker compose exec db mysql -u root -proot_password_change_me -e "SHOW DATABASES;"

# Recreate database
docker compose exec db mysql -u root -proot_password_change_me -e "DROP DATABASE IF EXISTS garage_db; CREATE DATABASE garage_db;"
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SOURCE /docker-entrypoint-initdb.d/seed.sql;"
```

### Issue: Port Already in Use

**Error:** "Bind for 0.0.0.0:8080 failed: port is already allocated"

**Solution:**
```powershell
# Option 1: Stop the process using that port
# For Windows, find and kill process on port 8080:
netstat -ano | findstr :8080
taskkill /PID <PID_NUMBER> /F

# Option 2: Change ports in docker-compose.yml
# Edit the ports section to use different ports
```

### Issue: Permission Denied Errors

**Solution:**
```powershell
# Rebuild with proper permissions
docker compose down
docker compose up -d --build
```

### Issue: CSS/JS Not Loading

**Solution:**
1. Clear browser cache (Ctrl + Shift + Delete)
2. Hard refresh (Ctrl + F5)
3. Check browser console for errors (F12)

### Issue: Login Not Working

**Solution:**
```powershell
# Verify seed data loaded correctly
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SELECT username, active FROM staff;"
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SELECT email FROM customers;"

# Reload seed data
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SOURCE /docker-entrypoint-initdb.d/seed.sql;"
```

---

## 🔄 Development Workflow

### Making Code Changes

The project uses volume mounting, so changes to PHP files are immediately reflected:

1. Edit any PHP file in your code editor
2. Refresh browser (F5)
3. Changes are live!

**No need to rebuild containers for PHP changes.**

### Database Schema Changes

If you modify the database schema:

```powershell
# Option 1: Run SQL directly
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "ALTER TABLE ..."

# Option 2: Update seed.sql and reload
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SOURCE /docker-entrypoint-initdb.d/seed.sql;"
```

---

## 📁 Project Structure

```
garage_system/
├── docker-compose.yml          # Docker services configuration
├── Dockerfile                  # PHP Apache container setup
├── public/                     # Entry points (login, dashboards)
│   ├── welcome.php
│   ├── staff_login.php
│   ├── customer_login.php
│   ├── customer_register.php
│   ├── customer_dashboard.php
│   └── staff_dashboard.php
├── vehicles/                   # Vehicle management
├── appointments/               # Appointment booking
├── bills/                      # Billing and invoices
├── chat/                       # Messaging system
├── config/                     # Database configuration
│   └── db.php
├── includes/                   # Shared components
│   ├── auth_check.php
│   ├── header.php
│   └── footer.php
└── docker/
    └── mysql/
        └── init/
            └── seed.sql        # Database schema and test data
```

---

## 🎨 Technology Stack

- **Backend:** PHP 8.1
- **Database:** MySQL 8.0
- **Web Server:** Apache 2.4
- **Frontend:** Bootstrap 5.3.0, Bootstrap Icons 1.11.0
- **Containerization:** Docker & Docker Compose
- **Database Admin:** phpMyAdmin

---

## 🔒 Security Notes

### Development vs Production

**Current Setup (Development):**
- Default passwords (CHANGE THESE IN PRODUCTION!)
- Root database access enabled
- Debug mode may be on
- CORS unrestricted

**For Production Deployment:**
1. Change all default passwords
2. Use environment variables for credentials
3. Enable HTTPS/SSL
4. Restrict database access
5. Enable error logging (disable display_errors)
6. Add rate limiting
7. Implement CSRF protection
8. Use prepared statements (already implemented)
9. Sanitize all user inputs (already implemented)

---

## 💡 Tips & Best Practices

### Performance
- Keep Docker Desktop updated
- Allocate adequate resources to Docker (Settings → Resources)
- Use volumes for persistent data
- Don't run unnecessary containers

### Database
- Regular backups of important data
- Use phpMyAdmin for quick queries
- Monitor database size in long-term use

### Development
- Use browser DevTools for debugging (F12)
- Check Docker logs for server errors
- Test on multiple browsers (Chrome, Firefox, Edge)
- Test mobile responsiveness (DevTools → Toggle device toolbar)

---

## 📞 Support & Resources

### Docker Documentation
- https://docs.docker.com/
- https://docs.docker.com/compose/

### PHP Documentation
- https://www.php.net/manual/en/

### MySQL Documentation
- https://dev.mysql.com/doc/

### Bootstrap Documentation
- https://getbootstrap.com/docs/5.3/

---

## 🎉 Quick Reference Card

### Start Everything
```powershell
docker compose up -d
```

### Stop Everything
```powershell
docker compose down
```

### Fresh Start (Delete All Data)
```powershell
docker compose down -v
docker compose up -d --build
# Wait 15 seconds
docker compose exec db mysql -u root -proot_password_change_me -e "CREATE DATABASE IF NOT EXISTS garage_db;"
docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SOURCE /docker-entrypoint-initdb.d/seed.sql;"
```

### Access Application
- **Landing Page:** http://localhost:8080/garage_system/public/welcome.php
- **Staff Login:** http://localhost:8080/garage_system/public/staff_login.php
- **Customer Login:** http://localhost:8080/garage_system/public/customer_login.php
- **phpMyAdmin:** http://localhost:8081

### Default Logins
- **Staff:** admin_user / staffpass
- **Customer:** alice@example.com / staffpass

---

**🎊 You're all set! Enjoy using Screw Dheela Management System!**
