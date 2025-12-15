// schedule_batch.js
(function(){
    const tableSelector = '#schedule-table';
    const modalId = 'addScheduleModal';
    const formId = 'addScheduleForm';
    const recurrenceSelect = document.getElementById('scheduleRecurrence');

    function adjustRecurrenceUI(value){
        document.querySelectorAll('.recurrence').forEach(el=>{
            el.style.display = el.classList.contains('recurrence-'+value) ? '' : (el.classList.contains('recurrence-daily') && value==='daily') ? '' : 'none';
        });
        if (value === 'daily') {
            copyTime('scheduleStartTime','scheduleStartTimeWeekly','scheduleStartTimeMonthly');
            copyTime('scheduleEndTime','scheduleEndTimeWeekly','scheduleEndTimeMonthly');
        }
    }
    function copyTime(fromId, ...toIds){
        const val = document.getElementById(fromId)?.value || '';
        toIds.forEach(id=>{ const el=document.getElementById(id); if(el && !el.value) el.value=val; });
    }

    function normalizeTime(val){
        if (!val) return '';
        const m = String(val).match(/(\d{1,2}):(\d{2})/);
        if (!m) return '';
        const hh = m[1].padStart(2,'0');
        const mm = m[2].padStart(2,'0');
        return `${hh}:${mm}`;
    }

    function normalizeDate(val){
        if (!val) return '';
        return String(val).slice(0,10);
    }

    async function loadTable(){
        try {
            const res = await CRUD.get('api/schedule_batch.php?action=list');
            if (!res.success) return;
            const table = document.querySelector(tableSelector);
            const tbody = table ? table.querySelector('tbody') : null;
            if (!tbody) return;
            
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable && $.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }
            
            tbody.innerHTML = '';
            res.data.forEach(row => {
                const tr = document.createElement('tr');
                const subjCount = row.subject_ids ? (JSON.parse(row.subject_ids||'[]')||[]).length : 0;
                const stuCount = row.student_ids ? (JSON.parse(row.student_ids||'[]')||[]).length : 0;
                const recurrenceLabel = row.recurrence.charAt(0).toUpperCase()+row.recurrence.slice(1);
                const windowLabel = [row.start_date||'', row.end_date?(' to '+row.end_date):''].join('');
                let timing = '';
                if (row.recurrence === 'weekly') timing = `${weekdayLabel(row.day_of_week)} ${row.start_time||''}`;
                else if (row.recurrence === 'monthly') timing = `Day ${row.day_of_month||''} ${row.start_time||''}`;
                else timing = `${row.start_time||''}${row.end_time?(' - '+row.end_time):''}`;
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td>${escapeHtml(row.batch_title||'')}</td>
                    <td>${recurrenceLabel}</td>
                    <td>${windowLabel || '-'}</td>
                    <td>${timing || '-'}</td>
                    <td>${escapeHtml(row.faculty_name||'')}</td>
                    <td>${subjCount}</td>
                    <td>${stuCount}</td>
                    <td>${row.status==='inactive'?'Inactive':'Active'}</td>
                    <td class="text-center">
                        <div class="table-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="editSchedule(${row.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-info" onclick="viewSchedule(${row.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSchedule(${row.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>`;
                tbody.appendChild(tr);
            });
            
            // Reinitialize DataTable
            try { 
                if (typeof initAdvancedTable === 'function') {
                    initAdvancedTable(tableSelector); 
                }
            } catch(e){ 
                console.error('DataTable init error:', e); 
            }
        } catch(err){ console.error('loadTable failed', err); }
    }

    function resetForm(){
        const form = document.getElementById(formId);
        if (form) form.reset();
        const idInput = document.getElementById('scheduleId'); if (idInput) idInput.value = '';
        
        // Clear dynamic containers
        const facultyContainer = document.getElementById('facultyListContainer');
        if (facultyContainer) facultyContainer.innerHTML = '';
        
        const studentsContainer = document.getElementById('studentsListContainer');
        if (studentsContainer) studentsContainer.innerHTML = '';
        document.getElementById('studentsTableWrapper').style.display = 'none';
        
        const subjectsContainer = document.getElementById('subjectsListContainer');
        if (subjectsContainer) subjectsContainer.innerHTML = '';
        document.getElementById('subjectsTableWrapper').style.display = 'none';
        
        // Reset batch dropdown
        const batchSel = document.getElementById('scheduleBatch');
        if (batchSel) {
            batchSel.innerHTML = '<option value="">-- Select Branch First --</option>';
        }
        
        adjustRecurrenceUI(document.getElementById('scheduleRecurrence')?.value || 'daily');
    }

    async function fetchBatchMeta(batchId){
        if (!batchId) return {subjects:[], students:[], faculties:[], employees:[], primary_faculty:null};
        const res = await CRUD.get(`api/schedule_batch.php?action=batch_meta&batch_id=${encodeURIComponent(batchId)}`);
        return res.success ? res.data : {subjects:[], students:[], faculties:[], employees:[], primary_faculty:null};
    }

    async function loadBatchesForBranch(branchId){
        const batchSel = document.getElementById('scheduleBatch');
        if (!batchSel) return;
        
        batchSel.innerHTML = '<option value="">-- Loading... --</option>';
        
        if (!branchId) {
            batchSel.innerHTML = '<option value="">-- Select Branch First --</option>';
            return;
        }
        
        try {
            const res = await CRUD.get(`api/batches.php?action=list&branch_id=${encodeURIComponent(branchId)}`);
            batchSel.innerHTML = '<option value="">-- Select Batch --</option>';
            
            if (res.success && Array.isArray(res.data)) {
                res.data.forEach(batch => {
                    const opt = document.createElement('option');
                    opt.value = batch.id;
                    opt.textContent = batch.title || batch.name || `Batch #${batch.id}`;
                    opt.setAttribute('data-course', batch.course_id || 0);
                    batchSel.appendChild(opt);
                });
            }
        } catch(e) {
            console.error('Failed to load batches', e);
            batchSel.innerHTML = '<option value="">-- Error loading batches --</option>';
        }
    }

    async function loadBatchDetails(batchId){
        if (!batchId) {
            // Clear everything
            document.getElementById('facultyListContainer').innerHTML = '';
            document.getElementById('studentsListContainer').innerHTML = '';
            document.getElementById('subjectsListContainer').innerHTML = '';
            document.getElementById('studentsTableWrapper').style.display = 'none';
            document.getElementById('subjectsTableWrapper').style.display = 'none';
            return;
        }
        
        const meta = await fetchBatchMeta(batchId);
        
        // Display faculty
        const facultyContainer = document.getElementById('facultyListContainer');
        facultyContainer.innerHTML = '';
        if (meta.faculties && meta.faculties.length > 0) {
            meta.faculties.forEach(fac => {
                const div = document.createElement('div');
                div.className = 'form-check';
                div.innerHTML = `
                    <input class="form-check-input" type="checkbox" name="faculty_ids[]" value="${fac.id}" id="fac_${fac.id}">
                    <label class="form-check-label" for="fac_${fac.id}">${escapeHtml(fac.name || '')}</label>
                `;
                facultyContainer.appendChild(div);
            });
        } else {
            facultyContainer.innerHTML = '<small class="text-muted">No faculty assigned to this batch</small>';
        }
        
        // Display students with checkboxes
        const studentsContainer = document.getElementById('studentsListContainer');
        studentsContainer.innerHTML = '';
        if (meta.students && meta.students.length > 0) {
            meta.students.forEach(stu => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center"><input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="${stu.id}" id="stu_${stu.id}"></td>
                    <td><label for="stu_${stu.id}">${escapeHtml(stu.name || stu.email || 'Student #' + stu.id)}</label></td>
                `;
                studentsContainer.appendChild(tr);
            });
            document.getElementById('studentsTableWrapper').style.display = '';
        } else {
            studentsContainer.innerHTML = '<tr><td colspan="2" class="text-center"><small class="text-muted">No students assigned</small></td></tr>';
            document.getElementById('studentsTableWrapper').style.display = '';
        }
        
        // Display subjects with checkboxes
        const subjectsContainer = document.getElementById('subjectsListContainer');
        subjectsContainer.innerHTML = '';
        if (meta.subjects && meta.subjects.length > 0) {
            meta.subjects.forEach(subj => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center"><input class="form-check-input subject-checkbox" type="checkbox" name="subject_ids[]" value="${subj.id}" id="subj_${subj.id}"></td>
                    <td><label for="subj_${subj.id}">${escapeHtml(subj.title || subj.code || 'Subject #' + subj.id)}</label></td>
                `;
                subjectsContainer.appendChild(tr);
            });
            document.getElementById('subjectsTableWrapper').style.display = '';
        } else {
            subjectsContainer.innerHTML = '<tr><td colspan="2" class="text-center"><small class="text-muted">No subjects found for this course</small></td></tr>';
            document.getElementById('subjectsTableWrapper').style.display = '';
        }
    }

    window.viewSchedule = async function(id){
        await openSchedule(id, true);
    }
    window.editSchedule = async function(id){
        await openSchedule(id, false);
    }

    async function openSchedule(id, viewOnly){
        try{
            const res = await CRUD.get(`api/schedule_batch.php?action=get&id=${encodeURIComponent(id)}`);
            if(!res.success || !res.data){ CRUD.toastError && CRUD.toastError('Schedule not found'); return; }
            const d = res.data;
            resetForm();
            const setVal=(sel,val)=>{ const el=document.querySelector(sel); if(el){ el.value = val ?? ''; } };
            setVal('#scheduleId', d.id);
            setVal('#scheduleBranch', d.branch_id);
            
            // Load batches for the selected branch, then set the batch value
            await loadBatchesForBranch(d.branch_id);
            setVal('#scheduleBatch', d.batch_id);
            
            setVal('#scheduleRecurrence', d.recurrence);
            setVal('#scheduleStartDate', normalizeDate(d.start_date));
            setVal('#scheduleEndDate', normalizeDate(d.end_date));
            const ntStart = normalizeTime(d.start_time);
            const ntEnd = normalizeTime(d.end_time);
            setVal('#scheduleStartTime', ntStart);
            setVal('#scheduleEndTime', ntEnd);
            setVal('#scheduleStartTimeWeekly', ntStart);
            setVal('#scheduleEndTimeWeekly', ntEnd);
            setVal('#scheduleStartTimeMonthly', ntStart);
            setVal('#scheduleEndTimeMonthly', ntEnd);
            setVal('#scheduleDayOfWeek', d.day_of_week);
            setVal('#scheduleDayOfMonth', d.day_of_month);
            setVal('#scheduleNotes', d.notes);
            setVal('#scheduleStatus', d.status);
            adjustRecurrenceUI(d.recurrence || 'daily');
            
            // Load batch details (faculty, students, subjects)
            await loadBatchDetails(d.batch_id);
            
            // Mark selected students and subjects as checked
            const subjIds = safeJson(d.subject_ids);
            const stuIds = safeJson(d.student_ids);
            
            if (subjIds && subjIds.length > 0) {
                subjIds.forEach(id => {
                    const checkbox = document.getElementById(`subj_${id}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            if (stuIds && stuIds.length > 0) {
                stuIds.forEach(id => {
                    const checkbox = document.getElementById(`stu_${id}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            // Mark selected faculty as checked
            if (d.faculty_id) {
                const facCheckbox = document.getElementById(`fac_${d.faculty_id}`);
                if (facCheckbox) facCheckbox.checked = true;
            }
            
            const form = document.getElementById(formId);
            Array.from(form.elements).forEach(el=> el.disabled = !!viewOnly);
            const saveBtn = document.getElementById('saveScheduleBtn');
            if (saveBtn) {
                saveBtn.style.display = viewOnly ? 'none' : '';
                if (!viewOnly) saveBtn.textContent = 'Update Schedule';
            }
            const title = document.getElementById('scheduleModalTitle'); if (title) title.textContent = viewOnly ? 'View Schedule' : 'Edit Schedule';
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
        }catch(err){ CRUD.toastError && CRUD.toastError('Failed: '+err.message); }
    }

    window.saveSchedule = async function(){
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        
        // normalize time fields based on recurrence
        const recurrence = formData.get('recurrence') || 'daily';
        if (recurrence === 'weekly') {
            formData.set('start_time', formData.get('start_time_weekly'));
            formData.set('end_time', formData.get('end_time_weekly'));
        } else if (recurrence === 'monthly') {
            formData.set('start_time', formData.get('start_time_monthly'));
            formData.set('end_time', formData.get('end_time_monthly'));
        }
        formData.delete('start_time_weekly'); formData.delete('end_time_weekly');
        formData.delete('start_time_monthly'); formData.delete('end_time_monthly');
        
        // Collect selected students from checkboxes
        formData.delete('student_ids[]');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox:checked');
        studentCheckboxes.forEach(cb => formData.append('student_ids[]', cb.value));
        
        // Collect selected subjects from checkboxes
        formData.delete('subject_ids[]');
        const subjectCheckboxes = document.querySelectorAll('.subject-checkbox:checked');
        subjectCheckboxes.forEach(cb => formData.append('subject_ids[]', cb.value));
        
        // Collect selected faculty from checkboxes
        formData.delete('faculty_ids[]');
        const facultyCheckboxes = document.querySelectorAll('input[name="faculty_ids[]"]:checked');
        facultyCheckboxes.forEach(cb => formData.append('faculty_ids[]', cb.value));
        
        const id = formData.get('id');
        const action = id ? 'update' : 'create';
        // Attach CSRF token
        const csrfToken = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : (window.__csrfToken || null);
        if (csrfToken && !formData.get('csrf_token')) formData.set('csrf_token', csrfToken);

        const res = await fetch('api/schedule_batch.php?action='+action, {
            method:'POST',
            credentials:'same-origin',
            headers: Object.assign({'X-Requested-With':'XMLHttpRequest'}, csrfToken ? {'X-CSRF-Token': csrfToken} : {}),
            body: formData
        });
        const data = await res.json();
        if (data.success){
            CRUD.toastSuccess && CRUD.toastSuccess(data.message || 'Saved');
            await loadTable();
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).hide();
        } else {
            CRUD.toastError && CRUD.toastError(data.message || 'Save failed');
        }
    }

    window.deleteSchedule = async function(id){
        if (!confirm('Delete schedule '+id+'?')) return;
        const params = new URLSearchParams(); params.append('id', id);
        const res = await CRUD.post('api/schedule_batch.php?action=delete', params);
        if (res.success){ 
            CRUD.toastSuccess && CRUD.toastSuccess('Deleted'); 
            await loadTable(); 
        }
        else { CRUD.toastError && CRUD.toastError(res.message || 'Delete failed'); }
    }

    function safeJson(str){ try { const v = JSON.parse(str||'[]'); return Array.isArray(v)?v:[]; } catch(e){ return []; } }
    function weekdayLabel(n){ const map=['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; if(n===null||n===undefined||n==='') return ''; n=parseInt(n,10); return map[n]||''; }
    function escapeHtml(str){ return String(str||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]||m)); }

    function initScheduleBatch(){
        const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');
        loadTable();
        if (recurrenceSelect) {
            adjustRecurrenceUI(recurrenceSelect.value);
            recurrenceSelect.addEventListener('change', e=>adjustRecurrenceUI(e.target.value));
        }
        
        const batchSel = document.getElementById('scheduleBatch');
        const branchSel = document.getElementById('scheduleBranch');
        
        // Branch change event - load batches for selected branch
        if (branchSel) branchSel.addEventListener('change', async (e) => {
            const branchId = e.target.value;
            await loadBatchesForBranch(branchId);
            // Clear batch details when branch changes
            loadBatchDetails(null);
        });
        
        // Batch change event - load faculty, students, subjects
        if (batchSel) batchSel.addEventListener('change', async (e) => {
            const batchId = e.target.value;
            await loadBatchDetails(batchId);
        });
        
        // Select All Students checkbox
        const selectAllStudents = document.getElementById('selectAllStudents');
        if (selectAllStudents) {
            selectAllStudents.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.student-checkbox');
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            });
        }
        
        // Select All Subjects checkbox
        const selectAllSubjects = document.getElementById('selectAllSubjects');
        if (selectAllSubjects) {
            selectAllSubjects.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.subject-checkbox');
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            });
        }
        
        const addBtn = document.querySelector('[data-modal-target="addScheduleModal"], [data-bs-target="#addScheduleModal"]');
        if (addBtn) addBtn.addEventListener('click', ()=>{
            resetForm();
            const form = document.getElementById(formId); if (form) Array.from(form.elements).forEach(el=>el.disabled=false);
            const saveBtn=document.getElementById('saveScheduleBtn'); if (saveBtn) { saveBtn.style.display=''; saveBtn.textContent='Save Schedule'; }
            const title=document.getElementById('scheduleModalTitle'); if (title) title.textContent='Add Schedule';
        });
    }

    window.initScheduleBatch = initScheduleBatch;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initScheduleBatch); else initScheduleBatch();
})();
