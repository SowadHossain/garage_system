<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/role_check.php';

// Check if user is admin (analytics is admin-only)
requireRole(['admin']);

// Get mechanics for filter
$mechanics_result = $conn->query("SELECT staff_id, name FROM staff WHERE role = 'mechanic' AND active = 1 ORDER BY name");
$mechanics = $mechanics_result->fetch_all(MYSQLI_ASSOC);

// Get services for filter
$services_result = $conn->query("SELECT service_id, name FROM services ORDER BY name LIMIT 50");
$services = $services_result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Analytics Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../public/admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Analytics</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-graph-up me-2"></i>Analytics Dashboard
            </h2>
            <p class="text-muted">Business insights and performance metrics</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-funnel me-2"></i>Filters
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label fw-bold">From Date</label>
                    <input 
                        type="date" 
                        id="date_from" 
                        class="form-control"
                        value="<?php echo date('Y-m-01'); ?>"
                    >
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label fw-bold">To Date</label>
                    <input 
                        type="date" 
                        id="date_to" 
                        class="form-control"
                        value="<?php echo date('Y-m-d'); ?>"
                    >
                </div>
                <div class="col-md-3">
                    <label for="mechanic_filter" class="form-label fw-bold">Mechanic</label>
                    <select id="mechanic_filter" class="form-select">
                        <option value="">All Mechanics</option>
                        <?php foreach ($mechanics as $mechanic): ?>
                            <option value="<?php echo $mechanic['staff_id']; ?>">
                                <?php echo htmlspecialchars($mechanic['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="service_filter" class="form-label fw-bold">Service</label>
                    <select id="service_filter" class="form-select">
                        <option value="">All Services</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['service_id']; ?>">
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col">
                    <button id="apply-filters" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Apply Filters
                    </button>
                </div>
                <div class="col">
                    <button id="reset-filters" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h3 class="text-primary mb-0" id="metric-revenue">$0.00</h3>
                    <small class="text-muted">All bills</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Paid Amount</h6>
                    <h3 class="text-success mb-0" id="metric-paid">$0.00</h3>
                    <small class="text-muted">Completed payments</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Outstanding</h6>
                    <h3 class="text-danger mb-0" id="metric-unpaid">$0.00</h3>
                    <small class="text-muted">Unpaid invoices</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Completed Jobs</h6>
                    <h3 class="text-warning mb-0" id="metric-jobs">0</h3>
                    <small class="text-muted">Finished services</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Revenue Trend -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Revenue Trend
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Service Performance -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-wrench me-2"></i>Top Services
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="servicesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mechanic Performance -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-gear me-2"></i>Mechanic Efficiency
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="mechanicsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Payment Status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Payment Status
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="paymentStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment Status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar2-check me-2"></i>Appointment Status
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="appointmentStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Acquisition -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill me-2"></i>New Customers
                    </h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="customerAcquisitionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Summary Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Performance Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Metric</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody id="summary-table">
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none d-flex align-items-center justify-content-center" style="z-index: 9999;">
    <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Chart instances
let charts = {};

// Colors
const colors = {
    primary: '#0d6efd',
    success: '#198754',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#0dcaf0',
    secondary: '#6c757d'
};

const chartColors = [
    '#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0',
    '#6c757d', '#6610f2', '#fd7e14', '#20c997', '#e83e8c'
];

// Initialize on load
$(document).ready(function() {
    loadAnalytics();
    
    $('#apply-filters').on('click', function() {
        loadAnalytics();
    });
    
    $('#reset-filters').on('click', function() {
        $('#date_from').val('<?php echo date('Y-m-01'); ?>');
        $('#date_to').val('<?php echo date('Y-m-d'); ?>');
        $('#mechanic_filter').val('');
        $('#service_filter').val('');
        loadAnalytics();
    });
});

function getFilters() {
    return {
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val(),
        mechanic_id: $('#mechanic_filter').val() || 0,
        service_id: $('#service_filter').val() || 0
    };
}

function showLoading(show) {
    if (show) {
        $('#loading-overlay').removeClass('d-none');
    } else {
        $('#loading-overlay').addClass('d-none');
    }
}

function loadAnalytics() {
    showLoading(true);
    const filters = getFilters();
    
    // Load all data in parallel
    Promise.all([
        loadRevenueData(filters),
        loadServiceData(filters),
        loadMechanicData(filters),
        loadPaymentStatusData(filters),
        loadAppointmentStatusData(filters),
        loadCustomerAcquisitionData(filters),
        loadSummaryData(filters)
    ]).then(() => {
        showLoading(false);
    }).catch(error => {
        console.error('Error loading analytics:', error);
        showLoading(false);
        alert('Error loading analytics data');
    });
}

function loadRevenueData(filters) {
    return $.ajax({
        url: '../api/analytics_revenue.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            updateRevenueMetrics(response);
            drawRevenueChart(response);
        }
    });
}

function loadServiceData(filters) {
    return $.ajax({
        url: '../api/analytics_services.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            drawServicesChart(response);
        }
    });
}

function loadMechanicData(filters) {
    return $.ajax({
        url: '../api/analytics_mechanics.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            drawMechanicsChart(response);
        }
    });
}

function loadPaymentStatusData(filters) {
    return $.ajax({
        url: '../api/analytics_payment_status.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            drawPaymentStatusChart(response);
        }
    });
}

function loadAppointmentStatusData(filters) {
    return $.ajax({
        url: '../api/analytics_appointment_status.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            drawAppointmentStatusChart(response);
        }
    });
}

function loadCustomerAcquisitionData(filters) {
    return $.ajax({
        url: '../api/analytics_customer_acquisition.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            drawCustomerAcquisitionChart(response);
        }
    });
}

function loadSummaryData(filters) {
    return $.ajax({
        url: '../api/analytics_summary.php',
        method: 'GET',
        data: filters,
        dataType: 'json'
    }).done(function(response) {
        if (response.success) {
            updateSummaryTable(response);
        }
    });
}

function updateRevenueMetrics(data) {
    $('#metric-revenue').text('$' + parseFloat(data.total_revenue || 0).toFixed(2));
    $('#metric-paid').text('$' + parseFloat(data.paid_revenue || 0).toFixed(2));
    $('#metric-unpaid').text('$' + parseFloat(data.unpaid_revenue || 0).toFixed(2));
    $('#metric-jobs').text(data.completed_jobs || 0);
}

function drawRevenueChart(data) {
    if (charts.revenue) {
        charts.revenue.destroy();
    }
    
    const ctx = document.getElementById('revenueChart').getContext('2d');
    charts.revenue = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.months || [],
            datasets: [
                {
                    label: 'Total Revenue',
                    data: data.revenue_data || [],
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Paid Revenue',
                    data: data.paid_data || [],
                    borderColor: colors.success,
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '$' + v } }
            }
        }
    });
}

function drawServicesChart(data) {
    if (charts.services) {
        charts.services.destroy();
    }
    
    const ctx = document.getElementById('servicesChart').getContext('2d');
    charts.services = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.services || [],
            datasets: [{
                label: 'Service Count',
                data: data.counts || [],
                backgroundColor: chartColors.slice(0, data.services?.length || 0)
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
}

function drawMechanicsChart(data) {
    if (charts.mechanics) {
        charts.mechanics.destroy();
    }
    
    const ctx = document.getElementById('mechanicsChart').getContext('2d');
    charts.mechanics = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.mechanics || [],
            datasets: [{
                label: 'Jobs Completed',
                data: data.job_counts || [],
                backgroundColor: colors.info
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
}

function drawPaymentStatusChart(data) {
    if (charts.paymentStatus) {
        charts.paymentStatus.destroy();
    }
    
    const ctx = document.getElementById('paymentStatusChart').getContext('2d');
    charts.paymentStatus = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{
                data: [data.paid_count || 0, data.unpaid_count || 0],
                backgroundColor: [colors.success, colors.danger]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function drawAppointmentStatusChart(data) {
    if (charts.appointmentStatus) {
        charts.appointmentStatus.destroy();
    }
    
    const ctx = document.getElementById('appointmentStatusChart').getContext('2d');
    charts.appointmentStatus = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.statuses || [],
            datasets: [{
                data: data.counts || [],
                backgroundColor: chartColors.slice(0, data.statuses?.length || 0)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function drawCustomerAcquisitionChart(data) {
    if (charts.customerAcquisition) {
        charts.customerAcquisition.destroy();
    }
    
    const ctx = document.getElementById('customerAcquisitionChart').getContext('2d');
    charts.customerAcquisition = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.months || [],
            datasets: [{
                label: 'New Customers',
                data: data.customer_counts || [],
                borderColor: colors.warning,
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

function updateSummaryTable(data) {
    let html = '';
    for (const [key, value] of Object.entries(data.metrics || {})) {
        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const formattedValue = typeof value === 'number' ? 
            (key.includes('revenue') || key.includes('amount') ? '$' + value.toFixed(2) : value) :
            value;
        html += `<tr><td>${formattedKey}</td><td class="text-end fw-bold">${formattedValue}</td></tr>`;
    }
    $('#summary-table').html(html || '<tr><td colspan="2" class="text-center text-muted">No data</td></tr>');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
