<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

$pageKey = $pageKey ?? ($page ?? 'dashboard');
$pageTitle = trim($pageTitle ?? 'CampusLite ERP');
$fullTitle = $pageTitle === 'CampusLite ERP' ? $pageTitle : $pageTitle . ' • CampusLite ERP';
$pagesConfig = $pagesConfig ?? [];
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$csrfToken = $_SESSION['csrf_token'] ?? '';
$bodyClass = 'app-page-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $pageKey);
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($fullTitle) ?></title>
    <link rel="icon" type="image/png" href="/public/assets/images/favicon.png">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="/public/assets/css/style.css">
    <script>
        window.__csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
    </script>
</head>
<body class="h-100 <?= htmlspecialchars($bodyClass) ?>">
    <div class="app-shell d-flex flex-column min-vh-100">
        <?php include __DIR__ . '/../partials/nav.php'; ?>
        <main id="app-main" class="flex-grow-1 container-fluid pb-4" data-page="<?= htmlspecialchars($pageKey) ?>">
            <?= $contentForLayout ?? '' ?>
        </main>
        <?php include __DIR__ . '/../partials/footer.php'; ?>
        <?php include __DIR__ . '/../partials/modals.php'; ?>
    </div>

    <!-- Shared media preview modal for photos/docs -->
    <div class="modal fade" id="mediaPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaPreviewTitle">Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="mediaPreviewBody" class="d-flex justify-content-center align-items-center" style="min-height:320px;">
                        <div class="text-muted" id="mediaPreviewPlaceholder">Select a file to preview</div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <small class="text-muted" id="mediaPreviewMeta"></small>
                    <div class="d-flex gap-2">
                        <a id="mediaPreviewOpenOriginal" class="btn btn-outline-primary" href="#" target="_blank" rel="noopener">Open original</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="/public/assets/js/export-print.js"></script>
    <script src="/public/assets/js/crud-helpers.js"></script>
    <script src="/public/assets/js/batch-actions.js"></script>
    <script src="/public/assets/js/common.js"></script>
    <script src="/public/assets/js/nav-ajax.js"></script>
    <script src="/public/assets/js/page-init.js"></script>
</body>
</html>
