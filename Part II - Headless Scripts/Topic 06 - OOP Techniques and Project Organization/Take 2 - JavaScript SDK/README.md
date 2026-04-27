# Take 2 — Extracted JavaScript SDK

Pulls the fetch boilerplate out of the browser script and into a named SDK object. The browser script now reads like business logic; the round-trip plumbing lives in one file that all consumers share.

## Files

- `00 Guzzle Client.php` — small wrapper around `GuzzleHttp\Client` for hitting third-party REST APIs from PHP. Lives next to the API so multiple endpoints can share it.
- `01 API Template.php` — server-side dispatch (same shape as Take 1).
- `02 JavaScript SDK.html` — the new piece. Exposes `ApiDemoSdk.GetLibraryNamesAsync()` etc., one method per API endpoint. Loaded via `Script::includeScript('api_demo.api_demo__hyphen__javascript_sdk')`.
- `03 JS SDK Documentation.html` — Vue page rendering one section per SDK method. Doubles as docs and a live test harness.

## What changes from Take 1

The fetch boilerplate moves into the SDK file — one place, reusable across consumer pages. The doc page hand-codes a section per method, which is the pain point Take 4 eventually fixes.

## Try it in your SMIP

`library_export.json` in this folder is an importable SMIP library named `api_demo_take_2`. Import it (Settings → Libraries → Import), then open the "JS SDK Documentation" script.

See the [topic README](../README.md) for the full four-take progression.
