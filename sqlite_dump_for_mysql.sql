USE `stokdb2`;
SET FOREIGN_KEY_CHECKS=0;
INSERT IGNORE INTO `barang` (id_barang,nama_barang,id_kategori,id_satuan,stok) VALUES (1,'sarden',NULL,NULL,20);
INSERT IGNORE INTO `barang` (id_barang,nama_barang,id_kategori,id_satuan,stok) VALUES (2,'golda',NULL,NULL,20);
INSERT IGNORE INTO `barang` (id_barang,nama_barang,id_kategori,id_satuan,stok) VALUES (3,'dadap',NULL,NULL,10);
INSERT IGNORE INTO `stok_masuk` (id_barang,jumlah,tanggal,note) VALUES (1,10,'2025-12-19 17:05:14',NULL);
INSERT IGNORE INTO `stok_masuk` (id_barang,jumlah,tanggal,note) VALUES (3,5,'2025-12-19 17:08:57',NULL);
INSERT IGNORE INTO `stok_masuk` (id_barang,jumlah,tanggal,note) VALUES (3,5,'2025-12-19 17:09:13',NULL);
SET FOREIGN_KEY_CHECKS=1;