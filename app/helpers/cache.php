<?php
// Simple file-based cache helper. Use for small maps and short TTLs.
// Not intended as a replacement for Redis/APCu in production, but provides a portable fallback.

function cache_dir_path() {
    $dir = __DIR__ . '/../../storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_get($key) {
    $file = cache_dir_path() . '/' . sha1($key) . '.cache';
    if (!file_exists($file)) return null;
    $data = @file_get_contents($file);
    if ($data === false) return null;
    $obj = @unserialize($data);
    if (!is_array($obj) || !isset($obj['expiry']) || !array_key_exists('value', $obj)) return null;
    if ($obj['expiry'] !== 0 && time() > $obj['expiry']) { @unlink($file); return null; }
    return $obj['value'];
}

function cache_set($key, $value, $ttl = 60) {
    $file = cache_dir_path() . '/' . sha1($key) . '.cache';
    $obj = ['expiry' => $ttl>0 ? time() + intval($ttl) : 0, 'value' => $value];
    @file_put_contents($file, serialize($obj), LOCK_EX);
}

function cache_delete($key) {
    $file = cache_dir_path() . '/' . sha1($key) . '.cache';
    if (file_exists($file)) @unlink($file);
}

?>