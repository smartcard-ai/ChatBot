import certifi
import databricks.sql

with databricks.sql.connect(
    server_hostname="dbc-320b5ad3-912f.cloud.databricks.com",
    http_path="/sql/1.0/warehouses/b9fe55bed5289cbd",
    access_token="dapi17521753e0832872600ea4439f2d5f85",
    _is_ssl=True,
    _tls_cert_file=certifi.where()   # ✅ Use certifi CA bundle
) as connection:
    cursor = connection.cursor()
    cursor.execute("SHOW TABLE")
    print(cursor.fetchall())

# token;- dapi17521753e0832872600ea4439f2d5f85
# hostname:- dbc-320b5ad3-912f.cloud.databricks.com
# wearhouse:- /sql/1.0/warehouses/b9fe55bed5289cbd

