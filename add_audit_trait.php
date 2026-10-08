<?php
$models = ['Purchase.php', 'Sale.php', 'Voucher.php', 'JournalEntry.php', 'Ledger.php'];
foreach ($models as $modelFile) {
    $path = __DIR__ . "/app/Models/$modelFile";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, 'use \App\Traits\Auditable;') === false) {
            $content = preg_replace('/class\s+[A-Za-z0-9_]+\s+extends\s+[A-Za-z0-9_]+[^{]*\{/', "$0\n    use \\App\\Traits\\Auditable;\n", $content);
            file_put_contents($path, $content);
            echo "Added Auditable to $modelFile\n";
        }
    }
}
