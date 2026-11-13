=== MaxLife Post CSS & JavaScript ===
Contributors: mspsys
Tags: custom css, custom javascript, classic editor, interactive, code editor
Requires at least: 4.9
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The easiest way to build interactive posts in the Classic Editor. Per-post CSS & JavaScript with a VS Code-style editor. No jQuery.

== Description ==

**The easiest way to build interactive posts in the Classic Editor.**

Paste your HTML, CSS and JavaScript — from ChatGPT, Gemini or Claude — into three boxes and publish. Quick, no build tools, no page builder.

**Lightweight by design:** zero extra files on your pages, no jQuery. Run your post through PageSpeed Insights and see.

**VS Code-style editor** with line numbers and syntax colors.

= How it works =

* **HTML** goes in the editor's Code (Text) tab, as usual.
* **CSS** goes in the *Post CSS* box under the editor. It is printed inline in the page's `<head>` — no extra file to download.
* **JavaScript** goes in the *Post JavaScript* box. It runs at the end of the page, after your HTML.
* **CDN libraries** (Chart.js, D3, …) go in *External scripts*, one address per line. They load first, in order, before your script.

= Made for interactive posts =

* A post with JavaScript always opens in the Code tab, so the Visual tab can't rewrite its HTML.
* Its HTML is used exactly as pasted — WordPress won't add stray `<p>` or `<br>` tags around your `<canvas>`, `<input>` and other elements.
* Pasted `<script>` or `<style>` wrapper tags are removed automatically.

= Switching from Simple CSS =

Per-post CSS is stored in the same place Simple CSS uses, so every existing post keeps its CSS — nothing to copy. Install this plugin, then deactivate Simple CSS. Any site-wide CSS you saved in Simple CSS keeps working too.

= Safe by default =

The boxes are shown only to users who can post unfiltered HTML (administrators). Authors, contributors and guest writers never see them.

= Works with the block editor too =

Made for Classic Editor users, but the CSS and JavaScript boxes also appear as panels under the block editor.

== Frequently Asked Questions ==

= Does it slow my site down? =

The plugin itself adds nothing to your pages: no stylesheet, no script, no jQuery. Only the CSS and JavaScript you write for a post are added, and only on that post. Libraries you load from a CDN cost the same as they would anywhere else.

= I don't see the colored editor. =

WordPress lets each user turn off syntax highlighting (Users → Profile → "Disable syntax highlighting when editing code"). With it off, the boxes are plain text areas and work the same way.

== Changelog ==

= 1.1.0 =
* New: Post CSS box (replaces Simple CSS; existing posts carry over).
* New: VS Code-style editor for the CSS and JavaScript boxes — line numbers, syntax colors, bracket matching.

= 1.0.0 =
* Post JavaScript and External scripts boxes.
