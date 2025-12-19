<?php
require 'db.php';
$action = $_REQUEST['action'] ?? '';
if ($action === 'add') {
  $stmt = $db->prepare('INSERT INTO barang (nama_barang,id_kategori,id_satuan,stok) VALUES (?,?,?,?)');
  $stmt->execute([$_POST['name'], $_POST['category']?:null, $_POST['unit']?:null, (int)$_POST['stock']]);
  header('Location: items.php'); exit;
}
if ($action === 'edit') {
  $stmt = $db->prepare('UPDATE barang SET nama_barang=?,id_kategori=?,id_satuan=?,stok=? WHERE id_barang=?');
  $stmt->execute([$_POST['name'], $_POST['category']?:null, $_POST['unit']?:null, (int)$_POST['stock'], (int)$_POST['id']]);
  header('Location: items.php'); exit;
}
if ($action === 'delete') {
  $db->prepare('DELETE FROM barang WHERE id_barang=?')->execute([$_GET['id']]);
  header('Location: items.php'); exit;
}

$q = $_GET['q'] ?? '';
if ($q) {
  $stmt = $db->prepare("SELECT b.*, k.nama_kategori AS category_name, s.nama_satuan AS unit_name FROM barang b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori LEFT JOIN satuan s ON s.id_satuan=b.id_satuan WHERE b.nama_barang LIKE ? OR k.nama_kategori LIKE ? ORDER BY b.id_barang DESC");
  $like = '%' . $q . '%';
  $stmt->execute([$like,$like]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  // ensure default kategori/satuan exist (guarantee population on page load)
  // Only run SQLite-style CREATE TABLE here; MySQL tables are created in db.php
  if (!isset($DB_INFO) || ($DB_INFO['engine'] ?? '') === 'sqlite') {
    $db->exec("CREATE TABLE IF NOT EXISTS kategori (id_kategori INTEGER PRIMARY KEY AUTOINCREMENT, nama_kategori TEXT NOT NULL);");
    $db->exec("CREATE TABLE IF NOT EXISTS satuan (id_satuan INTEGER PRIMARY KEY AUTOINCREMENT, nama_satuan TEXT NOT NULL);");
  }
  $defaults_k = ['Makanan','Minuman','Bumbu','Sembako','Snack','Perawatan Rumah','Peralatan Dapur','Umum'];
  foreach($defaults_k as $dk){ $r = $db->prepare('SELECT id_kategori FROM kategori WHERE nama_kategori = ? LIMIT 1'); $r->execute([$dk]); if(!$r->fetch()) $db->prepare('INSERT INTO kategori (nama_kategori) VALUES (?)')->execute([$dk]); }
  $defaults_s = ['pcs','box','kg','g','ltr','ml','pak','roll'];
  foreach($defaults_s as $ds){ $r = $db->prepare('SELECT id_satuan FROM satuan WHERE nama_satuan = ? LIMIT 1'); $r->execute([$ds]); if(!$r->fetch()) $db->prepare('INSERT INTO satuan (nama_satuan) VALUES (?)')->execute([$ds]); }
  $items = $db->query('SELECT b.*, k.nama_kategori AS category_name, s.nama_satuan AS unit_name FROM barang b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori LEFT JOIN satuan s ON s.id_satuan=b.id_satuan ORDER BY id_barang DESC')->fetchAll(PDO::FETCH_ASSOC);
}
$editing = null;
if (isset($_GET['edit'])) {
  $editing = $db->prepare('SELECT * FROM barang WHERE id_barang=?'); $editing->execute([$_GET['edit']]); $editing = $editing->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Barang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container py-4">
  <h1>Kelola Barang</h1>
  <p><a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a></p>
  <div class="row">
    <div class="col-md-5">
      <div class="card p-3">
        <h5><?= $editing ? 'Edit Barang' : 'Tambah Barang' ?></h5>
        <form method="post" action="items.php?action=<?= $editing ? 'edit' : 'add' ?>">
          <?php if($editing): ?><input type="hidden" name="id" value="<?= $editing['id_barang'] ?>"><?php endif; ?>
          <div class="mb-2"><label class="form-label">Kategori</label>
            <select name="category" class="form-select">
              <option value="">-- Pilih --</option>
              <?php foreach($db->query('SELECT * FROM kategori') as $kc): ?>
                <option value="<?= $kc['id_kategori'] ?>" <?= ($editing && $editing['id_kategori']==$kc['id_kategori'])? 'selected':'' ?>><?= htmlspecialchars($kc['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Nama</label><input name="name" class="form-control" required value="<?= $editing['nama_barang'] ?? ''?>"></div>
          <div class="mb-2"><label class="form-label">Satuan</label>
            <select name="unit" class="form-select">
              <option value="">-- Pilih --</option>
              <?php foreach($db->query('SELECT * FROM satuan') as $su): ?>
                <option value="<?= $su['id_satuan'] ?>" <?= ($editing && $editing['id_satuan']==$su['id_satuan'])? 'selected':'' ?>><?= htmlspecialchars($su['nama_satuan']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Stok awal</label><input name="stock" type="number" min="0" class="form-control" required value="<?= $editing['stok'] ?? 0?>"></div>
          <button class="btn btn-primary">Simpan</button>
        </form>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card p-3">
        <h5>Daftar Barang</h5>
        <form class="mb-2"><input id="q" name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Cari... (nama / kategori)" oninput="filter()"></form>
        <div class="table-responsive">
        <table class="table table-sm" id="tbl">
          <thead><tr><th>#</th><th>Kategori</th><th>Nama</th><th>Satuan</th><th>Stok</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach($items as $it): ?>
            <tr><td><?= $it['id_barang'] ?></td><td><?=htmlspecialchars($it['category_name'])?></td><td><?=htmlspecialchars($it['nama_barang'])?></td><td><?=htmlspecialchars($it['unit_name'])?></td><td><?= (int)$it['stok'] ?></td><td><?php if($it['stok']<=5){echo '<span class="badge bg-danger">Menipis</span>';} else {echo '<span class="badge bg-success">Cukup</span>';} ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="items.php?edit=<?= $it['id_barang'] ?>">Edit</a> <a class="btn btn-sm btn-outline-danger" href="items.php?action=delete&id=<?= $it['id_barang'] ?>" onclick="return confirm('Hapus?')">Hapus</a></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function filter(){
  var q=document.getElementById('q').value.toLowerCase();
  var rows=document.querySelectorAll('#tbl tbody tr');
  rows.forEach(r=>{r.style.display=(r.innerText.toLowerCase().includes(q))?'':'none';});
}
</script>
</body>
</html>