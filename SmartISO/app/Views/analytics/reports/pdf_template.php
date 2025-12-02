<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SmartISO Analytics Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            line-height: 1.6;
            font-size: 10px;
            margin: 0;
            padding: 15px;
        }
        
        .header {
            background: linear-gradient(135deg, #FFD166 0%, #FFADC7 100%);
            color: #2c3e50;
            padding: 20px 25px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        
        .header .subtitle {
            font-size: 11px;
            margin-bottom: 8px;
        }
        
        .header .meta {
            font-size: 9px;
            opacity: 0.85;
        }
        
        .filter-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .filter-info strong {
            color: #856404;
        }
        
        .section {
            margin-bottom: 18px;
            page-break-inside: avoid;
            background: #fafbfc;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #FFD166;
        }
        
        .section-title {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #2c3e50;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            border-left: 3px solid #06D6A0;
            margin: -12px -12px 12px -12px;
            border-radius: 8px 8px 0 0;
        }
        
        .metrics-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .metric-card {
            flex: 1;
            min-width: 100px;
            background: linear-gradient(135deg, #ffffba, #ffdfba);
            border: 1px solid #f0e68c;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        
        .metric-value {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .metric-label {
            color: #5a6c7d;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 500;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border-radius: 6px;
            overflow: hidden;
            font-size: 9px;
        }
        
        .table th,
        .table td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e3f2fd;
        }
        
        .table th {
            background: linear-gradient(135deg, #06D6A0, #bae1ff);
            font-weight: 600;
            color: #2c3e50;
            font-size: 8px;
        }
        
        .table tr:nth-child(even) {
            background: #f8fcff;
        }
        
        .table-ranking th {
            background: linear-gradient(135deg, #FFD166, #FFADC7);
        }
        
        .progress-bar {
            background: #f0f4f8;
            border-radius: 6px;
            height: 5px;
            overflow: hidden;
            margin-top: 3px;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #06D6A0, #FFD166);
            height: 100%;
            border-radius: 6px;
        }
        
        .footer {
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .two-column {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        
        .two-column > div {
            flex: 1;
        }
        
        .two-column h4 {
            font-size: 10px;
            margin-bottom: 6px;
            color: #2c3e50;
        }
        
        .highlight {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border: 1px solid #81c784;
            border-radius: 6px;
            padding: 8px 10px;
            margin: 8px 0;
            font-size: 9px;
        }
        
        .rank-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 600;
            text-align: center;
            min-width: 20px;
        }
        
        .rank-1 { background: #FFD700; color: #333; }
        .rank-2 { background: #C0C0C0; color: #333; }
        .rank-3 { background: #CD7F32; color: #fff; }
        .rank-other { background: #e9ecef; color: #666; }
        
        .text-success { color: #27a844; }
        .text-warning { color: #fd7e14; }
        .text-danger { color: #e74c3c; }
        .text-info { color: #5bc0de; }
        
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: 500;
        }
        
        .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-submitted { background: #e2e3e5; color: #383d41; }
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 SmartISO Analytics Report</h1>
        <p class="subtitle"><?= $report_type_label ?? ucfirst($report_type) ?></p>
        <p class="meta">Generated on <?= date('F j, Y \a\t g:i A', strtotime($generated_at)) ?></p>
    </div>

    <?php if (!empty($filters)): ?>
    <div class="filter-info">
        <strong>🔍 Applied Filters:</strong>
        <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
            Date: <?= !empty($filters['date_from']) ? $filters['date_from'] : 'Start' ?> to <?= !empty($filters['date_to']) ? $filters['date_to'] : 'Present' ?> |
        <?php endif; ?>
        <?php if (!empty($filters['service'])): ?>
            Service: Filtered |
        <?php endif; ?>
        <?php if (!empty($filters['office'])): ?>
            Office: Filtered |
        <?php endif; ?>
        <?php if (!empty($filters['status'])): ?>
            Status: <?= ucfirst($filters['status']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">Executive Summary</div>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?= number_format($overview['total_submissions']) ?></div>
                <div class="metric-label">Total Submissions</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?= number_format($overview['total_users']) ?></div>
                <div class="metric-label">Active Users</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?= $overview['completion_rate'] ?>%</div>
                <div class="metric-label">Completion Rate</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $overview['completion_rate'] ?>%"></div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?= number_format($overview['recent_submissions']) ?></div>
                <div class="metric-label">Recent Activity</div>
            </div>
        </div>

        <div class="highlight">
            <strong>📊 Executive Summary:</strong>
            <div style="margin: 6px 0; font-size: 9px; line-height: 1.4;">
                • <strong><?= number_format($overview['total_submissions']) ?></strong> total submissions processed with <strong><?= $overview['completion_rate'] ?>%</strong> completion rate<br>
                • <strong><?= $overview['recent_submissions'] ?></strong> new submissions in the past 30 days showing <?= $overview['recent_submissions'] > 50 ? 'high' : ($overview['recent_submissions'] > 20 ? 'moderate' : 'low') ?> activity<br>
                • <strong><?= $overview['total_users'] ?></strong> active users across <strong><?= $overview['total_departments'] ?></strong> departments<br>
                • Performance trend: <?= $overview['completion_rate'] > 80 ? '🟢 Excellent' : ($overview['completion_rate'] > 60 ? '🟡 Good' : '🔴 Needs Attention') ?>
            </div>
        </div>
    </div>

    <!-- Service & Office Rankings -->
    <div class="section">
        <div class="section-title">🏆 Service & Office Rankings</div>
        
        <div class="two-column">
            <div>
                <h4>📋 Most Requested Services</h4>
                <table class="table table-ranking">
                    <thead>
                        <tr>
                            <th style="width: 30px">Rank</th>
                            <th>Service</th>
                            <th style="width: 60px">Requests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($formStats['form_usage'])): ?>
                            <?php $rank = 1; foreach (array_slice($formStats['form_usage'], 0, 8) as $service): ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge rank-<?= $rank <= 3 ? $rank : 'other' ?>">
                                            <?= $rank <= 3 ? '🏆' : '' ?><?= $rank ?>
                                        </span>
                                    </td>
                                    <td><?= esc($service['form_name']) ?></td>
                                    <td><strong><?= number_format($service['usage_count']) ?></strong></td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #6c757d;">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h4>🏢 Office with Most Requests</h4>
                <table class="table table-ranking">
                    <thead>
                        <tr>
                            <th style="width: 30px">Rank</th>
                            <th>Office/Department</th>
                            <th style="width: 60px">Requests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($departmentStats['submissions_by_department'])): ?>
                            <?php $rank = 1; foreach (array_slice($departmentStats['submissions_by_department'], 0, 8) as $office): ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge rank-<?= $rank <= 3 ? $rank : 'other' ?>">
                                            <?= $rank <= 3 ? '🏆' : '' ?><?= $rank ?>
                                        </span>
                                    </td>
                                    <td><?= esc($office['department_name'] ?: 'Unassigned') ?></td>
                                    <td><strong><?= number_format($office['submission_count']) ?></strong></td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #6c757d;">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="section">
        <div class="section-title">Form Status Distribution</div>
        
        <?php if (isset($chart_images['status_chart'])): ?>
            <div style="text-align: center; margin: 15px 0;">
                <img src="<?= $chart_images['status_chart'] ?>" alt="Status Distribution Chart" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        <?php endif; ?>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                    <th>Trend</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overview['status_distribution'] as $status): ?>
                    <?php $percentage = $overview['total_submissions'] > 0 ? ($status['count'] / $overview['total_submissions']) * 100 : 0; ?>
                    <tr>
                        <td><?= ucfirst(str_replace('_', ' ', esc($status['status']))) ?></td>
                        <td><?= number_format($status['count']) ?></td>
                        <td><?= number_format($percentage, 1) ?>%</td>
                        <td>
                            <div class="progress-bar" style="width: 100px;">
                                <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <?php if (!empty($formStats)): ?>
    <!-- Form Usage Statistics -->
    <div class="section">
        <div class="section-title">Form Usage Analytics</div>
        
        <?php if (isset($chart_images['form_usage_chart'])): ?>
            <div style="text-align: center; margin: 15px 0;">
                <img src="<?= $chart_images['form_usage_chart'] ?>" alt="Form Usage Chart" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        <?php endif; ?>
        
        <div class="two-column">
            <div>
                <h4>Most Popular Forms</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Form Name</th>
                            <th>Usage Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($formStats['form_usage'])): ?>
                            <?php foreach (array_slice($formStats['form_usage'], 0, 10) as $form): ?>
                                <tr>
                                    <td><?= esc($form['form_name']) ?></td>
                                    <td><strong><?= number_format($form['usage_count']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: #6c757d;">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h4>Processing Times</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Form Name</th>
                            <th>Avg. Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($formStats['processing_times'])): ?>
                            <?php foreach (array_slice($formStats['processing_times'], 0, 10) as $form): ?>
                                <tr>
                                    <td><?= esc($form['form_name']) ?></td>
                                    <td><?= round($form['avg_hours'], 1) ?>h</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: #6c757d;">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($departmentStats)): ?>
    <!-- Department Analysis -->
    <div class="section">
        <div class="section-title">Department Performance</div>
        
        <?php if (isset($chart_images['department_chart'])): ?>
            <div style="text-align: center; margin: 15px 0;">
                <img src="<?= $chart_images['department_chart'] ?>" alt="Department Activity Chart" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        <?php endif; ?>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Submissions</th>
                    <th>Completion Rate</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($departmentStats['submissions_by_department'])): ?>
                    <?php foreach ($departmentStats['submissions_by_department'] as $dept): ?>
                        <?php 
                        $completionData = null;
                        foreach ($departmentStats['completion_by_department'] as $comp) {
                            if ($comp['department_name'] === $dept['department_name']) {
                                $completionData = $comp;
                                break;
                            }
                        }
                        $completionRate = $completionData && $completionData['total'] > 0 ? 
                            ($completionData['completed'] / $completionData['total']) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= esc($dept['department_name'] ?: 'Unassigned') ?></td>
                            <td><?= number_format($dept['submission_count']) ?></td>
                            <td><?= number_format($completionRate, 1) ?>%</td>
                            <td>
                                <span class="<?= $completionRate > 80 ? 'text-success' : ($completionRate > 60 ? 'text-warning' : 'text-danger') ?>">
                                    <?= $completionRate > 80 ? '●' : ($completionRate > 60 ? '●' : '●') ?>
                                    <?= $completionRate > 80 ? 'Excellent' : ($completionRate > 60 ? 'Good' : 'Needs Improvement') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #6c757d;">No department data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($performanceMetrics)): ?>
    <!-- Performance Metrics -->
    <div class="section">
        <div class="section-title">Performance Insights</div>
        
        <div class="two-column">
            <div>
                <h4>Top Performers (This Month)</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($performanceMetrics['user_productivity'])): ?>
                            <?php foreach (array_slice($performanceMetrics['user_productivity'], 0, 8) as $user): ?>
                                <tr>
                                    <td><?= esc($user['full_name']) ?></td>
                                    <td><strong><?= $user['submissions'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: #6c757d;">No performance data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h4>Processing Time Analysis</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Avg. Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($performanceMetrics['status_processing_times'])): ?>
                            <?php foreach ($performanceMetrics['status_processing_times'] as $status): ?>
                                <tr>
                                    <td><?= ucfirst(str_replace('_', ' ', esc($status['status']))) ?></td>
                                    <td><?= round($status['avg_hours'], 1) ?> hours</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: #6c757d;">No processing time data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($timelineData)): ?>
    <!-- Timeline Analysis -->
    <div class="section">
        <div class="section-title">Timeline Analysis</div>
        
        <?php if (isset($chart_images['timeline_chart'])): ?>
            <div style="text-align: center; margin: 15px 0;">
                <img src="<?= $chart_images['timeline_chart'] ?>" alt="Submissions Timeline Chart" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        <?php endif; ?>
        
        <h4>Recent Activity Summary</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Submissions</th>
                    <th>Trend</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($timelineData['daily_submissions'])): ?>
                    <?php 
                    $recentData = array_slice($timelineData['daily_submissions'], -7); // Last 7 days
                    foreach (array_reverse($recentData) as $day): 
                    ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($day['date'])) ?></td>
                            <td><?= $day['count'] ?></td>
                            <td>
                                <div class="progress-bar" style="width: 80px;">
                                    <div class="progress-fill" style="width: <?= min(100, ($day['count'] / max(1, max(array_column($recentData, 'count')))) * 100) ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #6c757d;">No timeline data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="page-break"></div>

    <?php if (!empty($recentSubmissions)): ?>
    <!-- Recent Submissions -->
    <div class="section">
        <div class="section-title">📋 Recent Submissions (Last 100)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 30px">ID</th>
                    <th>Form Name</th>
                    <th>Submitted By</th>
                    <th>Department</th>
                    <th style="width: 50px">Priority</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                    <th>Date Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentSubmissions)): ?>
                    <?php foreach (array_slice($recentSubmissions, 0, 50) as $submission): ?>
                        <?php 
                        $statusClass = 'status-submitted';
                        if ($submission['status'] === 'completed') $statusClass = 'status-completed';
                        elseif (strpos($submission['status'], 'pending') !== false) $statusClass = 'status-pending';
                        elseif ($submission['status'] === 'approved') $statusClass = 'status-approved';
                        elseif ($submission['status'] === 'rejected') $statusClass = 'status-rejected';
                        
                        $priorityBadge = '';
                        if (!empty($submission['priority_level'])) {
                            $pColor = '#6c757d';
                            if ($submission['priority_level'] === 'high') $pColor = '#dc3545';
                            elseif ($submission['priority_level'] === 'medium') $pColor = '#ffc107';
                            elseif ($submission['priority_level'] === 'low') $pColor = '#28a745';
                            $priorityBadge = '<span style="color:'.$pColor.'; font-weight:600;">'.ucfirst($submission['priority_level']).'</span>';
                        } else {
                            $priorityBadge = '<span style="color:#999;">-</span>';
                        }
                        ?>
                        <tr>
                            <td><?= esc($submission['id']) ?></td>
                            <td><?= esc($submission['form_name']) ?></td>
                            <td><?= esc($submission['submitted_by']) ?></td>
                            <td><?= esc($submission['department_name'] ?? 'Unassigned') ?></td>
                            <td style="text-align: center;"><?= $priorityBadge ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= ucfirst(str_replace('_', ' ', esc($submission['status']))) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($submission['created_at'])) ?></td>
                            <td><?= !empty($submission['completion_date']) ? date('M j, Y', strtotime($submission['completion_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #6c757d;">No submissions available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <div class="section">
        <div class="section-title">Recommendations & Action Items</div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
            <h4 style="color: #28a745; margin-bottom: 15px;">📋 Actionable Insights</h4>
            
            <ul style="list-style-type: none; padding: 0;">
                <?php if ($overview['completion_rate'] < 70): ?>
                    <li style="margin-bottom: 10px;">⚠️ <strong>Completion Rate:</strong> Consider reviewing approval processes to improve the <?= $overview['completion_rate'] ?>% completion rate.</li>
                <?php endif; ?>
                
                <?php if (!empty($formStats['form_usage']) && count($formStats['form_usage']) > 0): ?>
                    <li style="margin-bottom: 10px;">📊 <strong>Popular Forms:</strong> "<?= esc($formStats['form_usage'][0]['form_name']) ?>" is the most used form with <?= $formStats['form_usage'][0]['usage_count'] ?> submissions.</li>
                <?php endif; ?>
                
                <?php if (!empty($performanceMetrics['user_productivity'])): ?>
                    <li style="margin-bottom: 10px;">👤 <strong>Top Performer:</strong> <?= esc($performanceMetrics['user_productivity'][0]['full_name']) ?> leads with <?= $performanceMetrics['user_productivity'][0]['submissions'] ?> submissions this month.</li>
                <?php endif; ?>
                
                <li style="margin-bottom: 10px;">🔄 <strong>System Health:</strong> Regular monitoring of these metrics will help maintain optimal performance.</li>
                
                <li style="margin-bottom: 10px;">📈 <strong>Growth Opportunity:</strong> Consider expanding successful processes to underperforming departments.</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        SmartISO Analytics Report | Generated <?= date('Y-m-d H:i:s') ?> | Page <span class="pageNumber"></span>
    </div>
</body>
</html>
