<?php
require_once "config/database.php";
$tables = ["users","agents","properties","favorites","enquiries","visits","messages","notifications","settings"];
$missing=[];
foreach($tables as $table){
    $stmt=$pdo->query("SHOW TABLES LIKE ".$pdo->quote($table));
    if(!$stmt->fetchColumn()) $missing[]=$table;
}
header("Content-Type: text/plain; charset=utf-8");
echo empty($missing) ? "RealEstateHub database OK\n" : "Missing tables: ".implode(", ",$missing)."\n";
?>
