<?php
/** @var string $route */
/** @var string $icon */
/** @var string $label */
$icons = [
    'LayoutDashboard' => '<i class="ph ph-squares-four text-xl"></i>',
    'Users' => '<i class="ph ph-users text-xl"></i>',
    'SlidersHorizontal' => '<i class="ph ph-sliders-horizontal text-xl"></i>',
    'ClipboardCheck' => '<i class="ph ph-clipboard-text text-xl"></i>',
    'Trophy' => '<i class="ph ph-trophy text-xl"></i>',
    'History' => '<i class="ph ph-clock-counter-clockwise text-xl"></i>',
    'Printer' => '<i class="ph ph-printer text-xl"></i>',
    'LogOut' => '<i class="ph ph-sign-out text-xl"></i>',
];

$isActive = currentRoute() === $route;
$iconSvg = $icons[$icon] ?? '<i class="ph ph-circle text-xl"></i>';
?>
<li class="nav-item">
    <a href="<?= route($route) ?>" class="nav-link <?= $isActive ? 'active' : '' ?>" aria-label="<?= e($label) ?>">
        <?= $iconSvg ?>
        <span class="nav-label"><?= e($label) ?></span>
    </a>
</li>
