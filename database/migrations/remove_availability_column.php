<?php
require_once __DIR__ . '/../db.php';

try {
    // Start transaction
    $pdo->beginTransaction();

    // Remove availability column
    $pdo->exec("ALTER TABLE apartments DROP COLUMN availability");

    // Commit transaction
    $pdo->commit();
    echo "Successfully removed availability column from apartments table\n";

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error removing availability column: " . $e->getMessage() . "\n";
    exit(1);
} 