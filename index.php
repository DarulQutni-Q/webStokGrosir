<?php
require 'db.php';
$threshold = 5;
$total_types = $db->query('SELECT COUNT(*) FROM barang')->fetchColumn();
$total_stock = $db->query('SELECT SUM(stok) FROM barang')->fetchColumn();
if ($total_stock === null) $total_stock = 0;
$low_items = $db->query('SELECT b.*, k.nama_kategori AS category, s.nama_satuan AS unit_name FROM barang b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori LEFT JOIN satuan s ON s.id_satuan=b.id_satuan WHERE b.stok <= ' . (int)$threshold)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Stok Barang</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container py-4">
  <h1 class="mb-2">Stok Barang</h1>
  <?php if(count($low_items)): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
    <div><strong>Perhatian:</strong> Beberapa barang stok menipis.</div>
    <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#lowModal">Lihat</button>
  </div>
  <?php endif; ?>
  <!-- Summary row moved into hero area, keep compact -->
  

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card summary-card">
        <div class="icon" style="background:var(--accent)">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke="white" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
        </div>
        <div>
          <h3><?=htmlspecialchars($total_types)?></h3>
          <div class="small-muted">Total Jenis Barang</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card summary-card">
        <div class="icon" style="background:#5fd39a">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke="white" stroke-width="2" d="M3 12h18M6 6h12M6 18h12"/></svg>
        </div>
        <div>
          <h3><?=htmlspecialchars($total_stock)?></h3>
          <div class="small-muted">Total Stok Keseluruhan</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3">
        <h5>Fitur Cepat</h5>
        <div><a href="items.php" class="btn btn-outline-primary btn-sm">Kelola Barang</a> <a href="stock_in.php" class="btn btn-outline-primary btn-sm">Stok Masuk</a> <a href="stock_out.php" class="btn btn-outline-primary btn-sm">Stok Keluar</a> <a href="reports.php" class="btn btn-outline-primary btn-sm">Laporan</a></div>
      </div>
    </div>
  </div>

  <h4 class="mt-4">Daftar Barang</h4>
  <div class="mb-3 small-muted">Menampilkan semua barang. Gunakan fitur "Kelola Barang" untuk menambah atau edit stok.</div>
  <div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Satuan</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach($db->query('SELECT b.*, k.nama_kategori AS category_name, s.nama_satuan AS unit_name FROM barang b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori LEFT JOIN satuan s ON s.id_satuan=b.id_satuan ORDER BY b.nama_barang') as $item): ?>
      <tr>
        <td><?= $item['id_barang'] ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div><div style="font-weight:700"><?=htmlspecialchars($item['nama_barang'])?></div><div class="small-muted">ID: <?= $item['id_barang'] ?></div></div>
          </div>
        </td>
        <td><span class="badge bg-light" style="color:var(--muted);border:1px solid rgba(43,107,69,0.06)"><?=htmlspecialchars($item['category_name'])?></span></td>
        <td><?=htmlspecialchars($item['unit_name'])?></td>
        <td><?= (int)$item['stok'] ?></td>
        <td><?php if($item['stok']<=5){echo '<span class="badge bg-danger">Menipis</span>';} else {echo '<span class="badge bg-success">Cukup</span>';} ?></td>
        <td><a class="btn btn-sm btn-outline-primary" href="items.php?edit=<?= $item['id_barang'] ?>">Edit</a> <a class="btn btn-sm btn-outline-danger" href="items.php?action=delete&id=<?= $item['id_barang'] ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Low stock modal -->
<div class="modal fade" id="lowModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Barang Menipis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php if(count($low_items)): ?>
          <div class="list-group">
            <?php foreach($low_items as $li): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div style="font-weight:700"><?=htmlspecialchars($li['nama_barang'])?></div>
                  <div class="small-muted">Kategori: <?=htmlspecialchars($li['category'])?> • ID: <?= $li['id_barang'] ?></div>
                </div>
                <div class="text-end">
                  <div class="badge bg-danger py-2"><?= (int)$li['stok'] ?> <?=htmlspecialchars($li['unit_name'] ?? '')?></div>
                  <div style="margin-top:6px"><a class="btn btn-sm btn-outline-primary" href="items.php?edit=<?= $li['id_barang'] ?>">Tambah/Stok</a></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-info">Tidak ada barang menipis.</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// small client-side highlight effect for new low items button
const btn = document.querySelector('.alert button'); if(btn){btn.addEventListener('click',()=>{btn.classList.add('btn-success'); setTimeout(()=>btn.classList.remove('btn-success'),600)} )}
</script>
</body>
</html>