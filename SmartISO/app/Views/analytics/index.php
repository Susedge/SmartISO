<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/pastel.css') ?>" rel="stylesheet">
<style>
/* Analytics specific styles using pastel.css variables */
.analytics-card {
    background: white;
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: var(--border-radius);
    color: var(--text-color);
    transition: all var(--transition-speed);
    box-shadow: var(--box-shadow);
}

.analytics-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.analytics-card .card-body {
    padding: 1.5rem;
}

.stat-icon {
    font-size: 2rem;
    color: var(--primary-color);
    opacity: 0.8;
}

.chart-container {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
}

.chart-container:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.chart-container h5 {
    color: var(--dark-color);
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-container h5 i {
    color: var(--primary-color);
}

.analytics-header {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--box-shadow);
    border: 1px solid rgba(0,0,0,0.05);
}

.analytics-header h1 {
    color: var(--dark-color);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.analytics-header p {
    color: var(--text-color);
    opacity: 0.8;
    margin: 0;
}

.page-background {
    background: var(--body-bg);
    min-height: 100vh;
    padding: 1rem;
}

.performance-table {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
}

.performance-table .table {
    margin-bottom: 0;
}

.performance-table .table th {
    background: var(--light-color);
    color: var(--dark-color);
    font-weight: 600;
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.performance-table .table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.badge {
    border-radius: 50rem;
    padding: 0.35em 0.65em;
    font-weight: 500;
    font-size: 0.75em;
}

.progress-bar-minimal {
    background: var(--light-color);
    border-radius: 50rem;
    height: 4px;
    overflow: hidden;
}

.progress-fill-minimal {
    background: var(--primary-color);
    height: 100%;
    border-radius: 50rem;
    transition: width 0.3s ease;
}

.export-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    color: var(--text-color);
    margin: 2rem 0;
    box-shadow: var(--box-shadow);
    border: 1px solid rgba(0,0,0,0.05);
}

.export-form {
    background: var(--light-color);
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.modern-select, .modern-input {
    background: white;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 0.5rem;
    padding: 0.6rem 1rem;
    color: var(--text-color);
    transition: all var(--transition-speed);
}

.modern-select:focus, .modern-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(255, 209, 102, 0.25);
}

.modern-btn {
    background: var(--primary-color);
    border: 1px solid var(--primary-color);
    border-radius: 0.5rem;
    padding: 0.6rem 1.5rem;
    color: var(--dark-color);
    font-weight: 600;
    transition: all var(--transition-speed);
    box-shadow: none;
}

.modern-btn:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    color: var(--dark-color);
    transform: translateY(-1px);
}

.loading-spinner {
    display: none;
    text-align: center;
    padding: 2rem;
}

.metric-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    transition: all var(--transition-speed);
    border: 1px solid rgba(0,0,0,0.05);
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .analytics-card {
        margin-bottom: 1rem;
    }
    
    .chart-container {
        padding: 1rem;
    }
    
    .export-section {
        margin: 1rem 0;
        padding: 1.5rem;
    }
    
    .page-background {
        padding: 0.5rem;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="page-background">
    <div class="container-fluid">
        <div class="analytics-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 fw-bold">📊 Analytics Dashboard</h1>
                    <p class="mb-0">Comprehensive insights and reporting</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>
            </div>
        </div>

<!-- Overview Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="analytics-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Submissions</div>
                        <div class="h2 mb-0" id="total-submissions"><?= number_format($overview['total_submissions']) ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-arrow-up me-1"></i>
                        <?= $overview['recent_submissions'] ?> this month
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="analytics-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Active Users</div>
                        <div class="h2 mb-0" id="total-users"><?= number_format($overview['total_users']) ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-building me-1"></i>
                        <?= $overview['total_departments'] ?> departments
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="analytics-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Completion Rate</div>
                        <div class="h2 mb-0" id="completion-rate"><?= $overview['completion_rate'] ?>%</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress-bar-minimal">
                        <div class="progress-fill-minimal" style="width: <?= $overview['completion_rate'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="analytics-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Available Forms</div>
                        <div class="h2 mb-0" id="total-forms"><?= number_format($overview['total_forms']) ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-check-circle me-1"></i>
                        All active
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row">
    <!-- Status Distribution -->
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-chart-pie me-2 text-primary"></i>
                Status Distribution
            </h5>
            <canvas id="statusChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- Submissions Timeline -->
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                Submissions Timeline (30 Days)
            </h5>
            <canvas id="timelineChart" height="300"></canvas>
        </div>
    </div>
</div>

<div class="row">
    <!-- Form Usage -->
    <div class="col-xl-8 col-lg-12 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-chart-bar me-2 text-primary"></i>
                Most Used Forms
            </h5>
            <canvas id="formUsageChart" height="250"></canvas>
        </div>
    </div>
    
    <!-- Department Stats -->
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-building me-2 text-primary"></i>
                Department Activity
            </h5>
            <canvas id="departmentChart" height="250"></canvas>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-trophy me-2"></i>
                Top Performers (This Month)
            </h5>
            <div class="performance-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="text-center">Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($performanceMetrics['user_productivity'])): ?>
                            <?php foreach (array_slice($performanceMetrics['user_productivity'], 0, 5) as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2" style="width: 32px; height: 32px;">
                                                <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                                            </div>
                                            <?= esc($user['full_name']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $user['submissions'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center py-4">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-clock me-2"></i>
                Average Processing Times
            </h5>
            <div class="performance-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-end">Average Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($performanceMetrics['status_processing_times'])): ?>
                            <?php foreach ($performanceMetrics['status_processing_times'] as $status): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary me-2"><?= ucfirst(esc($status['status'])) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong><?= round($status['avg_hours'], 1) ?>h</strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center py-4">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download me-2"></i>Export Analytics Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="<?= base_url('analytics/export') ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select modern-select" id="report_type" name="report_type" required>
                                <option value="overview">Complete Overview</option>
                                <option value="forms">Form Analytics</option>
                                <option value="departments">Department Statistics</option>
                                <option value="performance">Performance Metrics</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date_range" class="form-label">Date Range</label>
                            <select class="form-select modern-select" id="date_range" name="date_range" required>
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="format" class="form-label">Export Format</label>
                            <div class="btn-group w-100" role="group" aria-label="Format selection">
                                <input type="radio" class="btn-check" name="format" id="format_pdf" value="pdf" checked>
                                <label class="btn btn-outline-primary" for="format_pdf">
                                    <i class="fas fa-file-pdf me-2"></i>PDF Report
                                </label>
                                
                                <input type="radio" class="btn-check" name="format" id="format_word" value="word">
                                <label class="btn btn-outline-primary" for="format_word">
                                    <i class="fas fa-file-word me-2"></i>Word Document
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-2">Report will include:</h6>
                        <ul class="mb-0 small">
                            <li>Executive summary with key metrics</li>
                            <li>Visual charts and graphs</li>
                            <li>Detailed data tables</li>
                            <li>Trend analysis and insights</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="exportForm" class="btn btn-primary">
                    <i class="fas fa-download me-2"></i>Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2 text-muted">Refreshing data...</p>
</div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
<script>
// Chart.js default configuration
Chart.defaults.font.family = 'Arial, sans-serif';
Chart.defaults.color = '#666';

// Analytics data from PHP
const analyticsData = {
    overview: <?= json_encode($overview) ?>,
    formStats: <?= json_encode($formStats) ?>,
    departmentStats: <?= json_encode($departmentStats) ?>,
    timelineData: <?= json_encode($timelineData) ?>,
    performanceMetrics: <?= json_encode($performanceMetrics) ?>
};

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Status Distribution Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = analyticsData.overview.status_distribution;
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
            datasets: [{
                data: statusData.map(item => item.count),
                backgroundColor: [
                    '#FFD166', // Primary yellow
                    '#FFADC7', // Accent pink  
                    '#06D6A0', // Success green
                    '#FFF3C4', // Secondary yellow
                    '#EF476F', // Danger red
                    '#118AB2'  // Info blue
                ],
                borderColor: [
                    '#EABC41', // Primary dark
                    '#FF9DB4', 
                    '#05C194',
                    '#F5E8A3',
                    '#DC3545',
                    '#0F7A9F'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
    
    // Timeline Chart
    const timelineCtx = document.getElementById('timelineChart').getContext('2d');
    const timelineData = analyticsData.timelineData.daily_submissions;
    
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: timelineData.map(item => new Date(item.date).toLocaleDateString()),
            datasets: [{
                label: 'Submissions',
                data: timelineData.map(item => item.count),
                borderColor: '#FFD166',
                backgroundColor: 'rgba(255, 209, 102, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#EABC41',
                pointBorderColor: '#FFD166',
                pointRadius: 6,
                pointHoverRadius: 8,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Form Usage Chart
    const formUsageCtx = document.getElementById('formUsageChart').getContext('2d');
    const formUsageData = analyticsData.formStats.form_usage.slice(0, 8);
    
    new Chart(formUsageCtx, {
        type: 'bar',
        data: {
            labels: formUsageData.map(item => item.form_name),
            datasets: [{
                label: 'Usage Count',
                data: formUsageData.map(item => item.usage_count),
                backgroundColor: '#FFADC7',
                borderColor: '#FF9DB4',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                hoverBackgroundColor: '#FFD166',
                hoverBorderColor: '#EABC41'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Department Chart
    const deptCtx = document.getElementById('departmentChart').getContext('2d');
    const deptData = analyticsData.departmentStats.submissions_by_department.slice(0, 5);
    
    new Chart(deptCtx, {
        type: 'polarArea',
        data: {
            labels: deptData.map(item => item.department_name || 'Unassigned'),
            datasets: [{
                data: deptData.map(item => item.submission_count),
                backgroundColor: [
                    'rgba(255, 209, 102, 0.8)', // Primary yellow
                    'rgba(255, 173, 199, 0.8)', // Accent pink
                    'rgba(6, 214, 160, 0.8)',   // Success green
                    'rgba(255, 243, 196, 0.8)', // Secondary yellow
                    'rgba(239, 71, 111, 0.8)'   // Danger red
                ],
                borderColor: [
                    '#EABC41', // Primary dark
                    '#FF9DB4',
                    '#05C194', 
                    '#F5E8A3',
                    '#DC3545'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                }
            }
        }
    });
}

function refreshData() {
    const spinner = document.getElementById('loadingSpinner');
    spinner.style.display = 'block';
    
    // Simulate data refresh (you can implement actual API calls here)
    setTimeout(() => {
        spinner.style.display = 'none';
        location.reload();
    }, 2000);
}

// Export form submission
document.getElementById('exportForm').addEventListener('submit', function(e) {
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    submitBtn.disabled = true;
    
    // Re-enable button after a delay (form will submit normally)
    setTimeout(() => {
        submitBtn.innerHTML = '<i class="fas fa-download me-2"></i>Generate Report';
        submitBtn.disabled = false;
    }, 3000);
});
</script>
<?= $this->endSection() ?>
