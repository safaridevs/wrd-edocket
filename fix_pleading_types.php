<?php
// Run this script to update pleading types for documents
// Usage: php fix_pleading_types.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update documents that should be pleading types
DB::table('documents')
    ->where('doc_type', 'request_to_docket')
    ->update(['pleading_type' => 'request_to_docket']);

DB::table('documents')
    ->where('doc_type', 'request_pre_hearing')
    ->update(['pleading_type' => 'request_pre_hearing']);

echo "Pleading types updated successfully!\n";

// Show documents that can now be stamped
$documents = DB::table('documents')
    ->select('id', 'original_filename', 'doc_type', 'pleading_type', 'approved', 'stamped')
    ->whereIn('pleading_type', ['request_to_docket', 'request_pre_hearing'])
    ->get();

echo "\nDocuments with pleading types:\n";
foreach ($documents as $doc) {
    $status = $doc->approved ? ($doc->stamped ? 'STAMPED' : 'READY TO STAMP') : 'NEEDS APPROVAL';
    echo "ID: {$doc->id} | {$doc->original_filename} | Status: {$status}\n";
}
