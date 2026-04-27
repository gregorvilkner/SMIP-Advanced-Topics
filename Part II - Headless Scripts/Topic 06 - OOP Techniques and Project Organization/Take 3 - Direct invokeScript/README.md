# Take 3 — Direct `invokeScriptAsync`

Asks a simple question: do we even need the SDK shim if `tiqJSHelper.invokeScriptAsync` already provides the round-trip? For small APIs with one or two consumers, the answer is "no". Three files instead of four.

Notice the missing `02` in the file numbering — that gap *is* the change from Take 2. The JavaScript SDK file is gone.

## Files

- `00 Guzzle Client.php` — same as Take 2.
- `01 API Template.php` — same dispatch shape, but the std_input convention is renamed from `function` / `argument` to the more generic `method` / `data`.
- `03 API Documentation.html` — the doc page now calls `tiqJSHelper.invokeScriptAsync(apiFileName, 'Echo', arg)` directly instead of going through an SDK shim.

## What changes from Take 2

SDK shim removed. The doc page is still hand-coded section by section — that's the next take's problem.

The SDK shim earns its keep when the same endpoints are called from many pages, or when IDE autocomplete on method names is worth the extra file. For a one-page demo, it isn't.

## Try it in your SMIP

`library_export.json` in this folder is an importable SMIP library named `api_demo_take_3`. Import it (Settings → Libraries → Import), then open the "API Documentation" script.

See the [topic README](../README.md) for the full four-take progression.
