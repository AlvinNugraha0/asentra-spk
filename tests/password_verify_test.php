<?php
// ASENTRA SPK — Password hash verification test for seed users

declare(strict_types=1);

$adminHash = '$2y$10$IKh7L123FNsJNAxh2dO1CesdMGxNTR8k0mAxlBQMtOUP4Enf26yBO';
$ownerHash = '$2y$10$HtnknL4Fdp409ftZJKID2u/7ZRlVn0Zy0Kmhj7uvaVMlIsajzhpIG';

$ok = true;

if (!password_verify('admin', $adminHash)) {
    echo "FAIL: admin password does not verify\n";
    $ok = false;
}

if (!password_verify('owner', $ownerHash)) {
    echo "FAIL: owner password does not verify\n";
    $ok = false;
}

if ($ok) {
    echo "OK: Seed password hashes verified.\n";
}
