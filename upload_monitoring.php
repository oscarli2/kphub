<?php
require_once 'page_security.php';

// Initialize page security and require admin access
PageSecurity::initPageSecurity();
PageSecurity::requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All System Logs - KP-HUB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .controls {
            padding: 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .tab-controls {
            padding: 20px 30px 0;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 20px;
        }

        .tab-button {
            padding: 12px 24px;
            background: #e2e8f0;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #4a5568;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
        }

        .tab-button.active {
            background: white;
            color: #4a90e2;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .tab-button:hover:not(.active) {
            background: #cbd5e0;
        }

        .page-badge {
            background: #e2e8f0;
            color: #4a5568;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-reviewed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-dismissed {
            background: #fee2e2;
            color: #991b1b;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .control-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .control-group input, .control-group select {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .control-group input:focus, .control-group select:focus {
            outline: none;
            border-color: #4a90e2;
        }

        .btn {
            padding: 10px 20px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #357abd;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6169;
        }

        .stats-grid {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .logs-container {
            padding: 0 20px 30px;
            overflow-x: auto;
        }

        .logs-table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .logs-table th {
            background: #f8fafc;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .logs-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .logs-table tr:hover {
            background: #f8fafc;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #4a5568;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .user-details {
            min-width: 0;
            flex: 1;
        }

        .user-name {
            font-weight: 500;
            font-size: 0.85rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-facility {
            font-size: 0.75rem;
            color: #6b7280;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 180px;
        }

        .file-icon {
            color: #4a90e2;
            flex-shrink: 0;
        }

        .file-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            font-size: 0.85rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .upload-type {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            display: inline-block;
        }

        .upload-type.post_attachment {
            background: #dbeafe;
            color: #1e40af;
        }

        .upload-type.profile_picture {
            background: #dcfce7;
            color: #16a34a;
        }

        .upload-type.folder_upload {
            background: #fef3c7;
            color: #d97706;
        }

        .upload-time {
            font-size: 0.8rem;
            color: #4a5568;
            white-space: nowrap;
        }

        .ip-address {
            font-size: 0.8rem;
            color: #6b7280;
            font-family: monospace;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                margin: 0 10px;
            }
            
            .controls {
                padding: 20px;
                gap: 15px;
            }
            
            .stats-grid {
                padding: 20px;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }
            
            .stat-card {
                padding: 15px;
                min-height: 100px;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8rem;
                margin-bottom: 8px;
            }
            
            .header p {
                font-size: 1rem;
            }
            
            .controls {
                padding: 15px;
                flex-direction: column;
                align-items: stretch;
            }
            
            .control-group {
                width: 100%;
            }
            
            .control-group input, .control-group select {
                width: 100%;
                box-sizing: border-box;
            }
            
            .stats-grid {
                padding: 15px;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 10px;
            }
            
            .stat-card {
                padding: 12px;
                min-height: 80px;
            }
            
            .stat-card h3 {
                font-size: 1.3rem;
                margin-bottom: 5px;
            }
            
            .stat-card p {
                font-size: 0.8rem;
            }
            
            .logs-container {
                padding: 0 10px 20px;
            }
            
            .logs-table {
                min-width: 600px;
                font-size: 0.8rem;
            }
            
            .logs-table th, .logs-table td {
                padding: 8px 6px;
            }
            
            .user-info {
                min-width: 120px;
            }
            
            .user-avatar {
                width: 28px;
                height: 28px;
                font-size: 0.7rem;
            }
            
            .file-info {
                min-width: 140px;
            }
            
            .pagination {
                padding: 15px 10px;
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .pagination button {
                padding: 6px 10px;
                font-size: 0.9rem;
            }
            
            .back-button {
                top: 10px;
                left: 10px;
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header p {
                font-size: 0.9rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .stat-card h3 {
                font-size: 1.1rem;
            }
            
            .logs-table {
                min-width: 500px;
            }
            
            .logs-table th, .logs-table td {
                padding: 6px 4px;
            }
            
            .user-info {
                min-width: 100px;
            }
            
            .file-info {
                min-width: 120px;
            }
            
            .user-name, .file-name {
                font-size: 0.8rem;
            }
            
            .user-facility, .file-meta {
                font-size: 0.7rem;
            }
            
            .upload-type {
                padding: 2px 6px;
                font-size: 0.7rem;
            }
            
            .upload-time, .ip-address {
                font-size: 0.75rem;
            }
        }

        /* Table responsiveness for very small screens */
        @media (max-width: 600px) {
            .logs-table-responsive {
                display: none;
            }
            
            .logs-cards {
                display: block;
            }
            
            .log-card {
                background: white;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 10px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border: 1px solid #e2e8f0;
            }
            
            .log-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }
            
            .log-card-body {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                font-size: 0.85rem;
            }
            
            .log-card-field {
                display: flex;
                flex-direction: column;
            }
            
            .log-card-label {
                font-weight: 600;
                color: #4a5568;
                font-size: 0.75rem;
                margin-bottom: 2px;
            }
            
            .log-card-value {
                color: #6b7280;
            }
        }

        /* Hide cards by default */
        .logs-cards {
            display: none;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button:hover {
            background: #f8fafc;
        }

        .pagination button.active {
            background: #4a90e2;
            color: white;
            border-color: #4a90e2;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .loading i {
            font-size: 2rem;
            margin-bottom: 10px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #d1d5db;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.9);
            color: #4a5568;
            padding: 10px 15px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .logs-table {
                font-size: 0.9rem;
            }
            
            .logs-table th, .logs-table td {
                padding: 10px 8px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> All System Logs</h1>
            <p>Track and analyze file upload activities across the platform</p>
        </div>

        <div class="stats-grid" id="statsGrid">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <p>Loading statistics...</p>
            </div>
        </div>

        <!-- Facility per-quarter chart controls -->
        <div style="padding: 20px 30px; background: #fff; border-top: 1px solid #e2e8f0;">
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div class="control-group">
                    <label for="quarterSelect">Quarter</label>
                    <select id="quarterSelect">
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="yearSelect">Year</label>
                    <select id="yearSelect"></select>
                </div>
                <div style="display:flex; align-items:flex-end; gap:10px;">
                    <button class="btn" id="refreshFacilityChart">Show Graph</button>
                </div>
            </div>
            <div style="margin-top:18px;">
                <canvas id="facilityQuarterChart" height="120"></canvas>
            </div>
        </div>

        <div class="tab-controls">
            <div class="tabs">
                <button class="tab-button active" onclick="switchTab('uploads')">
                    <i class="fas fa-upload"></i> Upload Logs
                </button>
                <button class="tab-button" onclick="switchTab('visits')">
                    <i class="fas fa-eye"></i> Public Visits
                </button>
                <button class="tab-button" onclick="switchTab('feedback')">
                    <i class="fas fa-comment-dots"></i> Feedback
                </button>
            </div>
        </div>

        <div class="controls" id="uploadControls">
            <div class="control-group">
                <label for="userFilter">Search User</label>
                <input type="text" id="userFilter" placeholder="Email address">
            </div>
            <div class="control-group">
                <label for="dateFilter">Time Period</label>
                <select id="dateFilter">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            <div class="control-group">
                <label>&nbsp;</label>
                <button class="btn" onclick="filterLogs()">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
            <div class="control-group">
                <label>&nbsp;</label>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>

        <div class="controls" id="visitControls" style="display: none;">
            <div class="control-group">
                <label for="visitDaysFilter">Time Period</label>
                <select id="visitDaysFilter">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
            <div class="control-group">
                <label>&nbsp;</label>
                <button class="btn" onclick="loadPublicVisits()">
                    <i class="fas fa-search"></i> Refresh
                </button>
            </div>
        </div>

        <div class="controls" id="feedbackControls" style="display: none;">
            <div class="control-group">
                <label for="feedbackStatusFilter">Status</label>
                <select id="feedbackStatusFilter">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="resolved">Resolved</option>
                    <option value="dismissed">Dismissed</option>
                </select>
            </div>
            <div class="control-group">
                <label for="feedbackCategoryFilter">Category</label>
                <select id="feedbackCategoryFilter">
                    <option value="">All Categories</option>
                    <option value="general">General</option>
                    <option value="bug">Bug Report</option>
                    <option value="feature">Feature Request</option>
                    <option value="usability">Usability</option>
                    <option value="performance">Performance</option>
                    <option value="content">Content</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="control-group">
                <label for="feedbackRatingFilter">Rating</label>
                <select id="feedbackRatingFilter">
                    <option value="">All Ratings</option>
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                </select>
            </div>
            <div class="control-group">
                <label>&nbsp;</label>
                <button class="btn" onclick="loadFeedback()">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </div>

        <div class="logs-container">
            <div id="logsTable">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading upload logs...</p>
                </div>
            </div>
            <div id="visitsTable" style="display: none;">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading public visits...</p>
                </div>
            </div>
            <div id="feedbackTable" style="display: none;">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading feedback...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        const logsPerPage = 25;

        // Facility per-quarter chart state
        let facilityChart = null;

        // Populate year select with recent years
        function populateYearSelect() {
            const yearSelect = document.getElementById('yearSelect');
            const now = new Date();
            const currentYear = now.getFullYear();
            for (let y = currentYear; y >= currentYear - 4; y--) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSelect.appendChild(opt);
            }
        }

        // Fetch uploads count per facility for given quarter and year
        async function loadFacilityQuarterData(quarter, year) {
            const canvas = document.getElementById('facilityQuarterChart');
            if (!canvas) return;

            // Show loading state on canvas parent
            const parent = canvas.parentElement;
            parent.style.opacity = '0.6';

            try {
                // Try dedicated endpoint first
                let url = `api_upload_logs.php?action=uploads_by_facility&quarter=${encodeURIComponent(quarter)}&year=${encodeURIComponent(year)}`;
                const res = await fetch(url);
                if (!res.ok) throw new Error('Endpoint not available');
                const data = await res.json();

                // Debug: log the response so we can see what's returned
                console.log('uploads_by_facility response:', data);

                // Expecting { success: true, data: [{ facility: 'LEYTE', uploads: 123 }, ...] }
                if (data && data.success && Array.isArray(data.data)) {
                    renderFacilityChart(data.data, quarter, year);
                } else {
                    // Fallback: attempt to use stats endpoint and approximate by facility if available
                    await fallbackFacilityChart(quarter, year);
                }
            } catch (err) {
                console.log('Facility quarter endpoint missing or failed, using fallback:', err.message);
                await fallbackFacilityChart(quarter, year);
            } finally {
                parent.style.opacity = '1';
            }
        }

        async function fallbackFacilityChart(quarter, year) {
            // Try to obtain per-facility breakdown from stats endpoint if it contains breakdown
            try {
                const res = await fetch('api_upload_logs.php?action=stats');
                if (!res.ok) throw new Error('Stats unavailable');
                const stats = await res.json();

                // If stats.top_by_facility exists, use it; else create placeholder
                let items = [];
                if (stats && stats.top_by_facility && Array.isArray(stats.top_by_facility)) {
                    items = stats.top_by_facility.map(s => ({ facility: s.facility, uploads: s.count }));
                } else if (stats && stats.top_uploaders && Array.isArray(stats.top_uploaders)) {
                    // fallback to top_uploaders grouped by facility where possible
                    const map = {};
                    stats.top_uploaders.forEach(u => { if (u.facility) map[u.facility] = (map[u.facility]||0) + (u.count||1); });
                    items = Object.keys(map).map(k => ({ facility: k, uploads: map[k] }));
                } else {
                    // final fallback: show message in chart
                    items = [{ facility: 'No data', uploads: 0 }];
                }

                console.log('fallback facility items:', items);
                renderFacilityChart(items, quarter, year);
            } catch (e) {
                console.error('Fallback facility data failed:', e);
                renderFacilityChart([{ facility: 'Unavailable', uploads: 0 }], quarter, year);
            }
        }

        function renderFacilityChart(items, quarter, year) {
            const labels = items.map(i => i.facility);
            const values = items.map(i => Number(i.uploads) || 0);

            const canvasElem = document.getElementById('facilityQuarterChart');
            const ctx = canvasElem.getContext('2d');

            // Ensure canvas area is large enough for bars
            if (canvasElem) {
                canvasElem.style.height = '320px';
                canvasElem.parentElement.style.minHeight = '320px';
            }

            const total = values.reduce((s, v) => s + v, 0);
            if (total === 0) {
                // Show explicit no-data chart to avoid confusing empty axes
                if (facilityChart) facilityChart.destroy();
                facilityChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['No data'],
                        datasets: [{ label: 'Uploads', data: [0], backgroundColor: ['#e5e7eb'], borderColor: ['#cbd5e1'], borderWidth: 1 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { title: { display: true, text: `No uploads for ${quarter} ${year}` }, legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
                return;
            }

            if (facilityChart) {
                facilityChart.data.labels = labels;
                facilityChart.data.datasets[0].data = values;
                facilityChart.options.plugins.title.text = `Uploads per Facility — ${quarter} ${year}`;
                // update colors as well
                const palette = getColorPalette(labels.length);
                facilityChart.data.datasets[0].backgroundColor = palette.map(c => hexToRgba(c, 0.85));
                facilityChart.data.datasets[0].borderColor = palette.map(c => c);
                facilityChart.update();
                return;
            }

            const palette = getColorPalette(labels.length);

            facilityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Uploads',
                        data: values,
                        backgroundColor: palette.map(c => hexToRgba(c, 0.85)),
                        borderColor: palette.map(c => c),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: { display: true, text: `Uploads per Facility — ${quarter} ${year}` },
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Return a palette of distinct hex colors (repeats if count > palette length)
        function getColorPalette(count) {
            const base = ['#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236', '#166a8f', '#00a950', '#58595b', '#8549ba'];
            const out = [];
            for (let i = 0; i < count; i++) out.push(base[i % base.length]);
            return out;
        }

        // Convert hex color (#rrggbb) to rgba string with given alpha
        function hexToRgba(hex, alpha) {
            if (!hex) return `rgba(54,162,235,${alpha})`;
            const h = hex.replace('#', '');
            const bigint = parseInt(h, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadLogs();

            // Setup year and quarter controls for facility chart
            populateYearSelect();
            // Default quarter based on current month
            const month = new Date().getMonth() + 1;
            const quarter = month <= 3 ? 'Q1' : month <= 6 ? 'Q2' : month <= 9 ? 'Q3' : 'Q4';
            document.getElementById('quarterSelect').value = quarter;
            document.getElementById('yearSelect').value = new Date().getFullYear();

            document.getElementById('refreshFacilityChart').addEventListener('click', () => {
                const q = document.getElementById('quarterSelect').value;
                const y = document.getElementById('yearSelect').value;
                loadFacilityQuarterData(q, y);
            });

            // Initial chart load
            setTimeout(() => {
                const q = document.getElementById('quarterSelect').value;
                const y = document.getElementById('yearSelect').value;
                loadFacilityQuarterData(q, y);
            }, 300);
        });

        async function loadStats() {
            try {
                // Load upload stats
                const uploadResponse = await fetch('api_upload_logs.php?action=stats');
                const uploadStats = await uploadResponse.json();
                
                // Load public visit stats
                let publicStats = { total_visits: 0, unique_visitors: 0 };
                try {
                    const publicResponse = await fetch('api_public_visits.php?action=stats&days=7');
                    const publicData = await publicResponse.json();
                    if (publicData.success) {
                        publicStats = publicData.data;
                    }
                } catch (error) {
                    console.log('Public visits not available:', error.message);
                }
                
                // Load feedback stats
                let feedbackStats = { stats: { total_feedback: 0, avg_rating: 0, pending_count: 0 } };
                try {
                    const feedbackResponse = await fetch('api_feedback.php?action=stats&days=30');
                    const feedbackData = await feedbackResponse.json();
                    if (feedbackData.success) {
                        feedbackStats = feedbackData.data;
                    }
                } catch (error) {
                    console.log('Feedback stats not available:', error.message);
                }
                
                document.getElementById('statsGrid').innerHTML = `
                    <div class="stat-card">
                        <h3>${uploadStats.today || 0}</h3>
                        <p>Uploads Today</p>
                    </div>
                    <div class="stat-card">
                        <h3>${uploadStats.week || 0}</h3>
                        <p>Uploads This Week</p>
                    </div>
                    <div class="stat-card">
                        <h3>${uploadStats.month || 0}</h3>
                        <p>Uploads This Month</p>
                    </div>
                    <div class="stat-card">
                        <h3>${uploadStats.top_uploaders?.length || 0}</h3>
                        <p>Active Users</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <h3>${publicStats.total_visits || 0}</h3>
                        <p>Public Visits (7 days)</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                        <h3>${publicStats.unique_visitors || 0}</h3>
                        <p>Unique Visitors (7 days)</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                        <h3>${feedbackStats.stats.total_feedback || 0}</h3>
                        <p>Total Feedback (30 days)</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #fd7e14, #e8590c);">
                        <h3>${feedbackStats.stats.avg_rating ? parseFloat(feedbackStats.stats.avg_rating).toFixed(1) : '0.0'}</h3>
                        <p>Average Rating</p>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadLogs(page = 1) {
            const logsTable = document.getElementById('logsTable');
            logsTable.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading upload logs...</p>
                </div>
            `;

            try {
                const userFilter = document.getElementById('userFilter').value;
                const dateFilter = document.getElementById('dateFilter').value;
                
                let url = `api_upload_logs.php?action=logs&page=${page}&limit=${logsPerPage}`;
                if (userFilter) url += `&user_filter=${encodeURIComponent(userFilter)}`;
                if (dateFilter) url += `&date_filter=${dateFilter}`;

                console.log('Loading logs from URL:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Response data:', data);

                if (data.logs && data.logs.length > 0) {
                    console.log('Rendering', data.logs.length, 'logs');
                    renderLogsTable(data.logs, data.pagination);
                } else {
                    console.log('No logs found, showing empty state');
                    logsTable.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Upload Logs Found</h3>
                            <p>No upload activities match your current filters.</p>
                            <p style="font-size: 0.9rem; color: #999;">Debug: ${JSON.stringify(data)}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading logs:', error);
                logsTable.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Logs</h3>
                        <p>Failed to load upload logs. Please try again.</p>
                        <p style="font-size: 0.9rem; color: #999;">Error: ${error.message}</p>
                    </div>
                `;
            }
        }

        function renderLogsTable(logs, pagination) {
            const isSmallScreen = window.innerWidth <= 600;
            
            if (isSmallScreen) {
                // Render as cards for small screens
                const cardsHTML = `
                    <div class="logs-cards">
                        ${logs.map(log => `
                            <div class="log-card">
                                <div class="log-card-header">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            ${log.email ? log.email.charAt(0).toUpperCase() : 'U'}
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name">${log.email || 'Unknown User'}</div>
                                            <div class="user-facility">${log.facility || 'No facility'}</div>
                                        </div>
                                    </div>
                                    <span class="upload-type ${log.upload_type}">
                                        ${formatUploadType(log.upload_type)}
                                    </span>
                                </div>
                                <div class="log-card-body">
                                    <div class="log-card-field">
                                        <div class="log-card-label">File</div>
                                        <div class="log-card-value">
                                            <i class="fas ${getFileIcon(log.file_type)}"></i>
                                            ${log.filename}
                                        </div>
                                    </div>
                                    <div class="log-card-field">
                                        <div class="log-card-label">Size</div>
                                        <div class="log-card-value">${formatFileSize(log.file_size)}</div>
                                    </div>
                                    <div class="log-card-field">
                                        <div class="log-card-label">Upload Time</div>
                                        <div class="log-card-value">${formatDateTime(log.upload_time)}</div>
                                    </div>
                                    <div class="log-card-field">
                                        <div class="log-card-label">IP Address</div>
                                        <div class="log-card-value">${log.ip_address}</div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    ${renderPagination(pagination)}
                `;
                
                document.getElementById('logsTable').innerHTML = cardsHTML;
            } else {
                // Render as table for larger screens
                const tableHTML = `
                    <div class="logs-table-responsive">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>File</th>
                                    <th>Type</th>
                                    <th>Upload Time</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${logs.map(log => `
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    ${log.email ? log.email.charAt(0).toUpperCase() : 'U'}
                                                </div>
                                                <div class="user-details">
                                                    <div class="user-name">${log.email || 'Unknown User'}</div>
                                                    <div class="user-facility">${log.facility || 'No facility'}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="file-info">
                                                <i class="fas ${getFileIcon(log.file_type)} file-icon"></i>
                                                <div class="file-details">
                                                    <div class="file-name">${log.filename}</div>
                                                    <div class="file-meta">${formatFileSize(log.file_size)}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="upload-type ${log.upload_type}">
                                                ${formatUploadType(log.upload_type)}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="upload-time">${formatDateTime(log.upload_time)}</div>
                                        </td>
                                        <td>
                                            <div class="ip-address">${log.ip_address}</div>
                                        </td>
                                        <td>
                                            <button class="btn" onclick="showLogDetails(${log.log_id})" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ${renderPagination(pagination)}
                `;

                document.getElementById('logsTable').innerHTML = tableHTML;
            }
        }

        function renderPagination(pagination) {
            if (pagination.total_pages <= 1) return '';

            let pages = [];
            const start = Math.max(1, pagination.current_page - 2);
            const end = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return `
                <div class="pagination">
                    ${pagination.current_page > 1 ? 
                        `<button onclick="loadLogs(${pagination.current_page - 1})">
                            <i class="fas fa-chevron-left"></i>
                        </button>` : ''
                    }
                    ${pages.map(page => `
                        <button onclick="loadLogs(${page})" ${page === pagination.current_page ? 'class="active"' : ''}>
                            ${page}
                        </button>
                    `).join('')}
                    ${pagination.current_page < pagination.total_pages ? 
                        `<button onclick="loadLogs(${pagination.current_page + 1})">
                            <i class="fas fa-chevron-right"></i>
                        </button>` : ''
                    }
                </div>
                <div style="text-align: center; margin-top: 10px; color: #6b7280; font-size: 0.9rem;">
                    Showing ${(pagination.current_page - 1) * pagination.per_page + 1} - 
                    ${Math.min(pagination.current_page * pagination.per_page, pagination.total_records)} 
                    of ${pagination.total_records} records
                </div>
            `;
        }

        function getFileIcon(fileType) {
            if (!fileType) return 'fa-file';
            const type = fileType.toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(type)) return 'fa-image';
            if (['mp4', 'avi', 'mov', 'webm'].includes(type)) return 'fa-video';
            if (['mp3', 'wav', 'ogg'].includes(type)) return 'fa-music';
            if (['pdf'].includes(type)) return 'fa-file-pdf';
            if (['doc', 'docx'].includes(type)) return 'fa-file-word';
            if (['xls', 'xlsx'].includes(type)) return 'fa-file-excel';
            if (['txt'].includes(type)) return 'fa-file-text';
            return 'fa-file';
        }

        function formatFileSize(bytes) {
            if (!bytes) return 'Unknown size';
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            if (bytes === 0) return '0 Bytes';
            const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
        }

        function formatUploadType(type) {
            const types = {
                'post_attachment': 'Post Attachment',
                'profile_picture': 'Profile Picture',
                'folder_upload': 'Folder Upload'
            };
            return types[type] || type;
        }

        function formatDateTime(dateTime) {
            return new Date(dateTime).toLocaleString();
        }

        function filterLogs() {
            currentPage = 1;
            loadLogs();
        }

        function clearFilters() {
            document.getElementById('userFilter').value = '';
            document.getElementById('dateFilter').value = '';
            currentPage = 1;
            loadLogs();
        }

        function showLogDetails(logId) {
            // Placeholder for log details modal
            alert('Log details functionality would be implemented here for log ID: ' + logId);
        }

        // Tab switching functionality
        let currentTab = 'uploads';

        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Hide all controls and tables
            document.getElementById('uploadControls').style.display = 'none';
            document.getElementById('visitControls').style.display = 'none';
            document.getElementById('feedbackControls').style.display = 'none';
            document.getElementById('logsTable').style.display = 'none';
            document.getElementById('visitsTable').style.display = 'none';
            document.getElementById('feedbackTable').style.display = 'none';
            
            if (tab === 'uploads') {
                document.getElementById('uploadControls').style.display = 'flex';
                document.getElementById('logsTable').style.display = 'block';
                loadLogs(1);
            } else if (tab === 'visits') {
                document.getElementById('visitControls').style.display = 'flex';
                document.getElementById('visitsTable').style.display = 'block';
                loadPublicVisits();
            } else if (tab === 'feedback') {
                document.getElementById('feedbackControls').style.display = 'flex';
                document.getElementById('feedbackTable').style.display = 'block';
                loadFeedback();
            }
        }

        // Public visits functionality
        async function loadPublicVisits() {
            const visitsTable = document.getElementById('visitsTable');
            const days = document.getElementById('visitDaysFilter').value;
            
            visitsTable.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading public visits...</p>
                </div>
            `;

            try {
                const response = await fetch(`api_public_visits.php?action=logs&days=${days}&limit=100`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    renderVisitsTable(data.data);
                } else {
                    visitsTable.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-eye-slash"></i>
                            <h3>No Public Visits Found</h3>
                            <p>No public visits recorded for the selected time period.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading public visits:', error);
                visitsTable.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Visits</h3>
                        <p>Failed to load public visit logs. Please try again.</p>
                    </div>
                `;
            }
        }

        function renderVisitsTable(visits) {
            const isSmallScreen = window.innerWidth <= 600;
            
            if (isSmallScreen) {
                // Render as cards for small screens
                const cardsHTML = `
                    <div class="logs-cards">
                        ${visits.map(visit => `
                            <div class="log-card">
                                <div class="log-card-header">
                                    <strong><i class="fas fa-eye"></i> Public Visit</strong>
                                    <span class="log-time">${new Date(visit.visit_time).toLocaleString()}</span>
                                </div>
                                <div class="log-card-body">
                                    <div><strong>Page:</strong> ${visit.page}</div>
                                    <div><strong>IP Address:</strong> ${visit.ip_address || 'Unknown'}</div>
                                    <div><strong>User Agent:</strong> ${(visit.user_agent || '').substring(0, 50)}${visit.user_agent && visit.user_agent.length > 50 ? '...' : ''}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                
                document.getElementById('visitsTable').innerHTML = cardsHTML;
            } else {
                // Render as table for larger screens
                const tableHTML = `
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-clock"></i> Time</th>
                                <th><i class="fas fa-file"></i> Page</th>
                                <th><i class="fas fa-globe"></i> IP Address</th>
                                <th><i class="fas fa-browser"></i> User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${visits.map(visit => `
                                <tr>
                                    <td>
                                        <div style="font-size: 0.9rem;">
                                            ${new Date(visit.visit_time).toLocaleDateString()}
                                        </div>
                                        <div style="font-size: 0.8rem; color: #6b7280;">
                                            ${new Date(visit.visit_time).toLocaleTimeString()}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="page-badge">${visit.page}</span>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.8rem; background: #f3f4f6; padding: 2px 4px; border-radius: 4px;">
                                            ${visit.ip_address || 'Unknown'}
                                        </code>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.8rem;">
                                            ${visit.user_agent || 'Unknown'}
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                
                document.getElementById('visitsTable').innerHTML = tableHTML;
            }
        }

        // Feedback management functionality
        async function loadFeedback() {
            const feedbackTable = document.getElementById('feedbackTable');
            const status = document.getElementById('feedbackStatusFilter').value;
            const category = document.getElementById('feedbackCategoryFilter').value;
            const rating = document.getElementById('feedbackRatingFilter').value;
            
            feedbackTable.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading feedback...</p>
                </div>
            `;

            try {
                let url = 'api_feedback.php?action=list&limit=100';
                if (status) url += `&status=${encodeURIComponent(status)}`;
                if (category) url += `&category=${encodeURIComponent(category)}`;
                if (rating) url += `&rating=${encodeURIComponent(rating)}`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    renderFeedbackTable(data.data);
                } else {
                    feedbackTable.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-comment-slash"></i>
                            <h3>No Feedback Found</h3>
                            <p>No feedback matches your current filters.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading feedback:', error);
                feedbackTable.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Feedback</h3>
                        <p>Failed to load feedback. Please try again.</p>
                    </div>
                `;
            }
        }

        function renderFeedbackTable(feedback) {
            const isSmallScreen = window.innerWidth <= 600;
            
            if (isSmallScreen) {
                // Render as cards for small screens
                const cardsHTML = `
                    <div class="logs-cards">
                        ${feedback.map(item => `
                            <div class="log-card">
                                <div class="log-card-header">
                                    <strong>
                                        <i class="fas fa-star" style="color: #fbbf24;"></i>
                                        ${item.rating}/5 - ${item.category}
                                    </strong>
                                    <span class="log-time">${new Date(item.created_at).toLocaleString()}</span>
                                </div>
                                <div class="log-card-body">
                                    <div><strong>Subject:</strong> ${item.subject}</div>
                                    <div><strong>From:</strong> ${item.user_email || item.name + ' (' + item.email + ')'}</div>
                                    <div><strong>Status:</strong> 
                                        <span class="status-badge status-${item.status}">${item.status}</span>
                                    </div>
                                    <div style="margin-top: 8px;"><strong>Message:</strong></div>
                                    <div style="font-style: italic; color: #6b7280; margin-top: 4px;">
                                        "${item.message.substring(0, 100)}${item.message.length > 100 ? '...' : ''}"
                                    </div>
                                    <button onclick="manageFeedback(${item.feedback_id}, '${item.status}')" 
                                            class="btn" style="margin-top: 8px; font-size: 0.8rem; padding: 6px 12px;">
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                
                document.getElementById('feedbackTable').innerHTML = cardsHTML;
            } else {
                // Render as table for larger screens
                const tableHTML = `
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-clock"></i> Date</th>
                                <th><i class="fas fa-star"></i> Rating</th>
                                <th><i class="fas fa-tag"></i> Category</th>
                                <th><i class="fas fa-user"></i> From</th>
                                <th><i class="fas fa-envelope"></i> Subject</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${feedback.map(item => `
                                <tr>
                                    <td>
                                        <div style="font-size: 0.9rem;">
                                            ${new Date(item.created_at).toLocaleDateString()}
                                        </div>
                                        <div style="font-size: 0.8rem; color: #6b7280;">
                                            ${new Date(item.created_at).toLocaleTimeString()}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="color: #fbbf24; font-weight: bold;">
                                            ${'★'.repeat(item.rating)}${'☆'.repeat(5-item.rating)}
                                        </div>
                                        <div style="font-size: 0.8rem; color: #6b7280;">
                                            ${item.rating}/5
                                        </div>
                                    </td>
                                    <td>
                                        <span class="page-badge">${item.category}</span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem; font-weight: 500;">
                                            ${item.user_email || item.name}
                                        </div>
                                        ${!item.user_email ? `<div style="font-size: 0.8rem; color: #6b7280;">${item.email}</div>` : ''}
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; margin-bottom: 4px;">${item.subject}</div>
                                        <div style="font-size: 0.8rem; color: #6b7280; font-style: italic;">
                                            "${item.message.substring(0, 60)}${item.message.length > 60 ? '...' : ''}"
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-${item.status}">${item.status}</span>
                                    </td>
                                    <td>
                                        <button onclick="manageFeedback(${item.feedback_id}, '${item.status}')" 
                                                class="btn" style="font-size: 0.8rem; padding: 6px 12px;">
                                            <i class="fas fa-edit"></i> Manage
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                
                document.getElementById('feedbackTable').innerHTML = tableHTML;
            }
        }

        function manageFeedback(feedbackId, currentStatus) {
            // Simple feedback management - in a real app, this would open a modal
            const newStatus = prompt(
                `Current status: ${currentStatus}\\n\\nEnter new status (pending/reviewed/resolved/dismissed):`,
                currentStatus
            );
            
            if (newStatus && ['pending', 'reviewed', 'resolved', 'dismissed'].includes(newStatus)) {
                updateFeedbackStatus(feedbackId, newStatus);
            }
        }

        async function updateFeedbackStatus(feedbackId, status, adminResponse = null) {
            try {
                const response = await fetch('api_feedback.php?action=update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        feedback_id: feedbackId,
                        status: status,
                        admin_response: adminResponse
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    loadFeedback(); // Reload feedback list
                    loadStats(); // Reload stats
                } else {
                    alert('Failed to update feedback: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating feedback:', error);
                alert('Network error updating feedback');
            }
        }

        // Handle window resize for responsive layout
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Re-render the current data with the new layout
                loadLogs(currentPage);
            }, 250);
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            loadStats();
            if (currentTab === 'uploads') {
                loadLogs(currentPage);
            } else if (currentTab === 'visits') {
                loadPublicVisits();
            } else if (currentTab === 'feedback') {
                loadFeedback();
            }
        }, 30000);
    </script>

<?php include 'feedback_widget.php'; ?>

</body>
</html>