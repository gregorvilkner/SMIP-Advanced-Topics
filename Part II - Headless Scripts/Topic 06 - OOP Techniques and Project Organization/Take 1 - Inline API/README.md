# Take 1 — Inline API in Browser Script

The bare-bones starting point. A tiny PHP "API" with a `switch($f)` over `function` / `argument` std_inputs, consumed from a browser script that hand-builds a `FormData` and POSTs to `/index.php?option=com_thinkiq&task=invokeScript`.

## Files

- `01 API Template.php` — server-side dispatch. Reads `$f` and `$a` from `$context->std_inputs`, runs the matching case, returns JSON.
- `02 Consume API from Browser Script.html` — browser-side caller with all the fetch boilerplate inline.

## What this take is good for

It's the version every SMIP project starts with: the first time you need server-side data, you write the call inline. The pain shows up the second time — every consumer page repeats the same fetch boilerplate. That's what the next takes set out to fix.

## Try it in your SMIP

`library_export.json` in this folder is an importable SMIP library named `api_demo_take_1`. Import it (Settings → Libraries → Import), then open the "Consume API from Browser Script" script in the model editor.

See the [topic README](../README.md) for the full four-take progression.
