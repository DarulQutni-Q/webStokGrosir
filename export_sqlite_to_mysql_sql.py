import sqlite3,os,sys,subprocess,re

# read PHP config.php to extract MySQL settings
cfg_path = os.path.join(os.path.dirname(__file__), 'config.php')
host='127.0.0.1'; port=3306; dbname='stokdb'; user='root'; password=''
if os.path.exists(cfg_path):
    txt = open(cfg_path,'r',encoding='utf-8').read()
    m = re.search(r"'host'\s*=>\s*'([^']+)'", txt)
    if m: host = m.group(1)
    m = re.search(r"'port'\s*=>\s*([0-9]+)", txt)
    if m: port = int(m.group(1))
    m = re.search(r"'db'\s*=>\s*'([^']+)'", txt)
    if m: dbname = m.group(1)
    m = re.search(r"'user'\s*=>\s*'([^']+)'", txt)
    if m: user = m.group(1)
    m = re.search(r"'pass'\s*=>\s*'([^']*)'", txt)
    if m: password = m.group(1)

sqlite_file = os.path.join(os.path.dirname(__file__), 'data.db')
if not os.path.exists(sqlite_file):
    print('SQLite file not found:', sqlite_file)
    sys.exit(1)

con = sqlite3.connect(sqlite_file)
cur = con.cursor()

out_sql = []
out_sql.append('USE `'+dbname+'`;')
out_sql.append('SET FOREIGN_KEY_CHECKS=0;')

# helper
def q(v):
    if v is None:
        return 'NULL'
    return "'" + str(v).replace("'","\\'") + "'"

# copy kategori and satuan if present
for t in ('kategori','satuan'):
    cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name=?", (t,))
    if cur.fetchone():
        rows = cur.execute('SELECT * FROM '+t).fetchall()
        if rows:
            cols = [c[1] for c in cur.execute('PRAGMA table_info(%s)'%t).fetchall()]
            for r in rows:
                vals = ','.join(q(v) for v in r)
                out_sql.append(f'INSERT IGNORE INTO `{t}` ({" ,".join(cols)}) VALUES ({vals});')

# items -> barang
cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='items'")
if cur.fetchone():
    cols = [c[1] for c in cur.execute('PRAGMA table_info(items)').fetchall()]
    rows = cur.execute('SELECT * FROM items').fetchall()
    # determine if items has id, name, stock columns
    for r in rows:
        d = dict(zip(cols,r))
        idv = d.get('id') or d.get('ID') or d.get('item_id')
        name = d.get('name') or d.get('nama') or d.get('nama_barang') or d.get('title')
        stock = d.get('stock') or d.get('stok') or d.get('qty') or 0
        # construct insert preserving id if present
        if idv is not None:
            out_sql.append("INSERT IGNORE INTO `barang` (id_barang,nama_barang,id_kategori,id_satuan,stok) VALUES (%s,%s,NULL,NULL,%s);" % (str(idv), q(name), str(stock)))
        else:
            out_sql.append("INSERT IGNORE INTO `barang` (nama_barang,id_kategori,id_satuan,stok) VALUES (%s,NULL,NULL,%s);" % (q(name), str(stock)))

# stock_in -> stok_masuk
cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='stock_in'")
if cur.fetchone():
    cols = [c[1] for c in cur.execute('PRAGMA table_info(stock_in)').fetchall()]
    rows = cur.execute('SELECT * FROM stock_in').fetchall()
    for r in rows:
        d = dict(zip(cols,r))
        item_id = d.get('item_id') or d.get('id_item') or d.get('item')
        qty = d.get('qty') or d.get('jumlah') or 0
        date = d.get('date') or d.get('tanggal')
        note = d.get('note') or d.get('catatan')
        if item_id is None:
            continue
        out_sql.append("INSERT IGNORE INTO `stok_masuk` (id_barang,jumlah,tanggal,note) VALUES (%s,%s,%s,%s);" % (str(item_id), str(qty), q(date), q(note)))

# stock_out -> stok_keluar
cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='stock_out'")
if cur.fetchone():
    cols = [c[1] for c in cur.execute('PRAGMA table_info(stock_out)').fetchall()]
    rows = cur.execute('SELECT * FROM stock_out').fetchall()
    for r in rows:
        d = dict(zip(cols,r))
        item_id = d.get('item_id') or d.get('id_item') or d.get('item')
        qty = d.get('qty') or d.get('jumlah') or 0
        date = d.get('date') or d.get('tanggal')
        note = d.get('note') or d.get('catatan')
        if item_id is None:
            continue
        out_sql.append("INSERT IGNORE INTO `stok_keluar` (id_barang,jumlah,tanggal,note) VALUES (%s,%s,%s,%s);" % (str(item_id), str(qty), q(date), q(note)))

out_sql.append('SET FOREIGN_KEY_CHECKS=1;')

sql_path = os.path.join(os.path.dirname(__file__), 'sqlite_dump_for_mysql.sql')
open(sql_path,'w',encoding='utf-8').write('\n'.join(out_sql))
print('Wrote SQL to', sql_path)

# import using mysql client
mysql_exe = r'C:\\xampp\\mysql\\bin\\mysql.exe'
if not os.path.exists(mysql_exe):
    mysql_exe = 'mysql'
cmd = [mysql_exe, '-h', host, '-P', str(port), '-u', user]
if password != '':
    cmd.append('-p'+password)
# if password is empty, do not pass -p to avoid interactive prompt
cmd.append(dbname)
print('Running mysql import...')
with open(sql_path,'rb') as infile:
    p = subprocess.run(cmd, stdin=infile, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    print('mysql stdout:', p.stdout.decode('utf-8',errors='ignore'))
    print('mysql stderr:', p.stderr.decode('utf-8',errors='ignore'))

# print counts
print('Counts after import:')
check_cmd = [mysql_exe, '-h', host, '-P', str(port), '-u', user]
if password != '':
    check_cmd.append('-p'+password)
# if password is empty, do not pass -p
check_cmd.append('-e')
check_cmd.append("SELECT 'barang',COUNT(*) FROM barang; SELECT 'stok_masuk',COUNT(*) FROM stok_masuk; SELECT 'stok_keluar',COUNT(*) FROM stok_keluar;")
check_cmd.append(dbname)
pc = subprocess.run(check_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
print(pc.stdout.decode('utf-8',errors='ignore'))
if pc.returncode != 0:
    print('mysql check stderr:', pc.stderr.decode('utf-8',errors='ignore'))

con.close()
