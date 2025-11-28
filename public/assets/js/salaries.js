// salaries.js
function initSalaries(){ try { initAdvancedTable('#salaries-table'); } catch(e){ console.error('initSalaries failed', e); } const c=document.querySelector('.dashboard-container'); if(c) c.classList.add('show'); }
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', initSalaries); else try{ initSalaries(); }catch(e){console.error(e);} 

function exportToExcel(){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=salaries&export=excel'; if(window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); },800); }
function refreshTable(){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),700); }
function printTable(){ const table=document.getElementById('salaries-table').cloneNode(true); const w=window.open('','_blank'); w.document.write(`<html><head><title>Salaries</title></head><body><h2>Salaries</h2>${table.outerHTML}</body></html>`); w.document.close(); w.print(); }
