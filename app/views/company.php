<?php

use CampusLite\Controllers\CompanyController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/company.php
$company = CompanyController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($company);
$totalPages = 1;
// pick first company record for editing
$company_record = !empty($company) ? $company[0] : null;
?>

<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-building"></i> Company</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editCompanyModal"><i class="fas fa-edit"></i> Edit Company</button>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($company)): ?>
                <p>No company data configured. Click Edit to add.</p>
            <?php else: ?>
                <?php foreach ($company as $c): ?>
                    <h3><?= htmlspecialchars($c['name'] ?? 'Company') ?></h3>
                    <p><?= nl2br(htmlspecialchars($c['address'] ?? '')) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($c['phone'] ?? '') ?> | <strong>Email:</strong> <?= htmlspecialchars($c['email'] ?? '') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Company Modal -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="editCompanyForm">
                    <input type="hidden" name="id" id="companyId" value="<?= $company_record['id'] ?? '' ?>">
                    <div class="mb-3"><label class="form-label">Company Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($company_record['name'] ?? '') ?>" required></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3"><?= htmlspecialchars($company_record['address'] ?? '') ?></textarea></div>
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($company_record['phone'] ?? '') ?>"></div><div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($company_record['email'] ?? '') ?>"></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveCompany()">Save</button></div>
        </div>
    </div>
</div>

<script>
    async function saveCompany(){ const form=document.getElementById('editCompanyForm'); const params=new FormData(form); CRUD.showLoading(); try{ const res=await CRUD.post('api/company.php?action=save', params); if(res.success){ location.reload(); } else { alert('Save failed: '+(res.message||res.error||'Unknown')); } }catch(e){ alert('Request failed: '+e.message); } finally{ CRUD.hideLoading(); } }
    document.addEventListener('DOMContentLoaded', ()=>document.querySelector('.dashboard-container').classList.add('show'));
</script>
