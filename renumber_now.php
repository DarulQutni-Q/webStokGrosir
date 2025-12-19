<?php
require 'db.php';
try{
  // beginTransaction may fail in some environments, so suppress errors
  try{ $db->beginTransaction(); } catch(Exception $bt){}
  if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'){
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $db->exec("CREATE TABLE IF NOT EXISTS barang_new LIKE barang");
    $db->exec("TRUNCATE TABLE barang_new");
  } else {
    $db->exec('PRAGMA foreign_keys=OFF');
    $db->exec("CREATE TABLE IF NOT EXISTS barang_new (id_barang INTEGER PRIMARY KEY AUTOINCREMENT, nama_barang TEXT NOT NULL, id_kategori INTEGER, id_satuan INTEGER, stok INTEGER DEFAULT 0);");
    $db->exec("DELETE FROM barang_new");
  }
  $rows = $db->query('SELECT * FROM barang ORDER BY id_barang')->fetchAll(PDO::FETCH_ASSOC);
  $ins = $db->prepare('INSERT INTO barang_new (nama_barang,id_kategori,id_satuan,stok) VALUES (?,?,?,?)');
  $map = [];
  foreach($rows as $r){ $ins->execute([$r['nama_barang'],$r['id_kategori'],$r['id_satuan'],$r['stok']]); $map[$r['id_barang']] = $db->lastInsertId(); }
  foreach($map as $old=>$new){ $db->exec("UPDATE stok_masuk SET id_barang = $new WHERE id_barang = $old"); $db->exec("UPDATE stok_keluar SET id_barang = $new WHERE id_barang = $old"); }
  if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'){ $db->exec("RENAME TABLE barang TO barang_old, barang_new TO barang"); $db->exec("DROP TABLE IF EXISTS barang_old"); $db->exec('SET FOREIGN_KEY_CHECKS=1'); }
  else { $db->exec("ALTER TABLE barang RENAME TO barang_old"); $db->exec("ALTER TABLE barang_new RENAME TO barang"); $db->exec("DROP TABLE IF EXISTS barang_old"); $db->exec('PRAGMA foreign_keys=ON'); }
  try{ $db->commit(); } catch(Exception$e){}
  echo "Renumbered\n";
}catch(Exception $e){ try{ $db->rollBack(); }catch(Exception$e2){} echo "Error: " . $e->getMessage(); }
