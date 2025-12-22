<?php

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

$userId = intval($GLOBALS['user']['id'] ?? 0);
$userRole = $GLOBALS['user']['role'] ?? '';
$userName = $GLOBALS['user']['name'] ?? '';

// For admin, show all sessions. For faculty/employee, show only their sessions
$isAdmin = in_array($userRole, ['super_admin', 'branch_admin']);

?>

<div class="container-fluid dashboard-container fade-in">
    <?php include __DIR__ . '/partials/page-header.php'; ?>

    <!-- Debug Info (Remove after testing) -->
    <?php if (!$isAdmin): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>Info:</strong> Sessions from batches you're assigned to. Requires: 1) Schedule batch created 2) Faculty assigned to batch 3) Session date >= today
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4 sticky-top bg-white pt-2" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions-pane" 
                    type="button" role="tab" aria-controls="sessions-pane" aria-selected="true">
                <i class="fas fa-calendar-check me-2"></i>Upcoming Sessions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-pane" 
                    type="button" role="tab" aria-controls="completed-pane" aria-selected="false">
                <i class="fas fa-check-circle me-2"></i>Completed <span class="badge bg-success ms-1" id="completedBadge">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" 
                    type="button" role="tab" aria-controls="history-pane" aria-selected="false">
                <i class="fas fa-history me-2"></i>History
            </button>
        </li>
        <?php if ($isAdmin): ?>
        <li class="nav-item ms-auto" role="presentation">
            <small class="text-muted pt-3"><i class="fas fa-shield me-1"></i>Admin View - All Sessions</small>
        </li>
        <?php else: ?>
        <li class="nav-item ms-auto" role="presentation">
            <small class="text-muted pt-3"><i class="fas fa-user me-1"></i>Your Sessions</small>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Upcoming Sessions -->
        <div class="tab-pane fade show active" id="sessions-pane" role="tabpanel" aria-labelledby="sessions-tab">
            <div class="row" id="sessionsContainer">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Completed Sessions -->
        <div class="tab-pane fade" id="completed-pane" role="tabpanel" aria-labelledby="completed-tab">
            <div class="table-responsive">
                <table class="table table-hover" id="completedSessionsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Batch</th>
                            <th>Scheduled</th>
                            <th>Actual</th>
                            <th>Code</th>
                            <th>Notes</th>
                            <th>Files</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="completedTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- History -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel" aria-labelledby="history-tab">
            <div id="historyContainer" style="max-height: 600px; overflow-y: auto;">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Session Detail Modal -->
<div class="modal fade" id="sessionDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="sessionDetailTitle">Session Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sessionDetailContent"><!-- Populated by JS --></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="markCompleteBtn" style="display:none;" onclick="markSessionComplete()">
                    <i class="fas fa-check me-1"></i>Mark Completed
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Completion Form Modal -->
<div class="modal fade" id="completionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Complete Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="completionForm">
                    <input type="hidden" id="completionSessionId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Actual Start Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="actualStartTime" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Actual End Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="actualEndTime" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Completion Code</label>
                        <input type="text" class="form-control" id="completionCode" placeholder="e.g., SESSION-001">
                        <small class="text-muted">Unique identifier for this session</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="sessionNotes" rows="3" placeholder="Session notes, key topics covered, etc."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitCompletion()">Save & Complete</button>
            </div>
        </div>
    </div>
</div>

<style>
    .session-card {
        border-left: 5px solid #0d6efd;
        transition: all 0.3s ease;
    }
    .session-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .session-card.past { border-left-color: #6c757d; }
    .session-card.completed { border-left-color: #28a745; }
    .session-card.cancelled { border-left-color: #dc3545; }
    
    .session-time-badge {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0d6efd;
    }
    .session-meta {
        font-size: 0.9rem;
        color: #6c757d;
    }
    .attachment-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.8rem;
        margin-right: 0.5rem;
    }
    .attachment-pdf { background-color: #ffebee; color: #c62828; }
    .attachment-video { background-color: #e3f2fd; color: #01579b; }
    .attachment-audio { background-color: #f3e5f5; color: #4a148c; }
    .attachment-document { background-color: #fff3e0; color: #e65100; }
    .attachment-image { background-color: #f1f8e9; color: #33691e; }
</style>

<script src="/public/assets/js/session_tracking.js"></script>
