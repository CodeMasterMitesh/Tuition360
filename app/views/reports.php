<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/reports.php

?>
<div class="container dashboard-container">
    <div class="dashboard-header">
        <h2 class="dashboard-title">Reports</h2>
        <div class="dashboard-controls">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="reportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    All Reports
                </button>
                <ul class="dropdown-menu" aria-labelledby="reportDropdown">
                    <li><a class="dropdown-item" href="../api/fees.php?action=outstanding&branch_id=1" target="_blank">Outstanding Fees Report</a></li>
                    <li><a class="dropdown-item" href="../api/attendance.php?action=report&branch_id=1" target="_blank">Attendance Report</a></li>
                    <li><a class="dropdown-item" href="../api/salary.php?action=report&branch_id=1" target="_blank">Salary Report</a></li>
                    <!-- Add more report links as needed -->
                </ul>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <ul>
                <li><a href="../api/fees.php?action=outstanding&branch_id=1" target="_blank">Outstanding Fees Report</a></li>
                <li><a href="../api/attendance.php?action=report&branch_id=1" target="_blank">Attendance Report</a></li>
                <li><a href="../api/salary.php?action=report&branch_id=1" target="_blank">Salary Report</a></li>
                <!-- Add more report links as needed -->
            </ul>
        </div>
    </div>
</div>

