import sqlite3,os,sys

db=r'C:\\xampp\\htdocs\\webStokGrosir\\data.db'
if not os.path.exists(db):
    print('MISSING')
    sys.exit(0)

con=sqlite3.connect(db)
cur=con.cursor()
cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
tables=[r[0] for r in cur.fetchall()]
if not tables:
    print('NO_TABLES')
    sys.exit(0)
for t in tables:
    try:
        query='SELECT COUNT(*) FROM "{}"'.format(t)
        c=cur.execute(query).fetchone()[0]
    except Exception as e:
        c='ERR:'+str(e)
    print('{}:{}'.format(t,c))
con.close()
