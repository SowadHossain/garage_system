<?php
// public/index.php

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

include __DIR__ . "/../includes/header.php";
?>

<h1 class="mb-4">Dashboard</h1>

<p>Welcome to the Garage Management System.</p>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Customers</h5>
                <p class="card-text">Manage customer information and view their vehicles.</p>
                <a href="/garage_system/customers/list.php" class="btn btn-primary btn-sm">View Customers</a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Appointments</h5>
                <p class="card-text">Create and manage service appointments.</p>
                <a href="/garage_system/appointments/list.php" class="btn btn-primary btn-sm">View Appointments</a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Bills</h5>
                <p class="card-text">Generate and review service bills.</p>
                <a href="/garage_system/bills/list.php" class="btn btn-primary btn-sm">View Bills</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
