<?php
/**
 * Plugin Name: MaxLife Post CSS & JavaScript
 * Plugin URI:  https://github.com/mspsys/maxlife-post-scripts
 * Description: The easiest way to build interactive posts in the Classic Editor. Paste your HTML, CSS and JavaScript — from ChatGPT, Gemini or Claude — and publish. Quick, no build tools, no page builder. Lightweight by design: zero extra files on your pages, no jQuery. VS Code-style editor with line numbers and syntax colors.
 * Requires at least: 4.9
 * Requires PHP: 7.0
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version:     1.1.0
 * Author:      MaxLife
 *
 * Writing a post with an interactive piece (e.g. from Gemini, asked for HTML / CSS / JS separately):
 *   - HTML (only what's inside <body>)  → the editor, Code tab
 *   - CSS                               → "Post CSS" box (below the editor)
 *   - JS                                → "Post JavaScript" box
 *   - CDN libraries (e.g. Chart.js)     → "External scripts", one address per line (in the JS box)
 *
 * On the page: the post's CSS goes in <head> (<style id="ml-post-css">); the external scripts load
 * first, in order, then the post's JS — at the end of the page. Nothing here uses jQuery on the site.
 *
 * Editor look: WordPress's own built-in code editor (CodeMirror) with a VS Code-style dark theme —
 * line numbers, colors, bracket matching. If a user turned off "Syntax Highlighting" in their profile,
 * the boxes stay plain text areas.
 *
 * Simple CSS: its per-post CSS is stored in the same place (post meta "_simple_css"), so every existing
 * post keeps its CSS. Deactivate Simple CSS after installing this; while both are active, this plugin
 * leaves CSS to Simple CSS and shows a reminder. Simple CSS's site-wide CSS (if any) keeps printing too.
 *
 * Themes that change pages without a full reload (PJAX / AJAX navigation): copy <style id="ml-post-css">
 * from the new page into <head>. Posts with JavaScript carry a hidden marker, <span data-pjax-full-load hidden>;
 * when the new page has it, load that page normally so its scripts run.
 *
 * 1.1.0 (2026-11-12): Post CSS box (replaces Simple CSS), VS Code-style editor for CSS and JS.
 * 1.0.0 (2026-11-10): Post JavaScript + External scripts.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


const ML_PJS_CODE = '_ml_post_js';
const ML_PJS_EXT  = '_ml_post_js_ext';
const ML_PJS_CSS  = '_simple_css';          // same key as Simple CSS → existing posts carry over

/** Is the old Simple CSS plugin still active? Then it handles CSS (no double box, no double output). */
function ml_pjs_simple_css_active() {
    return function_exists( 'simple_css_generate' );
}

/** Addresses from the External scripts box: one per line, or taken from a pasted <script src="…"> tag */
function ml_pjs_urls( $text ) {
    $urls = [];
    foreach ( preg_split( '/\R/', (string) $text ) as $line ) {
        $line = trim( $line );
        if ( $line === '' || strpos( $line, '#' ) === 0 ) continue;
        if ( preg_match( '#src\s*=\s*["\']([^"\']+)["\']#i', $line, $m ) ) $line = $m[1];
        if ( strpos( $line, '//' ) === 0 ) $line = 'https:' . $line;
        if ( preg_match( '#^https://#i', $line ) ) $urls[] = $line;
    }
    return array_values( array_unique( $urls ) );
}

/** JS box text without <script> wrapper tags, if Gemini included them */
function ml_pjs_clean_code( $code ) {
    $code = preg_replace( '#^\s*<script\b[^>]*>#i', '', (string) $code );
    $code = preg_replace( '#</script>\s*$#i', '', $code );
    return trim( $code );
}

/** CSS box text: drop a <style> wrapper if pasted, and anything that could close the <style> tag */
function ml_pjs_clean_css( $css ) {
    $css = preg_replace( '#^\s*<style\b[^>]*>#i', '', (string) $css );
    $css = preg_replace( '#</style>\s*$#i', '', $css );
    $css = str_ireplace( '</style', '', $css );
    return trim( wp_strip_all_tags( $css ) );
}


/* ── The boxes under the editor ───────────────────────────────────────── */
add_action( 'add_meta_boxes', function () {
    if ( ! current_user_can( 'unfiltered_html' ) ) return;
    foreach ( [ 'post', 'page' ] as $type ) {
        if ( ! ml_pjs_simple_css_active() ) {
            add_meta_box( 'ml-post-css', 'Post CSS', 'ml_pjs_css_metabox', $type, 'normal', 'default' );
        }
        add_meta_box( 'ml-post-js', 'Post JavaScript', 'ml_pjs_metabox', $type, 'normal', 'default' );
    }
} );

function ml_pjs_css_metabox( $post ) {
    wp_nonce_field( 'ml_pjs_css_save', 'ml_pjs_css_nonce' );
    $css = get_post_meta( $post->ID, ML_PJS_CSS, true );
    echo '<p class="ml-code-note">Styles for this post only — printed in the page&#39;s &lt;head&gt;.</p>';
    printf( '<textarea id="ml-pjs-css" class="ml-code" name="ml_pjs_css" rows="14" spellcheck="false" placeholder=".my-chart { max-width: 100%%; }">%s</textarea>', esc_textarea( $css ) );
}

function ml_pjs_metabox( $post ) {
    wp_nonce_field( 'ml_pjs_save', 'ml_pjs_nonce' );
    $ext  = get_post_meta( $post->ID, ML_PJS_EXT, true );
    $code = get_post_meta( $post->ID, ML_PJS_CODE, true );
    echo '<p class="ml-code-label"><b>External scripts</b> — CDN addresses, one per line (loaded first, in this order)</p>';
    printf( '<textarea class="ml-code-plain" name="ml_pjs_ext" rows="3" spellcheck="false" placeholder="https://cdn.jsdelivr.net/npm/chart.js">%s</textarea>', esc_textarea( $ext ) );
    echo '<p class="ml-code-label" style="margin-top:.9em"><b>JavaScript</b> — runs after the post\'s HTML and the scripts above</p>';
    printf( '<textarea id="ml-pjs-js" class="ml-code" name="ml_pjs_code" rows="14" spellcheck="false" placeholder="document.getElementById(\'…\')…">%s</textarea>', esc_textarea( $code ) );
    echo '<p class="ml-code-note">HTML goes in the editor (Code tab), CSS in the Post CSS box. '
       . 'A post with JavaScript always opens in the Code tab so the Visual tab can\'t change its HTML.</p>';
}

add_action( 'save_post', function ( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'unfiltered_html' ) ) return;

    // JavaScript + External scripts
    if ( isset( $_POST['ml_pjs_nonce'] ) && wp_verify_nonce( $_POST['ml_pjs_nonce'], 'ml_pjs_save' ) ) {
        foreach ( [ 'ml_pjs_ext' => ML_PJS_EXT, 'ml_pjs_code' => ML_PJS_CODE ] as $field => $key ) {
            $val = trim( wp_unslash( $_POST[ $field ] ?? '' ) );
            if ( $val === '' ) delete_post_meta( $post_id, $key );
            else update_post_meta( $post_id, $key, wp_slash( $val ) );      // stored as written (admins only)
        }
    }

    // CSS (only when our box was on the screen, i.e. Simple CSS is off)
    if ( isset( $_POST['ml_pjs_css_nonce'] ) && wp_verify_nonce( $_POST['ml_pjs_css_nonce'], 'ml_pjs_css_save' ) ) {
        $css = ml_pjs_clean_css( wp_unslash( $_POST['ml_pjs_css'] ?? '' ) );
        if ( $css === '' ) delete_post_meta( $post_id, ML_PJS_CSS );
        else update_post_meta( $post_id, ML_PJS_CSS, wp_slash( $css ) );
    }
} );


/* ── Editor look: built-in code editor + VS Code-style dark theme (admin only) ── */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
    if ( ! current_user_can( 'unfiltered_html' ) ) return;

    $base = [ 'codemirror' => [ 'lineNumbers' => true, 'lineWrapping' => false, 'indentUnit' => 4, 'tabSize' => 4,
                                'matchBrackets' => true, 'autoCloseBrackets' => true, 'styleActiveLine' => true ] ];
    $css_settings = wp_enqueue_code_editor( array_merge( [ 'type' => 'text/css' ], $base ) );
    $js_settings  = wp_enqueue_code_editor( array_merge( [ 'type' => 'text/javascript' ], $base ) );
    if ( false === $css_settings && false === $js_settings ) return;   // user turned syntax highlighting off

    wp_add_inline_script( 'code-editor', sprintf(
        'document.addEventListener("DOMContentLoaded",function(){var s={css:%s,js:%s};'
      . '[["ml-pjs-css","css"],["ml-pjs-js","js"]].forEach(function(p){var el=document.getElementById(p[0]);'
      . 'if(el&&s[p[1]]&&window.wp&&wp.codeEditor){var ed=wp.codeEditor.initialize(el,s[p[1]]);'
      . 'ed.codemirror.on("change",function(cm){cm.save();});}});});',
        wp_json_encode( $css_settings ), wp_json_encode( $js_settings )
    ) );

    wp_add_inline_style( 'code-editor', ml_pjs_editor_theme() );
} );

/** VS Code "Dark+" colors for the two boxes. */
function ml_pjs_editor_theme() {
    return '
#ml-post-css .CodeMirror, #ml-post-js .CodeMirror {
  height: 380px; border-radius: 6px; border: 1px solid #2b2b2b;
  background: #1e1e1e; color: #d4d4d4;
  font: 13px/1.55 Consolas, "Cascadia Code", Menlo, monospace;
}
#ml-post-css .CodeMirror-gutters, #ml-post-js .CodeMirror-gutters { background: #1e1e1e; border-right: 0; }
#ml-post-css .CodeMirror-linenumber, #ml-post-js .CodeMirror-linenumber { color: #858585; padding: 0 12px 0 8px; }
#ml-post-css .CodeMirror-activeline-background, #ml-post-js .CodeMirror-activeline-background { background: #2a2d2e; }
#ml-post-css .CodeMirror-activeline .CodeMirror-linenumber, #ml-post-js .CodeMirror-activeline .CodeMirror-linenumber { color: #c6c6c6; }
#ml-post-css .CodeMirror-cursor, #ml-post-js .CodeMirror-cursor { border-left: 2px solid #aeafad; }
#ml-post-css .CodeMirror-selected, #ml-post-js .CodeMirror-selected,
#ml-post-css .CodeMirror-focused .CodeMirror-selected, #ml-post-js .CodeMirror-focused .CodeMirror-selected { background: #264f78; }
#ml-post-css .CodeMirror-matchingbracket, #ml-post-js .CodeMirror-matchingbracket { color: #d4d4d4 !important; outline: 1px solid #888; }
#ml-post-css .CodeMirror-placeholder, #ml-post-js .CodeMirror-placeholder { color: #6b6b6b; }

/* CSS */
#ml-post-css .cm-tag, #ml-post-css .cm-qualifier, #ml-post-css .cm-builtin { color: #d7ba7d; }  /* selectors */
#ml-post-css .cm-property { color: #9cdcfe; }
#ml-post-css .cm-atom, #ml-post-css .cm-string, #ml-post-css .cm-keyword { color: #ce9178; }    /* values */
#ml-post-css .cm-number { color: #b5cea8; }
#ml-post-css .cm-def, #ml-post-css .cm-variable-2 { color: #c586c0; }                            /* @media, vars */
#ml-post-css .cm-comment { color: #6a9955; font-style: normal; }

/* JavaScript */
#ml-post-js .cm-keyword { color: #c586c0; }
#ml-post-js .cm-atom { color: #569cd6; }                                                         /* true null */
#ml-post-js .cm-def { color: #dcdcaa; }
#ml-post-js .cm-variable, #ml-post-js .cm-variable-2, #ml-post-js .cm-property { color: #9cdcfe; }
#ml-post-js .cm-variable-3, #ml-post-js .cm-type { color: #4ec9b0; }
#ml-post-js .cm-string, #ml-post-js .cm-string-2 { color: #ce9178; }
#ml-post-js .cm-number { color: #b5cea8; }
#ml-post-js .cm-comment { color: #6a9955; font-style: normal; }
#ml-post-js .cm-operator { color: #d4d4d4; }

/* labels and the plain External scripts box */
.ml-code-label { margin: .2em 0 .3em; }
.ml-code-note { margin: .4em 0 0; color: #50575e; }
.ml-code-plain, textarea.ml-code { width: 100%; font: 12px/1.5 Consolas, Menlo, monospace; tab-size: 4; }
';
}

/* Reminder while Simple CSS is still active */
add_action( 'admin_notices', function () {
    if ( ! ml_pjs_simple_css_active() || ! current_user_can( 'activate_plugins' ) ) return;
    echo '<div class="notice notice-info"><p><b>MaxLife Post CSS &amp; JavaScript:</b> the Post CSS box is ready. '
       . 'Deactivate <b>Simple CSS</b> to switch over — every post keeps its CSS (same storage).</p></div>';
} );


/* ── On the post's page ───────────────────────────────────────────────── */

// CSS in <head>
add_action( 'wp_head', function () {
    if ( ml_pjs_simple_css_active() || ! is_singular() ) return;
    $css = ml_pjs_clean_css( get_post_meta( get_queried_object_id(), ML_PJS_CSS, true ) );
    if ( $css === '' ) return;
    echo "<style id=\"ml-post-css\">\n" . $css . "\n</style>\n";
} );

// Simple CSS's site-wide CSS (Appearance → Simple CSS), if any was saved, keeps printing after deactivation
add_action( 'wp_head', function () {
    if ( ml_pjs_simple_css_active() ) return;
    $opt = get_option( 'simple_css' );
    $css = is_array( $opt ) && ! empty( $opt['css'] ) ? ml_pjs_clean_css( $opt['css'] ) : '';
    if ( $css !== '' ) echo "<style id=\"ml-site-css\">\n" . $css . "\n</style>\n";
}, 8 );

// External scripts, then the post's JS, at the end of the page
add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_singular() ) return;
    $id   = get_queried_object_id();
    $urls = ml_pjs_urls( get_post_meta( $id, ML_PJS_EXT, true ) );
    $code = ml_pjs_clean_code( get_post_meta( $id, ML_PJS_CODE, true ) );
    if ( ! $urls && $code === '' ) return;
    $prev = [];
    foreach ( $urls as $i => $url ) {
        $handle = 'ml-post-ext-' . $i;
        wp_enqueue_script( $handle, $url, $prev, null, true );
        $prev = [ $handle ];
    }
    if ( $code !== '' ) {
        wp_register_script( 'ml-post-js', false, $prev, null, true );
        wp_enqueue_script( 'ml-post-js' );
        wp_add_inline_script( 'ml-post-js', $code );
    }
} );

/* ── A post with JS stays in the Code tab ─────────────────────────────── */
add_filter( 'user_can_richedit', function ( $can ) {
    global $post;
    if ( $can && is_admin() && $post && ( get_post_meta( $post->ID, ML_PJS_CODE, true ) || get_post_meta( $post->ID, ML_PJS_EXT, true ) ) ) return false;
    return $can;
} );

/* ── A post with JS: its HTML is used exactly as pasted ───────────────────
 * WordPress's auto-paragraph step would wrap <canvas>, <input> etc. in <p> / <br>. Turned off for such posts
 * (and back on for the next post in a list). */
add_filter( 'the_content', function ( $content ) {
    $id = get_the_ID();
    $has_js = $id && ( get_post_meta( $id, ML_PJS_CODE, true ) || get_post_meta( $id, ML_PJS_EXT, true ) );
    if ( $has_js ) remove_filter( 'the_content', 'wpautop' );
    elseif ( ! has_filter( 'the_content', 'wpautop' ) ) add_filter( 'the_content', 'wpautop' );
    return $content;
}, 1 );


/* ── PJAX: posts with JS are opened with a normal page load (see js/pjax.js) ── */
add_filter( 'the_content', function ( $content ) {
    if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) return $content;
    $id = get_the_ID();
    if ( $id && ( get_post_meta( $id, ML_PJS_CODE, true ) || get_post_meta( $id, ML_PJS_EXT, true ) ) ) {
        $content .= '<span data-pjax-full-load hidden></span>';
    }
    return $content;
}, 20 );
