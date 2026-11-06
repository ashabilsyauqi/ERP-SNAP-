<?php
$pdo = new PDO("sqlite:D:/IT-ONLY!/project-dutaglobaltech/local-dev-serp/database/database.sqlite");
foreach ($pdo->query("PRAGMA table_info(cash_transactions)") as $row) {
    echo $row['name'] . " (" . $row['type'] . ")\n";
}
