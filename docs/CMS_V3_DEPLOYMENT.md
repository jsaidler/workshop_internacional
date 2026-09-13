# CMS v3 — deployment and self-updates

The production hosting keeps persistent state outside generated application code:

- `config/local.php`
- `config/install.php`
- `storage/database.sqlite`
- `storage/installed.lock`
- `storage/logs/`
- `storage/updates/`
- `uploads/`

These paths must never be replaced by a generated package.

## Production branch

The production source branch is:

`wip/form-response-refinement-2026-07-16`

The CI build publishes the approved generated package to the force-updated branch:

`production-dist`

A successful GitHub build does **not** by itself prove that HostGator received the files. FTP deployment only happens when all required repository secrets are configured. Repository state, generated production package and actual hosting deployment must always be treated as three separate states.

## Initial bootstrap

A hosting installation that predates the self-updater needs one normal deployment of the generated `dist/` directory.

In Windows PowerShell, scripts in the current directory require the `./` equivalent ` .\ ` prefix. Use:

```powershell
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
.\build-dist.cmd
```

Do **not** use only `build-dist.cmd` in PowerShell; PowerShell does not execute commands from the current directory by name unless the path is explicit.

Before any deployment containing new migrations, back up:

`storage/database.sqlite`

Then sync the **contents** of `dist/` to the actual web root while preserving every persistent path listed above.

`build-dist.cmd` generates `deploy-info.json` and `deploy-manifest.json` together with the application.

Useful verification after the build:

```powershell
Test-Path .\dist\index.php
Test-Path .\dist\deploy-info.json
Get-Content .\dist\deploy-info.json
```

The first two commands should return `True`.

## Subsequent updates

Once the initial bootstrap has installed the updater, use:

`Admin → Sistema e atualizações`

The updater:

1. reads the production manifest from `production-dist`;
2. downloads only changed managed files;
3. verifies SHA-256 checksums;
4. backs up files that will be replaced or removed;
5. replaces application files atomically;
6. never overwrites the database, uploads, local configuration or logs.

Normal editorial changes to pages, design, forms, media and navigation are written directly by the CMS and require no deployment.

## Current registration migration

The Brazilian registration update uses migration `020_registration_google_form_parity.php`.

It adds to `cms_form_submissions`:

- `payment_status` — default `pending`;
- `payment_confirmed_at`;
- `payment_note`.

It also reapplies the project-specific registration setup so the hosted form matches the canonical Brazilian registration flow. Back up the database before installing a build that introduces this migration.

## Post-update verification

After installing the registration/payment update, verify the real hosted installation, not only the repository:

1. `/inscricao/?lang=pt-br` opens the Brazilian registration page;
2. the form asks only for the canonical Google Form data: name, CPF, WhatsApp, email, Instagram, support size, address, city/UF, CEP, availability, payment method and acceptance;
3. availability shows the four October schedules defined for the current cohort;
4. selecting Pix reveals the R$ 698 payment information, QR code and copy-and-paste code;
5. selecting either card option reveals the Mercado Pago credit-card link;
6. a test submission appears in `Admin → Inscrições` as `Aguardando pagamento`;
7. the admin can enter a payment note and use `Confirmar inscrição e pagamento`;
8. the same record then appears as `Confirmada` with confirmation date/time;
9. the page editor still opens, edits, saves, reloads and publishes without the null `textContent` error previously observed;
10. multiline display headings keep `line-height >= 1`, with `1.02` as the project reference.

If the build is correct locally but the hosted UI remains old, stop rebuilding and diagnose the remote web-root path, stale hosted copy or cache. Do not assume another local build will solve a remote-path problem.
