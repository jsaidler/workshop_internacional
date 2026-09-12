# CMS v3 — deployment and self-updates

The public hosting keeps persistent state outside generated application code:

- `config/local.php`
- `config/install.php`
- `storage/database.sqlite`
- `storage/installed.lock`
- `storage/logs/`
- `storage/updates/`
- `uploads/`

## Initial bootstrap

A hosting installation that predates the self-updater needs one normal deployment of the generated `dist/` directory.

On Windows:

```bat
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
build-dist.cmd
```

Sync the **contents** of `dist/` to the web root while preserving the paths listed above.

`build-dist.cmd` generates `deploy-info.json` and `deploy-manifest.json` together with the application.

## Subsequent updates

The default branch CI publishes a verified production build to the `production-dist` branch. Once the initial bootstrap has installed the updater, go to:

`Admin → Sistema e atualizações`

The updater:

1. reads the production manifest;
2. downloads only changed managed files;
3. verifies SHA-256 checksums;
4. backs up files that will be replaced or removed;
5. replaces application files atomically;
6. never overwrites the database, uploads, local configuration or logs.

Normal editorial changes (pages, design, forms, media, navigation) are written directly by the CMS and require no deployment.
