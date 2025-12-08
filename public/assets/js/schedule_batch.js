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
            const tbody = document.querySelector(tableSelector+' tbody');
            if (!tbody) return;
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
            try { initAdvancedTable(tableSelector); } catch(e){}
        } catch(err){ console.error('loadTable failed', err); }
    }

    function resetForm(){
        const form = document.getElementById(formId);
        if (form) form.reset();
        const idInput = document.getElementById('scheduleId'); if (idInput) idInput.value = '';
        const facultyInput = document.getElementById('scheduleFaculty'); if (facultyInput) facultyInput.value = '';
        const facultyIdInput = document.getElementById('scheduleFacultyId'); if (facultyIdInput) facultyIdInput.value = '';
        const subj = document.getElementById('scheduleSubjects'); if (subj) subj.innerHTML='';
        const stu = document.getElementById('scheduleStudents'); if (stu) stu.innerHTML='';
        const facList = document.getElementById('scheduleFacultyList'); if (facList) facList.innerHTML='';
        const empList = document.getElementById('scheduleEmployeesList'); if (empList) empList.innerHTML='';
        adjustRecurrenceUI(document.getElementById('scheduleRecurrence')?.value || 'daily');
    }

    async function fetchBatchMeta(batchId){
        if (!batchId) return {subjects:[], students:[], faculties:[], employees:[], primary_faculty:null};
        const res = await CRUD.get(`api/schedule_batch.php?action=batch_meta&batch_id=${encodeURIComponent(batchId)}`);
        return res.success ? res.data : {subjects:[], students:[], faculties:[], employees:[], primary_faculty:null};
    }

    function filterBatchesByBranch(branchId){
        const batchSel = document.getElementById('scheduleBatch');
        if (!batchSel) return;
        Array.from(batchSel.options).forEach(opt=>{
            if (!opt.value) { opt.hidden=false; return; }
            const b = opt.getAttribute('data-branch') || '';
            opt.hidden = !!branchId && b !== String(branchId);
        });
        if (branchId && batchSel.selectedOptions.length && batchSel.selectedOptions[0].hidden) {
            batchSel.value = '';
        }
    }

    async function onBatchChange(){
        const batchId = document.getElementById('scheduleBatch')?.value;
        const meta = await fetchBatchMeta(batchId);
        const subjSel = document.getElementById('scheduleSubjects');
        const stuSel = document.getElementById('scheduleStudents');
        if (subjSel) {
            subjSel.innerHTML = '';
            meta.subjects.forEach(s=>{
                const opt=document.createElement('option'); opt.value=s.id; opt.textContent=s.title; subjSel.appendChild(opt);
            });
        }
        if (stuSel) {
            stuSel.innerHTML = '';
            meta.students.forEach(s=>{ const opt=document.createElement('option'); opt.value=s.id; opt.textContent=s.name; stuSel.appendChild(opt); });
        }
        // Populate faculty + employees display lists
        const facList = document.getElementById('scheduleFacultyList');
        if (facList) {
            facList.innerHTML = '';
            meta.faculties?.forEach(f=>{ const opt=document.createElement('option'); opt.value=f.id; opt.textContent=f.name; facList.appendChild(opt); });
        }
        const empList = document.getElementById('scheduleEmployeesList');
        if (empList) {
            empList.innerHTML = '';
            meta.employees?.forEach(e=>{ const opt=document.createElement('option'); opt.value=e.id; opt.textContent=e.name; empList.appendChild(opt); });
        }
        const primary = meta.primary_faculty || meta.faculties?.[0] || null;
        const facName = primary?.name || '';
        const facId = primary?.id || '';
        const facInput = document.getElementById('scheduleFaculty'); if (facInput) facInput.value = facName;
        const facHidden = document.getElementById('scheduleFacultyId'); if (facHidden) facHidden.value = facId;
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
            const meta = await fetchBatchMeta(d.batch_id);
            await onBatchChange();
            // reapply selected options
            const subjSel = document.getElementById('scheduleSubjects');
            const stuSel = document.getElementById('scheduleStudents');
            const subjIds = safeJson(d.subject_ids);
            const stuIds = safeJson(d.student_ids);
            if (subjSel && subjIds) subjIds.forEach(id=>{ const opt=subjSel.querySelector(`option[value="${id}"]`); if(opt) opt.selected=true; });
            if (stuSel && stuIds) stuIds.forEach(id=>{ const opt=stuSel.querySelector(`option[value="${id}"]`); if(opt) opt.selected=true; });
            document.getElementById('scheduleFaculty')?.setAttribute('value', d.faculty_name || meta.faculty?.name || '');
            document.getElementById('scheduleFacultyId')?.setAttribute('value', d.faculty_id || meta.faculty?.id || '');
            const form = document.getElementById(formId);
            Array.from(form.elements).forEach(el=> el.disabled = !!viewOnly);
            const saveBtn = document.getElementById('saveScheduleBtn'); if (saveBtn) saveBtn.style.display = viewOnly ? 'none' : '';
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
        const subjSel = document.getElementById('scheduleSubjects');
        const stuSel = document.getElementById('scheduleStudents');
        if (subjSel) {
            formData.delete('subject_ids[]');
            Array.from(subjSel.selectedOptions).forEach(opt=>formData.append('subject_ids[]', opt.value));
        }
        if (stuSel) {
            formData.delete('student_ids[]');
            Array.from(stuSel.selectedOptions).forEach(opt=>formData.append('student_ids[]', opt.value));
        }
        const id = formData.get('id');
        const action = id ? 'update' : 'create';
        const res = await fetch('api/schedule_batch.php?action='+action, { method:'POST', body: formData });
        const data = await res.json();
        if (data.success){
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).hide();
            CRUD.toastSuccess && CRUD.toastSuccess(data.message || 'Saved');
            loadTable();
        } else {
            CRUD.toastError && CRUD.toastError(data.message || 'Save failed');
        }
    }

    window.deleteSchedule = async function(id){
        if (!confirm('Delete schedule '+id+'?')) return;
        const params = new URLSearchParams(); params.append('id', id);
        const res = await CRUD.post('api/schedule_batch.php?action=delete', params);
        if (res.success){ CRUD.toastSuccess && CRUD.toastSuccess('Deleted'); loadTable(); }
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
        if (batchSel) batchSel.addEventListener('change', onBatchChange);
        const branchSel = document.getElementById('scheduleBranch');
        if (branchSel) branchSel.addEventListener('change', e=>{
            filterBatchesByBranch(e.target.value);
            // reset batch dependent fields
            const batchSelInner = document.getElementById('scheduleBatch');
            if (batchSelInner && batchSelInner.value) { batchSelInner.value=''; }
            onBatchChange();
        });
        // apply initial filter if branch preselected
        if (branchSel && branchSel.value) { filterBatchesByBranch(branchSel.value); }
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
