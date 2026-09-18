<?php
/**
 * ASENTRA SPK — Periode list (Phase 6L).
 *
 * Kept routed at GET /admin/periode for backward compatibility. The table markup
 * lives in admin/periode_table.php and is shared with /admin/import, which is now
 * the periode workflow center. ponytail: header + "+ Tambah Periode" button
 * deleted — manual creation is the mini-form on the import page.
 *
 * @var string $title
 * @var string $subtitle
 * @var array<int, array<string, mixed>> $periods
 */
?>
<?= viewPartial('admin.periode_table', ['periods' => $periods]) ?>
