<?php
require 'db.php';
try{
  if (isset($DB_INFO) && $DB_INFO['engine']==='mysql'){
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    // recreate stok_masuk
    $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk_tmp LIKE stok_masuk");
    $db->exec("INSERT INTO stok_masuk_tmp SELECT * FROM stok_masuk");
    $db->exec("RENAME TABLE stok_masuk TO stok_masuk_old, stok_masuk_tmp TO stok_masuk");
    $db->exec("DROP TABLE IF EXISTS stok_masuk_old");
    // recreate stok_keluar
    $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar_tmp LIKE stok_keluar");
    $db->exec("INSERT INTO stok_keluar_tmp SELECT * FROM stok_keluar");
    $db->exec("RENAME TABLE stok_keluar TO stok_keluar_old, stok_keluar_tmp TO stok_keluar");
    $db->exec("DROP TABLE IF EXISTS stok_keluar_old");
    $db->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Repaired FK (MySQL)\n";
  } else {
    // sqlite
    $db->exec('PRAGMA foreign_keys=OFF');
    $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk_tmp (id_masuk INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang));");
    $db->exec("INSERT INTO stok_masuk_tmp (id_masuk,id_barang,jumlah,tanggal,note) SELECT id_masuk,id_barang,jumlah,tanggal,note FROM stok_masuk");
    $db->exec("DROP TABLE IF EXISTS stok_masuk");
    $db->exec("ALTER TABLE stok_masuk_tmp RENAME TO stok_masuk");
    $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar_tmp (id_keluar INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang));");
    $db->exec("INSERT INTO stok_keluar_tmp (id_keluar,id_barang,jumlah,tanggal,note) SELECT id_keluar,id_barang,jumlah,tanggal,note FROM stok_keluar");
    $db->exec("DROP TABLE IF EXISTS stok_keluar");
    $db->exec("ALTER TABLE stok_keluar_tmp RENAME TO stok_keluar");
    $db->exec('PRAGMA foreign_keys=ON');
    echo "Repaired FK (SQLite)\n";
  }
}catch(Exception $e){ echo 'Error: '.$e->getMessage(); }
