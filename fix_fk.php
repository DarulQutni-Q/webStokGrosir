<?php
require 'db.php';
try{
  if ($DB_INFO['engine']==='mysql'){
    // recreate stok_masuk with correct FK
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk_new LIKE stok_masuk");
    $db->exec("INSERT INTO stok_masuk_new SELECT * FROM stok_masuk");
    $db->exec("RENAME TABLE stok_masuk TO stok_masuk_old, stok_masuk_new TO stok_masuk");
    $db->exec("DROP TABLE IF EXISTS stok_masuk_old");
    $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar_new LIKE stok_keluar");
    $db->exec("INSERT INTO stok_keluar_new SELECT * FROM stok_keluar");
    $db->exec("RENAME TABLE stok_keluar TO stok_keluar_old, stok_keluar_new TO stok_keluar");
    $db->exec("DROP TABLE IF EXISTS stok_keluar_old");
    $db->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "FK fix for MySQL done\n";
  } else {
    echo "Not MySQL, skipping\n";
  }
}catch(Exception $e){ echo 'Error: '.$e->getMessage(); }
