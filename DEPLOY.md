# Deploy on HostGator

Requires Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, and HTTPS. Upload only `dist/workshop-public.zip` through SFTP into the domain document root.

1. Copy `config/local.example.php` to `config/local.php`; set long unique secrets and a temporary `setup_key`.
2. Make only `storage/` writable by the PHP user (normally 775).
3. Open `/admin/setup.php`, enter the configured key, and create the first administrator.
4. Remove `setup_key` from `config/local.php`.
5. Submit the public form, sign in at `/admin/login.php`, and test CSV export.
6. Enable HTTPS at HostGator; secure cookies activate automatically.

SQLite initializes safely on its first request. Do not upload the development database, logs, credentials, editor, preview, tests, or captures.
