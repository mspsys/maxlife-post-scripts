# MaxLife Post CSS & JavaScript

**The easiest way to build interactive posts in the Classic Editor.**

Paste your HTML, CSS and JavaScript (from ChatGPT, Gemini or Claude) into three boxes and publish. Quick, no build tools, no page builder.

- **Lightweight by design:** zero extra files on your pages, no jQuery. Run your post through [PageSpeed Insights](https://pagespeed.web.dev/) and see.
- **VS Code-style editor** with line numbers, syntax colors and bracket matching, using WordPress's own built-in code editor.
- **Made for interactive posts:** charts, calculators, simulators. Load libraries such as Chart.js or D3 from a CDN.

## How it works

| What | Where it goes | Where it ends up on the page |
|---|---|---|
| HTML | The editor's **Code** (Text) tab, as usual | In the post |
| CSS | The **Post CSS** box under the editor | Inline in `<head>`, so there's no extra file |
| CDN libraries | **External scripts**, one address per line | End of the page, in order |
| JavaScript | The **Post JavaScript** box | End of the page, after the libraries |

A post with JavaScript always opens in the Code tab, and its HTML is used exactly as pasted. WordPress won't wrap your `<canvas>` or `<input>` in stray `<p>` and `<br>` tags. If you paste `<script>` or `<style>` wrapper tags, they're removed for you.

## Switching from Simple CSS

Per-post CSS is stored in the same place Simple CSS uses, so every existing post keeps its CSS and there's nothing to copy. Install this plugin, then deactivate Simple CSS. Any site-wide CSS you saved in Simple CSS keeps working too.

## Safe by default

The boxes appear only for users who can post unfiltered HTML (administrators). Authors, contributors and guest writers never see them.

## Install

1. Download the zip from **[Releases](../../releases)**.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the zip, then **Activate**.

Requires WordPress 4.9+ and PHP 7.0+. It works best with the Classic Editor; in the block editor the boxes appear as panels under the editor.

## For theme authors: PJAX / AJAX navigation

If your theme changes pages without a full reload:

- copy `<style id="ml-post-css">` from the new page into `<head>`;
- when the new page contains `<span data-pjax-full-load hidden>`, load it normally instead, so its scripts run.

## License

GPLv2 or later. See [LICENSE](LICENSE).
