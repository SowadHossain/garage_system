<?php
// Public wrapper to expose appointments view through the webroot
// This file includes the actual implementation from the repository root
// so the application can be served from the `public` document root.

require_once __DIR__ . '/../../appointments/view_appointments.php';
