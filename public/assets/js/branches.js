// branches.js
function initBranches(){ try { initAdvancedTable('#branches-table'); } catch(e){ console.error('initBranches failed', e); } const c=document.querySelector('.dashboard-container'); if(c) c.classList.add('show'); }
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', initBranches); else try{ initBranches(); }catch(e){console.error(e);} 

function exportToExcel(){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=branches&export=excel'; if(window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); },800); }
function refreshTable(){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),700); }
async function deleteBranch(id){ if(!confirm('Delete branch '+id+'?')) return; if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/branches.php?action=delete', p); if(res.success){ window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess('Deleted'); refreshTable(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed'); }catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed: '+e.message);} finally{ window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }
