<?php
require 'db.php';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $item = $db->prepare('SELECT * FROM barang WHERE id_barang=?'); $item->execute([$_POST['item_id']]); $it = $item->fetch(PDO::FETCH_ASSOC);
  if ($it){
    $qty = (int)$_POST['qty'];
    if(isset($DB_INFO) && $DB_INFO['engine']==='mysql'){
      $db->prepare('INSERT INTO stok_masuk (id_barang,jumlah,tanggal,note) VALUES (?,?,NOW(),?)')->execute([$_POST['item_id'],$qty,$_POST['note']]);
    } else {
      $db->prepare("INSERT INTO stok_masuk (id_barang,jumlah,tanggal,note) VALUES (?,?,datetime('now'),?)")->execute([$_POST['item_id'],$qty,$_POST['note']]);
    }
    $db->prepare('UPDATE barang SET stok = stok + ? WHERE id_barang=?')->execute([$qty,$_POST['item_id']]);
  }
  header('Location: stock_in.php'); exit;
}
$items = $db->query('SELECT b.id_barang, b.nama_barang, b.stok, s.nama_satuan, k.nama_kategori FROM barang b LEFT JOIN satuan s ON s.id_satuan=b.id_satuan LEFT JOIN kategori k ON k.id_kategori=b.id_kategori ORDER BY b.nama_barang')->fetchAll(PDO::FETCH_ASSOC);
$logs = $db->query('SELECT sm.*, b.nama_barang AS nama_barang FROM stok_masuk sm JOIN barang b ON b.id_barang=sm.id_barang ORDER BY sm.tanggal DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Stok Masuk</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container py-4">
  <h1>Stok Masuk</h1>
  <p><a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a></p>
  <div class="row">
    <div class="col-md-5"><div class="card p-3">
      <form method="post">
        <div class="mb-2"><label>Barang</label><select name="item_id" class="form-select" required><?php foreach($items as $i): ?><option value="<?= $i['id_barang'] ?>"><?=htmlspecialchars($i['nama_barang'])?> (<?=htmlspecialchars($i['nama_satuan'])?>)</option><?php endforeach; ?></select></div>
        <div class="mb-2"><label>Qty</label><input type="number" name="qty" class="form-control" min="1" value="1" required></div>
        <div class="mb-2"><label>Catatan</label><input name="note" class="form-control"></div>
        <button class="btn btn-success">Tambah Stok</button>
      </form>
    </div></div>
    <div class="col-md-7"><div class="card p-3">
      <h5>Log Stok Masuk</h5>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Barang</th><th>Qty</th><th>Tanggal</th><th>Note</th></tr></thead><tbody><?php foreach($logs as $l): ?><tr><td><?= $l['id_masuk'] ?></td><td><?=htmlspecialchars($l['nama_barang'])?></td><td><?= (int)$l['jumlah'] ?></td><td><?= $l['tanggal'] ?></td><td><?=htmlspecialchars($l['note'])?></td></tr><?php endforeach; ?></tbody></table></div>
    </div></div>
  </div>
</div>
</body>
</html>