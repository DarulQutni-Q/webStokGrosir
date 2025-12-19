<?php
// Navbar header included inside <body>
?>
<style>
:root{--g1:#9ee8bf;--g2:#f4fff9;--accent:#28b06b;--muted:#2b6b45;--card-shadow: rgba(43,107,69,0.08)}
body{background:var(--g2);font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;color:#234f33}
.navbar{box-shadow:0 10px 40px var(--card-shadow);background:var(--g1)!important;height:68px}
.navbar-brand{font-weight:800;letter-spacing:0.3px;color:#fff;font-size:1.25rem}
.navbar .container{display:flex;align-items:center}
.navbar-nav{align-items:center}
.nav-link{color:rgba(255,255,255,0.95);padding:0.4rem 0.9rem;border-radius:10px;transition:all .12s;font-weight:600}
.nav-link:hover{background:rgba(255,255,255,0.14);color:#062a16}
.nav-link.active{background:rgba(255,255,255,0.18);box-shadow:inset 0 -3px 0 rgba(0,0,0,0.02)}
.form-control{border-radius:12px; border:1px solid rgba(43,107,69,0.12); background:#fff;height:40px;padding:6px 12px;width:240px}
.card{border-radius:12px; box-shadow:0 12px 36px var(--card-shadow); border: none; transition:transform .18s ease, box-shadow .18s ease}
.card:hover{transform:translateY(-6px);box-shadow:0 20px 48px rgba(43,107,69,0.12)}
.summary-card{display:flex;align-items:center;gap:14px;padding:18px}
.summary-card .icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff}
.summary-card h3{margin:0;font-size:1.45rem}
.table thead th{background:transparent;color:#2d5a3f;border-bottom:2px solid rgba(43,107,69,0.06)}
.table tbody tr td{vertical-align:middle}
.table tbody tr:hover{background:linear-gradient(90deg, rgba(46,204,113,0.06), rgba(46,204,113,0.02))}
.btn-outline-primary{color:var(--muted); border-color: rgba(47,184,108,0.18)}
.btn-outline-primary:hover{background:var(--accent); color:#fff}
.btn-light{border-radius:8px}
.badge.bg-danger{background:#ff7b7b}
.small-muted{color:#2b6b45;font-size:0.95rem}
.container.mt-3{padding-top:6px}
.navbar-toggler{border:none;background:transparent;width:46px;height:46px;border-radius:10px}
.navbar-toggler-icon{background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255,255,255,0.95)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");}
/* Hero */
.hero{background:transparent;padding:22px 0 30px}
.hero .panel{display:flex;align-items:center;gap:18px}
.hero .title{font-size:1.7rem;font-weight:800;color:#0f3f26;margin-bottom:6px}
.hero .subtitle{color:#234f33;opacity:0.9;font-size:0.98rem;margin-bottom:6px}
.cta-btn{background:var(--accent);border:none;color:#fff;border-radius:10px;padding:8px 14px;font-weight:700}
.cta-btn.secondary{background:#fff;color:var(--accent);border:1px solid rgba(43,107,69,0.12)}
.leaf{width:86px;height:86px;border-radius:14px;background:rgba(40,176,107,0.12);display:flex;align-items:center;justify-content:center}
.leaf svg{transform:translateY(-2px)}
@media(max-width:767px){.hero .panel{flex-direction:column;align-items:flex-start}.form-control{width:140px}}
</style>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">StokKu</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu" aria-controls="navmenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="items.php">Barang</a></li>
        <li class="nav-item"><a class="nav-link" href="stock_in.php">Stok Masuk</a></li>
        <li class="nav-item"><a class="nav-link" href="stock_out.php">Stok Keluar</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Laporan</a></li>
      </ul>
      <div class="d-flex align-items-center me-3 small-muted"><?php date_default_timezone_set('Asia/Jakarta'); echo date('Y-m-d H:i'); ?></div>
      <?php if(isset($DB_INFO)){
          $dbbadge = ($DB_INFO['engine']==='mysql') ? 'MySQL' : 'SQLite';
          $dbstat = htmlspecialchars($DB_INFO['status']);
          // concise badge; full details available via title on hover when mysql
          $title = '';
          if(isset($DB_INFO['engine']) && $DB_INFO['engine']==='mysql'){
            $title = ' title="'.htmlspecialchars('Host: '.$DB_INFO['host'].' | DB: '.$DB_INFO['db'].' | User: '.$DB_INFO['user']).'"';
          }
          echo '<div class="me-3"><span class="badge"'.$title.' style="background:rgba(255,255,255,0.12);color:#fff;font-weight:600;padding:6px 10px;border-radius:8px">'.htmlspecialchars($dbbadge).' • '. $dbstat .'</span></div>';
      }
      ?>
      <form class="d-flex ms-auto" role="search" method="get" action="items.php">
        <input class="form-control me-2" type="search" name="q" placeholder="Cari barang..." aria-label="Search">
        <button class="btn btn-light" type="submit">Cari</button>
      </form>
    </div>
  </div>
</nav>



<div class="container mt-3"></div>