import sqlite3,os
con=sqlite3.connect('C:/xampp/htdocs/webStokGrosir/data.db')
cur=con.cursor()
cols=[c[0] for c in cur.execute('PRAGMA table_info(items)').fetchall()]
print('cols',cols)
rows=cur.execute('SELECT * FROM items').fetchall()
print('rows',rows)
for r in rows:
    d=dict(zip(cols,r))
    print('d',d)
    name=d.get('name') or d.get('nama') or d.get('nama_barang')
    stock=d.get('stock') or d.get('stok') or 0
    print('name:',name,'stock:',stock)
con.close()
