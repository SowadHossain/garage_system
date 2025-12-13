<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/role_check.php';

// Check if user is staff
requireRole(['admin', 'receptionist', 'mechanic']);

// Get list of mechanics for filter
$mechanics_result = $conn->query("SELECT staff_id, name FROM staff WHERE role = 'mechanic' AND active = 1 ORDER BY name");
$mechanics = $mechanics_result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Advanced Search';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../public/staff_dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Advanced Search</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-search me-2"></i>Advanced Search
            </h2>
            <p class="text-muted">Find appointments, bills, and jobs with powerful filtering options</p>
        </div>
    </div>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <div class="card shadow-sm position-sticky" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>Filters
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Entity Selection -->
                    <div class="mb-4">
                        <label for="entity" class="form-label fw-bold">Search In</label>
                        <select id="entity" class="form-select form-select-sm">
                            <option value="appointments">Appointments</option>
                            <option value="bills">Bills</option>
                            <option value="jobs">Jobs</option>
                        </select>
                    </div>

                    <hr>

                    <!-- Search Term -->
                    <div class="mb-4">
                        <label for="search" class="form-label fw-bold">Search Term</label>
                        <input 
                            type="text" 
                            id="search" 
                            class="form-control form-control-sm"
                            placeholder="Name, ID, registration..."
                        >
                        <small class="form-text text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Search by customer name, reference ID, or vehicle registration
                        </small>
                    </div>

                    <hr>

                    <!-- Date Range -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Date Range</label>
                        <div class="mb-2">
                            <input 
                                type="date" 
                                id="date_from" 
                                class="form-control form-control-sm"
                                placeholder="From date"
                            >
                            <small class="text-muted">From</small>
                        </div>
                        <div>
                            <input 
                                type="date" 
                                id="date_to" 
                                class="form-control form-control-sm"
                                placeholder="To date"
                            >
                            <small class="text-muted">To</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Status Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Status</label>
                        <div id="status-filters" class="d-none"></div>
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Status options change based on entity type
                        </small>
                    </div>

                    <hr>

                    <!-- Price Range (Bills only) -->
                    <div class="mb-4 d-none" id="price-filter">
                        <label class="form-label fw-bold">Amount Range</label>
                        <div class="mb-2">
                            <input 
                                type="number" 
                                id="price_min" 
                                class="form-control form-control-sm"
                                placeholder="Min amount"
                                step="0.01"
                            >
                            <small class="text-muted">Minimum</small>
                        </div>
                        <div>
                            <input 
                                type="number" 
                                id="price_max" 
                                class="form-control form-control-sm"
                                placeholder="Max amount"
                                step="0.01"
                            >
                            <small class="text-muted">Maximum</small>
                        </div>
                    </div>

                    <!-- Mechanic Filter (Jobs only) -->
                    <div class="mb-4 d-none" id="mechanic-filter">
                        <label for="staff_id" class="form-label fw-bold">Mechanic</label>
                        <select id="staff_id" class="form-select form-select-sm">
                            <option value="">All Mechanics</option>
                            <?php foreach ($mechanics as $mechanic): ?>
                                <option value="<?php echo $mechanic['staff_id']; ?>">
                                    <?php echo htmlspecialchars($mechanic['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button id="search-btn" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                        <button id="clear-btn" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-clockwise me-2"></i>Clear Filters
                        </button>
                        <button id="export-btn" class="btn btn-success btn-sm d-none">
                            <i class="bi bi-download me-2"></i>Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Area -->
        <div class="col-lg-9">
            <!-- Search Status -->
            <div id="search-status" class="alert alert-info d-none mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <span id="status-text"></span>
            </div>

            <!-- Results Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>Search Results
                    </h5>
                    <span id="result-count" class="badge bg-light text-dark">0 results</span>
                </div>
                <div class="card-body p-0">
                    <!-- Results Table (loaded via AJAX) -->
                    <div id="results-container">
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-search" style="font-size: 3rem;"></i>
                            <p class="mt-3">Enter search criteria and click "Search" to view results</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <nav id="pagination" aria-label="Page navigation" class="d-none"></nav>
                </div>
            </div>

            <!-- Filters Applied Info -->
            <div id="filters-info" class="mt-4 d-none">
                <div class="alert alert-secondary">
                    <strong>Active Filters:</strong>
                    <div id="applied-filters" class="mt-2">
                        <!-- Filters list populated via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div id="loading-spinner" class="d-none position-fixed top-50 start-50 translate-middle">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<style>
    #loading-spinner {
        z-index: 9999;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Status options for each entity type
const statusOptions = {
    'appointments': [
        { value: 'booked', label: 'Booked' },
        { value: 'pending', label: 'Pending' },
        { value: 'completed', label: 'Completed' },
        { value: 'cancelled', label: 'Cancelled' }
    ],
    'bills': [
        { value: 'paid', label: 'Paid' },
        { value: 'unpaid', label: 'Unpaid' }
    ],
    'jobs': [
        { value: 'open', label: 'Open' },
        { value: 'completed', label: 'Completed' },
        { value: 'cancelled', label: 'Cancelled' }
    ]
};

let currentPage = 1;
let currentFilters = {};

// Initialize
$(document).ready(function() {
    updateStatusFilters('appointments');
    
    // Event handlers
    $('#entity').on('change', function() {
        updateStatusFilters($(this).val());
        showPriceFilter($(this).val() === 'bills');
        showMechanicFilter($(this).val() === 'jobs');
    });
    
    $('#search-btn').on('click', function() {
        currentPage = 1;
        performSearch();
    });
    
    $('#clear-btn').on('click', function() {
        clearFilters();
    });
    
    $('#export-btn').on('click', function() {
        exportResults();
    });
    
    // Enter key in search field
    $('#search').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1;
            performSearch();
        }
    });
});

function updateStatusFilters(entity) {
    const statusContainer = $('#status-filters');
    const options = statusOptions[entity] || [];
    
    statusContainer.empty();
    
    options.forEach(option => {
        const html = `
            <div class="form-check">
                <input class="form-check-input status-checkbox" type="checkbox" value="${option.value}" id="status_${option.value}">
                <label class="form-check-label" for="status_${option.value}">
                    ${option.label}
                </label>
            </div>
        `;
        statusContainer.append(html);
    });
}

function showPriceFilter(show) {
    if (show) {
        $('#price-filter').removeClass('d-none');
    } else {
        $('#price-filter').addClass('d-none');
        $('#price_min').val('');
        $('#price_max').val('');
    }
}

function showMechanicFilter(show) {
    if (show) {
        $('#mechanic-filter').removeClass('d-none');
    } else {
        $('#mechanic-filter').addClass('d-none');
        $('#staff_id').val('');
    }
}

function performSearch() {
    showLoadingSpinner(true);
    
    // Collect filters
    const filters = {
        entity: $('#entity').val(),
        search: $('#search').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val(),
        status: [],
        staff_id: $('#staff_id').val() || 0,
        price_min: $('#price_min').val() || 0,
        price_max: $('#price_max').val() || 0,
        page: currentPage
    };
    
    // Get selected statuses
    $('.status-checkbox:checked').each(function() {
        filters.status.push($(this).val());
    });
    
    currentFilters = filters;
    
    $.ajax({
        url: '../api/search_advanced.php',
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            displayResults(response);
            showLoadingSpinner(false);
        },
        error: function(xhr, status, error) {
            console.error('Search error:', error);
            $('#results-container').html(`
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error performing search: ${error}
                </div>
            `);
            showLoadingSpinner(false);
        }
    });
}

function displayResults(response) {
    if (!response.success) {
        $('#results-container').html(`<div class="alert alert-danger m-3">${response.error}</div>`);
        return;
    }
    
    const entity = response.entity;
    const data = response.data;
    
    // Update result count
    $('#result-count').text(`${response.total} results`);
    
    if (data.length === 0) {
        $('#results-container').html(`
            <div class="text-center p-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">No results found</p>
            </div>
        `);
        $('#pagination').addClass('d-none');
        return;
    }
    
    // Build table
    let html = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr>';
    
    if (entity === 'appointments') {
        html += `
            <th>Appointment ID</th>
            <th>Date/Time</th>
            <th>Status</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Vehicle</th>
            <th>Actions</th>
        </tr></thead><tbody>`;
        
        data.forEach(row => {
            const statusBadge = getStatusBadge(row.status, entity);
            html += `
                <tr>
                    <td><strong>#${row.appointment_id}</strong></td>
                    <td>${new Date(row.appointment_datetime).toLocaleString()}</td>
                    <td>${statusBadge}</td>
                    <td>${row.customer_name}</td>
                    <td>${row.phone || 'N/A'}</td>
                    <td>${row.brand || 'N/A'} ${row.model || ''}</td>
                    <td>
                        <a href="../appointments/view_appointments.php?id=${row.appointment_id}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });
    } else if (entity === 'bills') {
        html += `
            <th>Bill ID</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Customer</th>
            <th>Actions</th>
        </tr></thead><tbody>`;
        
        data.forEach(row => {
            const statusBadge = getStatusBadge(row.payment_status, 'bills');
            html += `
                <tr>
                    <td><strong>#${row.bill_id}</strong></td>
                    <td>${new Date(row.bill_date).toLocaleDateString()}</td>
                    <td><strong>$${parseFloat(row.total_amount).toFixed(2)}</strong></td>
                    <td>${statusBadge}</td>
                    <td>${row.customer_name}</td>
                    <td>
                        <a href="../bills/view.php?id=${row.bill_id}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });
    } else if (entity === 'jobs') {
        html += `
            <th>Job ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Customer</th>
            <th>Mechanic</th>
            <th>Actions</th>
        </tr></thead><tbody>`;
        
        data.forEach(row => {
            const statusBadge = getStatusBadge(row.status, 'jobs');
            html += `
                <tr>
                    <td><strong>#${row.job_id}</strong></td>
                    <td>${new Date(row.job_date).toLocaleDateString()}</td>
                    <td>${statusBadge}</td>
                    <td>${row.customer_name}</td>
                    <td>${row.mechanic_name || 'Unassigned'}</td>
                    <td>
                        <a href="../jobs/list.php?id=${row.job_id}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });
    }
    
    html += '</tbody></table></div>';
    $('#results-container').html(html);
    
    // Show export button
    $('#export-btn').removeClass('d-none');
    
    // Build pagination
    buildPagination(response.total_pages, response.page);
    
    // Show applied filters
    if (response.filters_applied.length > 0) {
        $('#filters-info').removeClass('d-none');
        $('#applied-filters').html(
            response.filters_applied.map(f => `<span class="badge bg-secondary me-2">${f}</span>`).join('')
        );
    } else {
        $('#filters-info').addClass('d-none');
    }
}

function getStatusBadge(status, entity) {
    const colors = {
        'booked': 'primary',
        'pending': 'warning',
        'completed': 'success',
        'cancelled': 'danger',
        'paid': 'success',
        'unpaid': 'danger',
        'open': 'warning'
    };
    const color = colors[status] || 'secondary';
    return `<span class="badge bg-${color}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
}

function buildPagination(totalPages, currentPageNum) {
    const paginationHtml = $('<nav aria-label="Page navigation"><ul class="pagination"></ul></nav>');
    const ul = paginationHtml.find('ul');
    
    // Previous button
    if (currentPageNum > 1) {
        ul.append(`<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPageNum - 1}); return false;">Previous</a></li>`);
    }
    
    // Page numbers
    for (let i = Math.max(1, currentPageNum - 2); i <= Math.min(totalPages, currentPageNum + 2); i++) {
        const activeClass = i === currentPageNum ? 'active' : '';
        ul.append(`<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a></li>`);
    }
    
    // Next button
    if (currentPageNum < totalPages) {
        ul.append(`<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPageNum + 1}); return false;">Next</a></li>`);
    }
    
    $('#pagination').html(paginationHtml.html()).removeClass('d-none');
}

function goToPage(page) {
    currentPage = page;
    performSearch();
    $('html, body').animate({ scrollTop: 0 }, 'smooth');
}

function clearFilters() {
    $('#search').val('');
    $('#date_from').val('');
    $('#date_to').val('');
    $('#price_min').val('');
    $('#price_max').val('');
    $('#staff_id').val('');
    $('.status-checkbox').prop('checked', false);
    $('#results-container').html(`
        <div class="text-center p-5 text-muted">
            <i class="bi bi-search" style="font-size: 3rem;"></i>
            <p class="mt-3">Enter search criteria and click "Search" to view results</p>
        </div>
    `);
    $('#pagination').addClass('d-none');
    $('#export-btn').addClass('d-none');
    $('#filters-info').addClass('d-none');
}

function exportResults() {
    const params = new URLSearchParams(currentFilters);
    window.location.href = '../api/export_search.php?' + params.toString();
}

function showLoadingSpinner(show) {
    if (show) {
        $('#loading-spinner').removeClass('d-none');
    } else {
        $('#loading-spinner').addClass('d-none');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
