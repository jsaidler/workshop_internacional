# Deploy on HostGator

Requires Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, and HTTPS. Synchronize the **contents** of `dist/` directly to the domain document root (`remotePath: "/"`); do not create or upload a ZIP.

1. Copy `dist/config/install.example.php` to `config/install.php` on the server and set a long random `install_key`.
2. Make `storage/` and `config/` writable by the PHP user during installation (normally 775); afterward only `storage/` needs write access.
3. Configure the FTP client as upload/update only for persistent paths: never overwrite or delete `config/local.php`, `config/install.php`, `storage/database.sqlite`, `storage/logs/`, or `uploads/`.
4. Open `/install/`, enter the installation key, configure the site, and create the first administrator.
5. Delete `config/install.php` after the installer confirms completion.
6. Submit the public form, sign in at `/admin/login.php`, and test CSV export.
7. Enable HTTPS at HostGator; secure cookies activate automatically.

`dist/` intentionally contains no development database, logs, credentials, editor, preview, tests, captures, or deployment archive. SQLite initializes safely on its first request.
