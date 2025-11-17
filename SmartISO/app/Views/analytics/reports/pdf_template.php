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
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #ffb3ba 0%, #bae1ff 100%);
            color: #2c3e50;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 12px;
            opacity: 0.8;
            margin: 0;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
            background: #fafbfc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffb3ba;
        }
        
        .section-title {
            background: #f8f9fa;
            color: #2c3e50;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 600;
            border-left: 4px solid #bae1ff;
            margin: -15px -15px 15px -15px;
            border-radius: 8px 8px 0 0;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .metric-card {
            background: linear-gradient(135deg, #ffffba, #ffdfba);
            border: 1px solid #f0e68c;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .metric-label {
            color: #5a6c7d;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 500;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th,
        .table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e3f2fd;
        }
        
        .table th {
            background: linear-gradient(135deg, #bae1ff, #e0bbff);
            font-weight: 600;
            color: #2c3e50;
            font-size: 10px;
        }
        
        .table tr:nth-child(even) {
            background: #f8fcff;
        }
        
        .progress-bar {
            background: #f0f4f8;
            border-radius: 8px;
            height: 6px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #baffc9, #ffffba);
            height: 100%;
            border-radius: 8px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .two-column {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .two-column > div {
            flex: 1;
        }
        
        .chart-placeholder {
            background: linear-gradient(135deg, #f8fcff, #fff5f8);
            border: 2px dashed #e0bbff;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            color: #5a6c7d;
            margin: 10px 0;
            font-size: 10px;
        }
        
        .highlight {
            background: linear-gradient(135deg, #fff9e6, #fff3cd);
            border: 1px solid #ffffba;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 8px 0;
            font-size: 10px;
        }
        
        .text-success { color: #27a844; }
        .text-warning { color: #fd7e14; }
        .text-danger { color: #e74c3c; }
        .text-info { color: #5bc0de; }
        
        .metrics-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .metric-card {
            flex: 1;
            min-width: 120px;
        }
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SmartISO Analytics Report</h1>
        <p>Generated on <?= $generated_at ?> | Report Type: <?= ucfirst($report_type) ?></p>
        <p>Date Range: Last <?= $date_range ?> days</p>
    </div>

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
            <div style="margin: 8px 0; font-size: 10px; line-height: 1.4;">
                • <strong><?= number_format($overview['total_submissions']) ?></strong> total submissions processed with <strong><?= $overview['completion_rate'] ?>%</strong> completion rate<br>
                • <strong><?= $overview['recent_submissions'] ?></strong> new submissions in the past 30 days showing <?= $overview['recent_submissions'] > 50 ? 'high' : ($overview['recent_submissions'] > 20 ? 'moderate' : 'low') ?> activity<br>
                • <strong><?= $overview['total_users'] ?></strong> active users across <strong><?= $overview['total_departments'] ?></strong> departments<br>
                • Performance trend: <?= $overview['completion_rate'] > 80 ? '🟢 Excellent' : ($overview['completion_rate'] > 60 ? '🟡 Good' : '🔴 Needs Attention') ?>
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

    <div class="page-break"></div>

    <!-- Recent Submissions -->
    <div class="section">
        <div class="section-title">Recent Submissions (Last 100)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Form Name</th>
                    <th>Submitted By</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentSubmissions)): ?>
                    <?php foreach ($recentSubmissions as $submission): ?>
                        <tr>
                            <td><?= esc($submission['id']) ?></td>
                            <td><?= esc($submission['form_name']) ?></td>
                            <td><?= esc($submission['submitted_by']) ?></td>
                            <td><?= esc($submission['department_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <span class="<?= 
                                    $submission['status'] === 'completed' ? 'text-success' : 
                                    ($submission['status'] === 'pending_service' || $submission['status'] === 'approved' ? 'text-warning' : 
                                    ($submission['status'] === 'rejected' ? 'text-danger' : 'text-info')) 
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', esc($submission['status']))) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y H:i', strtotime($submission['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6c757d;">No submissions available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

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
