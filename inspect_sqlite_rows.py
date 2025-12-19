import sqlite3,os,sys

db=r'C:\xampp\htdocs\webStokGrosir\data.db'
if not os.path.exists(db):
    print('MISSING'); sys.exit(0)
con=sqlite3.connect(db)
cur=con.cursor()
for t in ('items','stock_in','stock_out'):
    cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name=?", (t,))
    if not cur.fetchone():
        print('NO', t)
        continue
    print('TABLE', t)
    info = cur.execute('PRAGMA table_info(%s)'%t).fetchall()
    print('COLUMNS:', info)
    rows = cur.execute('SELECT * FROM %s'%t).fetchall()
    print('ROWS:')
    for r in rows:
        print(r)
    print('---')
con.close()
