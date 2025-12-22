// session_tracking.js
(function(){
    const apiBase = 'api/session_completion.php';
    let currentSessionId = null;
    let currentOccurrenceId = null;

    /**
     * Load upcoming sessions
     */
    async function loadUpcomingSessions() {
        try {
            const res = await CRUD.get(`${apiBase}?action=occurrences`);
            if (!res.success || !Array.isArray(res.data)) return;

            const container = document.getElementById('sessionsContainer');
            container.innerHTML = '';

            if (res.data.length === 0) {
                container.innerHTML = '<div class="col-12"><div class="alert alert-info">No upcoming sessions</div></div>';
                return;
            }

            res.data.forEach(occ => {
                const date = new Date(occ.session_date);
                const isToday = isDateToday(date);
                const isPast = isPastDate(date);
                const statusClass = isPast ? 'past' : isToday ? 'today' : '';
                
                const card = document.createElement('div');
                card.className = 'col-md-6 col-lg-4 mb-4';
                card.innerHTML = `
                    <div class="card session-card ${statusClass}">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">${formatDate(date)}</h6>
                            <h5 class="card-title">${escapeHtml(occ.batch_title || 'Session')}</h5>
                            <p class="session-time-badge mb-3">
                                ${occ.start_time} - ${occ.end_time}
                            </p>
                            <div class="session-meta mb-3">
                                <div><i class="fas fa-graduation-cap me-1"></i> Batch ID: ${occ.batch_id}</div>
                                <div><i class="fas fa-flag me-1"></i> Status: <span class="badge bg-info">${occ.latest_status || 'Pending'}</span></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="viewSessionDetails(${occ.occurrence_id})">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                ${occ.latest_status !== 'completed' ? `
                                    <button class="btn btn-sm btn-success flex-grow-1" onclick="openCompletionForm(${occ.occurrence_id})">
                                        <i class="fas fa-check me-1"></i>Complete
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        } catch(err) {
            console.error('Failed to load sessions', err);
        }
    }

    /**
     * Load completed sessions
     */
    async function loadCompletedSessions() {
        try {
            const res = await CRUD.get(`${apiBase}?action=list`);
            if (!res.success || !Array.isArray(res.data)) return;

            const completed = res.data.filter(s => s.status === 'completed');
            const tbody = document.getElementById('completedTableBody');
            tbody.innerHTML = '';

            document.getElementById('completedBadge').textContent = completed.length;

            if (completed.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No completed sessions</td></tr>';
                return;
            }

            completed.forEach(comp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${formatDate(new Date(comp.session_date))}</td>
                    <td><strong>${escapeHtml(comp.batch_title || '')}</strong></td>
                    <td><small>${comp.scheduled_start} - ${comp.scheduled_end}</small></td>
                    <td><small>${comp.actual_start_time || '-'} - ${comp.actual_end_time || '-'}</small></td>
                    <td><code class="bg-light p-1">${comp.completion_code || '-'}</code></td>
                    <td>${comp.note_count > 0 ? `<span class="badge bg-info">${comp.note_count} notes</span>` : '-'}</td>
                    <td>${comp.attachment_count > 0 ? `<span class="badge bg-warning">${comp.attachment_count} files</span>` : '-'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-info" onclick="viewSessionDetails(${comp.occurrence_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch(err) {
            console.error('Failed to load completed', err);
        }
    }

    /**
     * Load all sessions for history
     */
    async function loadHistory() {
        try {
            const res = await CRUD.get(`${apiBase}?action=list`);
            if (!res.success || !Array.isArray(res.data)) return;

            const container = document.getElementById('historyContainer');
            container.innerHTML = '';

            if (res.data.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No session history</div>';
                return;
            }

            res.data.forEach(comp => {
                const date = new Date(comp.session_date);
                const statusBadge = {
                    'completed': 'bg-success',
                    'cancelled': 'bg-danger',
                    'pending': 'bg-warning'
                }[comp.status] || 'bg-secondary';

                const div = document.createElement('div');
                div.className = 'card mb-3 border-left-5';
                div.innerHTML = `
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">${escapeHtml(comp.batch_title || 'Session')}</h6>
                            <small class="text-muted">${formatDate(date)} • ${comp.scheduled_start} - ${comp.scheduled_end}</small>
                        </div>
                        <span class="badge ${statusBadge}">${comp.status.toUpperCase()}</span>
                    </div>
                    <div class="card-body">
                        ${comp.completion_code ? `<p class="mb-2"><strong>Code:</strong> <code>${comp.completion_code}</code></p>` : ''}
                        ${comp.actual_start_time ? `<p class="mb-2"><strong>Actual Time:</strong> ${comp.actual_start_time} - ${comp.actual_end_time}</p>` : ''}
                        ${comp.notes ? `<p class="mb-2"><strong>Notes:</strong> ${escapeHtml(comp.notes)}</p>` : ''}
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewSessionDetails(${comp.id})">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        } catch(err) {
            console.error('Failed to load history', err);
        }
    }

    /**
     * View session detail modal
     */
    window.viewSessionDetails = async function(sessionId) {
        try {
            const res = await CRUD.get(`${apiBase}?action=get&id=${sessionId}`);
            if (!res.success) {
                CRUD.toastError && CRUD.toastError('Session not found');
                return;
            }

            const sess = res.data;
            currentSessionId = sessionId;
            currentOccurrenceId = sess.occurrence_id;

            const content = document.getElementById('sessionDetailContent');
            content.innerHTML = '';

            // Header info
            const header = document.createElement('div');
            header.className = 'mb-4 pb-3 border-bottom';
            header.innerHTML = `
                <h5>${escapeHtml(sess.batch_title || 'Session')}</h5>
                <p class="text-muted mb-2">${formatDate(new Date(sess.session_date))}</p>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-info">Scheduled: ${sess.scheduled_start} - ${sess.scheduled_end}</span>
                    ${sess.actual_start_time ? `<span class="badge bg-success">Actual: ${sess.actual_start_time} - ${sess.actual_end_time}</span>` : ''}
                    <span class="badge ${{'completed':'bg-success','cancelled':'bg-danger','pending':'bg-warning'}[sess.status] || 'bg-secondary'}">
                        ${sess.status.toUpperCase()}
                    </span>
                </div>
            `;
            content.appendChild(header);

            // Completion details
            if (sess.completion_code || sess.notes) {
                const details = document.createElement('div');
                details.className = 'mb-4 p-3 bg-light rounded';
                details.innerHTML = `
                    ${sess.completion_code ? `<p><strong>Completion Code:</strong> <code>${escapeHtml(sess.completion_code)}</code></p>` : ''}
                    ${sess.notes ? `<p><strong>Notes:</strong> ${escapeHtml(sess.notes)}</p>` : ''}
                `;
                content.appendChild(details);
            }

            // Attachments
            if (sess.attachments && sess.attachments.length > 0) {
                const attachDiv = document.createElement('div');
                attachDiv.className = 'mb-4';
                attachDiv.innerHTML = '<h6 class="mb-3"><i class="fas fa-paperclip me-1"></i>Attachments</h6>';
                
                const list = document.createElement('div');
                sess.attachments.forEach(att => {
                    const item = document.createElement('div');
                    item.className = 'mb-2 p-2 border rounded d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <div>
                            <span class="attachment-badge attachment-${att.file_type}">${att.file_type.toUpperCase()}</span>
                            <strong>${escapeHtml(att.file_name)}</strong>
                            ${att.description ? `<br><small class="text-muted">${escapeHtml(att.description)}</small>` : ''}
                        </div>
                        <a href="${att.file_path}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i>
                        </a>
                    `;
                    list.appendChild(item);
                });
                attachDiv.appendChild(list);
                content.appendChild(attachDiv);
            }

            // Notes/Comments section
            if (sess.notes && sess.notes.length > 0) {
                const notesDiv = document.createElement('div');
                notesDiv.className = 'mb-4';
                notesDiv.innerHTML = '<h6 class="mb-3"><i class="fas fa-comments me-1"></i>Comments</h6>';
                
                sess.notes.forEach(note => {
                    const noteItem = document.createElement('div');
                    noteItem.className = 'mb-2 p-2 border-left-3 border-left-info bg-light rounded';
                    noteItem.innerHTML = `
                        <p class="mb-1"><strong>${escapeHtml(note.author_name || 'Anonymous')}</strong> <small class="text-muted">${formatDateTime(new Date(note.created_at))}</small></p>
                        <p class="mb-0">${escapeHtml(note.note_text)}</p>
                        <small class="badge bg-secondary">${note.note_type}</small>
                    `;
                    notesDiv.appendChild(noteItem);
                });
                content.appendChild(notesDiv);
            }

            // Action buttons
            const markBtn = document.getElementById('markCompleteBtn');
            markBtn.style.display = sess.status === 'completed' ? 'none' : 'block';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('sessionDetailModal')).show();
        } catch(err) {
            console.error('Failed to load session detail', err);
        }
    };

    /**
     * Open completion form
     */
    window.openCompletionForm = function(occurrenceId) {
        currentOccurrenceId = occurrenceId;
        document.getElementById('completionSessionId').value = occurrenceId;
        document.getElementById('actualStartTime').value = '';
        document.getElementById('actualEndTime').value = '';
        document.getElementById('completionCode').value = '';
        document.getElementById('sessionNotes').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('completionModal')).show();
    };

    /**
     * Mark session as complete
     */
    window.markSessionComplete = function() {
        document.getElementById('completionModal').style.display = 'block';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('completionModal')).show();
    };

    /**
     * Submit completion
     */
    window.submitCompletion = async function() {
        const occurrenceId = document.getElementById('completionSessionId').value;
        const startTime = document.getElementById('actualStartTime').value;
        const endTime = document.getElementById('actualEndTime').value;
        const code = document.getElementById('completionCode').value;
        const notes = document.getElementById('sessionNotes').value;

        if (!occurrenceId || !startTime || !endTime) {
            CRUD.toastError && CRUD.toastError('Please fill start and end times');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', occurrenceId);
            formData.append('actual_start_time', startTime);
            formData.append('actual_end_time', endTime);
            formData.append('completion_code', code);
            formData.append('notes', notes);

            const res = await CRUD.post(`${apiBase}?action=complete`, formData);
            if (res.success) {
                CRUD.toastSuccess && CRUD.toastSuccess('Session marked completed');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('completionModal')).hide();
                loadUpcomingSessions();
                loadCompletedSessions();
            } else {
                CRUD.toastError && CRUD.toastError(res.message || 'Failed to complete');
            }
        } catch(err) {
            CRUD.toastError && CRUD.toastError('Error: ' + err.message);
        }
    };

    // Utility functions
    function formatDate(date) {
        return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatDateTime(date) {
        return date.toLocaleDateString('en-US', { 
            month: 'short', day: 'numeric', 
            hour: '2-digit', minute: '2-digit' 
        });
    }

    function isDateToday(date) {
        const today = new Date();
        return date.getDate() === today.getDate() &&
               date.getMonth() === today.getMonth() &&
               date.getFullYear() === today.getFullYear();
    }

    function isPastDate(date) {
        return date < new Date();
    }

    function escapeHtml(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(str || '').replace(/[&<>"']/g, m => map[m]);
    }

    function initSessionTracking() {
        const container = document.querySelector('.dashboard-container');
        if (container) container.classList.add('show');
        
        loadUpcomingSessions();
        loadCompletedSessions();
        loadHistory();

        // Refresh on tab change
        document.getElementById('sessions-tab')?.addEventListener('click', loadUpcomingSessions);
        document.getElementById('completed-tab')?.addEventListener('click', loadCompletedSessions);
        document.getElementById('history-tab')?.addEventListener('click', loadHistory);
    }

    window.initSessionTracking = initSessionTracking;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSessionTracking);
    } else {
        initSessionTracking();
    }
})();
