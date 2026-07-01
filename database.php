<?php
// config.php - SQLite connection + table setup
// This part is ready to use as-is, you don't need to touch it.

$db = new PDO('sqlite:' . __DIR__ . '/db/library.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
    CREATE TABLE IF NOT EXISTS Books (
        ISBN TEXT PRIMARY KEY,
        Title TEXT,
        Copyright INTEGER,
        Edition TEXT,
        Price REAL,
        Quantity INTEGER
    )
");

// Seed sample data only if the table is empty
$count = $db->query("SELECT COUNT(*) FROM Books")->fetchColumn();
if ($count == 0) {
    $seed = $db->prepare("INSERT INTO Books (ISBN, Title, Copyright, Edition, Price, Quantity) VALUES (?, ?, ?, ?, ?, ?)");
    $seed->execute(['2', 'Lord of the Rings', 2004, '3rd', 999.00, 10]);
}
?>
