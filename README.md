# Direct Positive Workshop — clean start

This package contains only the approved static landing page and the content contract that a future visual editor will manipulate.

## Scope

Included:

- the approved page split into HTML, CSS and JavaScript;
- stable `data-section`, `data-editable-text`, `data-editable-image`, `data-editable-video` and `data-editable-form` markers;
- a structured default-content JSON;
- a JSON Schema for validation;
- local preview media;
- the untouched original approved HTML for comparison.

Not included:

- CMS;
- PHP;
- database;
- login;
- administration;
- visual editor;
- uploads;
- publication workflow.

## Files

- `template/index.html`: working static page.
- `template/page.css`: approved visual design.
- `template/page.js`: theme switch and prototype form behavior.
- `template/schema.json`: validation contract for editable content.
- `data/default-content.json`: initial content represented as data.
- `assets/media/`: local preview media.
- `reference/approved-page-original.html`: untouched original page.

## Preview

Serve the project root through a local HTTP server. From the project directory:

```bash
python -m http.server 8080
```

Open:

```text
http://localhost:8080/template/
```

The next phase may build an editor against `template/index.html` and `data/default-content.json`, without changing the page structure or design.
