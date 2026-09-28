<?php
// Fail sementara - PADAM selepas guna!
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache berjaya dikosongkan.";
} else {
    echo "ℹ️ OPcache tidak aktif pada server ini.";
}
