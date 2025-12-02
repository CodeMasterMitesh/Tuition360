<?php
// Usage expectations before including this partial:
// set $page_icon (e.g. 'fas fa-book'), $page_title (string),
// optional: $show_actions (bool), $action_buttons (array of ['label'=>..., 'class'=>..., 'onclick'=>...])
// optional: $add_button (array with 'label' and 'onclick')
$icon = $page_icon ?? 'fas fa-circle';
$title = $page_title ?? 'Page';
$show_actions = $show_actions ?? true;
$action_buttons = $action_buttons ?? [];
$add_button = $add_button ?? null;
?>
<div class="breadcrumb-container gap-2 gap-md-0">
    <nav aria-label="breadcrumb" class="flex-grow-1">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> <span class="d-none d-sm-inline">Dashboard</span></a></li>
            <li class="breadcrumb-item active" aria-current="page"><i class="<?= htmlspecialchars($icon) ?>"></i> <span class="d-none d-sm-inline"><?= htmlspecialchars($title) ?></span><span class="d-sm-none"><?= htmlspecialchars(strlen($title) > 15 ? substr($title, 0, 15) . '...' : $title) ?></span></li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if ($show_actions): ?>
            <div class="action-buttons d-none d-md-flex">
                <?php foreach ($action_buttons as $btn): ?>
                        <button <?php if (!empty($btn['id'])): ?>id="<?= htmlspecialchars($btn['id']) ?>"<?php endif; ?> class="btn <?= htmlspecialchars($btn['class'] ?? 'btn-primary') ?> btn-action" onclick="<?= $btn['onclick'] ?? '' ?>">
                            <i class="<?= htmlspecialchars($btn['icon'] ?? 'fas fa-file') ?>"></i> <span class="d-none d-lg-inline"><?= htmlspecialchars($btn['label'] ?? '') ?></span>
                        </button>
                    <?php endforeach; ?>
            </div>
            <!-- Mobile action buttons -->
            <div class="action-buttons d-flex d-md-none">
                <?php foreach ($action_buttons as $btn): ?>
                        <button <?php if (!empty($btn['id'])): ?>id="<?= htmlspecialchars($btn['id']) ?>"<?php endif; ?> class="btn <?= htmlspecialchars($btn['class'] ?? 'btn-primary') ?> btn-action btn-sm" onclick="<?= $btn['onclick'] ?? '' ?>" title="<?= htmlspecialchars($btn['label'] ?? '') ?>">
                            <i class="<?= htmlspecialchars($btn['icon'] ?? 'fas fa-file') ?>"></i>
                        </button>
                    <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($add_button): ?>
            <?php
                // Prefer declarative data attributes for modal wiring.
                // If developer passed 'modal' and optional 'form' use them.
                $btnAttrs = [];
                if (!empty($add_button['modal'])) {
                    $btnAttrs['data-modal-target'] = $add_button['modal'];
                    if (!empty($add_button['form'])) $btnAttrs['data-modal-form'] = $add_button['form'];
                } elseif (!empty($add_button['onclick']) && preg_match("/showAddModal\(['\"]([^'\"]+)['\"],?\s*['\"]?([^'\"]*)['\"]?\)/", $add_button['onclick'], $m)) {
                    // Extract modalId and formId from old onclick and render data attributes instead
                    $btnAttrs['data-modal-target'] = $m[1];
                    if (!empty($m[2])) $btnAttrs['data-modal-form'] = $m[2];
                }
            ?>
            <button class="btn btn-primary btn-action" <?= implode(' ', array_map(function($k) use ($btnAttrs){ return $k . '="' . htmlspecialchars($btnAttrs[$k]) . '"'; }, array_keys($btnAttrs))) ?> <?php if (empty($btnAttrs)) echo 'onclick="' . ($add_button['onclick'] ?? '') . '"'; ?> >
                <i class="fas fa-plus"></i> <span class="d-none d-sm-inline"><?= htmlspecialchars($add_button['label']) ?></span>
            </button>
        <?php endif; ?>
    </div>
</div>
