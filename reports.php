<?php
require 'db.php';
if (isset($_GET['export'])){
  $what = $_GET['export'];
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="report-'. $what .'.xlsx"'); // .xlsx extension for Excel
  $out = fopen('php://output','w');
  if ($what==='items'){
    fputcsv($out, ['id_barang','nama_barang','kategori','satuan','stok']);
    $s=$db->query('SELECT b.*, k.nama_kategori, s.nama_satuan FROM barang b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori LEFT JOIN satuan s ON s.id_satuan=b.id_satuan'); foreach($s as $r) fputcsv($out, [$r['id_barang'],$r['nama_barang'],$r['nama_kategori'],$r['nama_satuan'],$r['stok']]);
  } elseif ($what==='in'){
    fputcsv($out, ['id_masuk','id_barang','nama_barang','jumlah','tanggal','note']);
    $s=$db->query('SELECT sm.*, b.nama_barang FROM stok_masuk sm JOIN barang b ON b.id_barang=sm.id_barang'); foreach($s as $r) fputcsv($out, [$r['id_masuk'],$r['id_barang'],$r['nama_barang'],$r['jumlah'],$r['tanggal'],$r['note']]);
  } else {
    fputcsv($out, ['id_keluar','id_barang','nama_barang','jumlah','tanggal','note']);
    $s=$db->query('SELECT sk.*, b.nama_barang FROM stok_keluar sk JOIN barang b ON b.id_barang=sk.id_barang'); foreach($s as $r) fputcsv($out, [$r['id_keluar'],$r['id_barang'],$r['nama_barang'],$r['jumlah'],$r['tanggal'],$r['note']]);
  }
  fclose($out);
  exit;
}
// fetch data to display
$items = $db->query('SELECT b.*, k.nama_kategori, s.nama_satuan FROM barang b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori LEFT JOIN satuan s ON s.id_satuan=b.id_satuan ORDER BY b.nama_barang')->fetchAll(PDO::FETCH_ASSOC);
$ins = $db->query('SELECT sm.*, b.nama_barang AS nama_barang FROM stok_masuk sm JOIN barang b ON b.id_barang=sm.id_barang ORDER BY sm.tanggal DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
$outs = $db->query('SELECT sk.*, b.nama_barang AS nama_barang FROM stok_keluar sk JOIN barang b ON b.id_barang=sk.id_barang ORDER BY sk.tanggal DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container py-4">
  <h1>Laporan</h1>
  <p><a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a></p>

  <div class="row g-3">
    <div class="col-md-12">
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="m-0">Stok Barang (barang, kategori, satuan)</h5>
          <div><a class="btn btn-sm btn-outline-primary" href="reports.php?export=items">Export (.xlsx)</a></div>
        </div>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Kategori</th><th>Nama</th><th>Satuan</th><th>Stok</th></tr></thead><tbody>
        <?php foreach($items as $r): ?>
          <tr><td><?= $r['id_barang'] ?></td><td><?=htmlspecialchars($r['nama_kategori'])?></td><td><?=htmlspecialchars($r['nama_barang'])?></td><td><?=htmlspecialchars($r['nama_satuan'])?></td><td><?= (int)$r['stok'] ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="m-0">Log Stok Masuk</h6>
          <div><a class="btn btn-sm btn-outline-success" href="reports.php?export=in">Export (.xlsx)</a></div>
        </div>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Barang</th><th>Qty</th><th>Tanggal</th><th>Note</th></tr></thead><tbody>
        <?php foreach($ins as $r): ?>
          <tr><td><?= $r['id_masuk'] ?></td><td><?=htmlspecialchars($r['nama_barang'])?></td><td><?= (int)$r['jumlah'] ?></td><td><?= $r['tanggal'] ?></td><td><?=htmlspecialchars($r['note'])?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="m-0">Log Stok Keluar</h6>
          <div><a class="btn btn-sm btn-outline-danger" href="reports.php?export=out">Export (.xlsx)</a></div>
        </div>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Barang</th><th>Qty</th><th>Tanggal</th><th>Note</th></tr></thead><tbody>
        <?php foreach($outs as $r): ?>
          <tr><td><?= $r['id_keluar'] ?></td><td><?=htmlspecialchars($r['nama_barang'])?></td><td><?= (int)$r['jumlah'] ?></td><td><?= $r['tanggal'] ?></td><td><?=htmlspecialchars($r['note'])?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </div>
    </div>

  </div>

</div>
</body>
</html>