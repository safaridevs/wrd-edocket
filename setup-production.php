<?php
/**
 * Production Setup Script
 * Run this on the production server to create necessary directories
 */

$directories = [
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
    'public/build'
];

echo "Creating Laravel directories for production...\n";

foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "✓ Created: $dir\n";
        } else {
            echo "✗ Failed to create: $dir\n";
        }
    } else {
        echo "✓ Exists: $dir\n";
    }
    
    // Set permissions
    chmod($fullPath, 0755);
}

echo "\nSetting permissions...\n";

// Set storage permissions
$storagePath = __DIR__ . '/storage';
if (is_dir($storagePath)) {
    chmod($storagePath, 0755);
    echo "✓ Set storage permissions\n";
}

// Set bootstrap/cache permissions
$bootstrapCache = __DIR__ . '/bootstrap/cache';
if (is_dir($bootstrapCache)) {
    chmod($bootstrapCache, 0755);
    echo "✓ Set bootstrap/cache permissions\n";
}

echo "\nProduction setup complete!\n";
echo "Next steps:\n";
echo "1. Run: php artisan config:cache\n";
echo "2. Run: php artisan route:cache\n";
echo "3. Run: php artisan view:cache\n";
echo "4. Run: npm run build (if not done)\n";