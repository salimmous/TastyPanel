<?php

/**
 * Direct download of project ZIP (no Laravel – works even if routing fails).
 * URL: https://tastypanel.site/download.php
 */
$newZipFile = __DIR__.'/tastypanel-site.zip';
$legacyZipFile = __DIR__.'/tastypanel-site.zip';
$zipFile = is_file($newZipFile) ? $newZipFile : $legacyZipFile;
if (! is_file($zipFile)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'File not found';
    exit;
}
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="tastypanel-site.zip"');
header('Content-Length: '.filesize($zipFile));
header('Cache-Control: no-transform');
while (ob_get_level()) {
    ob_end_clean();
}
readfile($zipFile);
exit;
