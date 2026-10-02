<?php
require_once '../config.php'; // adjust path as needed

$pdo->exec("ALTER TABLE ingredients ADD COLUMN IF NOT EXISTS cost DECIMAL(10,2) NOT NULL DEFAULT 0.00");
$pdo->exec("ALTER TABLE ingredients ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) NULL");

echo "Migration completed. Columns added/verified.\n";
?>
