<?php

use CampusLite\Controllers\CourseController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/course.php (single-course manager)
$courses = CourseController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($courses);
$totalPages = 1;
?>

<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-graduation-cap"></i> Courses</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus"></i> Add Course</button>
    </div>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive" id="tableContainer">
            <table class="table data-table" id="courses-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Title</th>
                        <th>Branch</th>
                        <th>Total Fee</th>
                        <th>Duration</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><h4>No courses found</h4><p>No courses match your search criteria</p><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus"></i> Add Course</button></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['title'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['branch_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['total_fee'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['duration_months'] ?? '') ?> months</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editCourse(<?= $c['id'] ?? 0 ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteCourse(<?= $c['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Course</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="addCourseForm">
                    <input type="hidden" name="id" id="courseId" value="">
                    <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                    <div class="mb-3"><label class="form-label">Branch</label><input class="form-control" name="branch_id"></div>
                    <div class="mb-3"><label class="form-label">Total Fee</label><input type="number" class="form-control" name="total_fee"></div>
                    <div class="mb-3"><label class="form-label">Duration (months)</label><input type="number" class="form-control" name="duration_months"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveCourse()">Save</button></div>
        </div>
    </div>
</div>

<script>
    let searchTimeout;
    (function(){ const si = document.getElementById('searchInput'); if(!si) return; si.addEventListener('input', function(e){ clearTimeout(searchTimeout); searchTimeout=setTimeout(()=>{ const v=e.target.value.toLowerCase(); document.querySelectorAll('#courses-table tbody tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(v)?'':'none'); },200);} ); })();
    document.addEventListener('DOMContentLoaded', ()=>document.querySelector('.dashboard-container').classList.add('show'));
    function exportToExcel(){ showLoading(); setTimeout(()=>{ window.location.href='?page=courses&export=excel'; hideLoading(); },800);} function printTable(){ const table=document.getElementById('courses-table').cloneNode(true); const w=window.open('','_blank'); w.document.write(`<html><head><title>Courses</title></head><body><h2>Courses</h2>${table.outerHTML}</body></html>`); w.document.close(); w.print(); }
    function refreshTable(){ showLoading(); setTimeout(()=>location.reload(),600);} function showLoading(){ const c=document.getElementById('tableContainer'); const o=document.createElement('div'); o.className='loading-overlay'; o.innerHTML='<div class="spinner-border text-primary spinner" role="status"><span class="visually-hidden">Loading...</span></div>'; c.style.position='relative'; c.appendChild(o);} function hideLoading(){ const o=document.querySelector('.loading-overlay'); if(o) o.remove(); }
    async function editCourse(id){ CRUD.showLoading('tableContainer'); try{ const res=await CRUD.get(`api/courses.php?action=get&id=${encodeURIComponent(id)}`); if(res.success && res.data){ const c=res.data; document.getElementById('courseId').value=c.id||''; document.querySelector('#addCourseForm [name="title"]').value=c.title||''; document.querySelector('#addCourseForm [name="branch_id"]').value=c.branch_id||''; document.querySelector('#addCourseForm [name="total_fee"]').value=c.total_fee||''; document.querySelector('#addCourseForm [name="duration_months"]').value=c.duration_months||''; const modalEl=document.getElementById('addCourseModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); } else alert('Not found'); }catch(e){ alert('Failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function deleteCourse(id){ if(!confirm('Delete course '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/courses.php?action=delete', p); if(res.success) refreshTable(); else alert('Delete failed'); }catch(e){ alert('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveCourse(){ const form=document.getElementById('addCourseForm'); const params=new FormData(form); if(!params.get('title')){ alert('Title required'); return;} CRUD.showLoading('tableContainer'); try{ const id=params.get('id'); const action = id ? 'update' : 'create'; const res = await CRUD.post('api/courses.php?action='+action, params); if(res.success){ const modalEl=document.getElementById('addCourseModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.hide(); refreshTable(); } else alert('Save failed'); }catch(e){ alert('Request failed: '+e.message);} finally{ CRUD.hideLoading(); } }
</script>
