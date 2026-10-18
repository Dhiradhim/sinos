<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * UI Helper — rollout flag untuk migrasi desain v3 -> v4.
 *
 * Variant diselesaikan sekali per request (static cache) dengan urutan:
 *   1. query string `?ui=v3|v4`  (menang, lalu dipersist ke cookie)
 *   2. environment `SINOS_UI`    (saklar global)
 *   3. cookie `sinos_ui`
 *   4. default: `v4` (desain baru); `?ui=v3` atau `SINOS_UI=v3` untuk rollback
 *
 * Helper ini sengaja dependency-free: tidak memuat library/model apa pun,
 * hanya FCPATH + base_url() milik CI3.
 */

if (!function_exists('ui_variant')) {
    /**
     * Resolve the active UI variant: 'v4' (default) or 'v3' (legacy rollback).
     *
     * Default is 'v4' since 2026-10-17. Use `?ui=v3` (persisted in the
     * `sinos_ui` cookie for 30 days) or the `SINOS_UI=v3` environment
     * variable to revert to the legacy stylesheet.
     *
     * @return string 'v3'|'v4'
     */
    function ui_variant()
    {
        static $variant = null;
        if ($variant !== null) {
            return $variant;
        }

        $allowed   = array('v3', 'v4');
        $requested = null;

        // 1. Query param menang dan dipersist ke cookie.
        if (isset($_GET['ui']) && in_array($_GET['ui'], $allowed, true)) {
            $requested = $_GET['ui'];
            setcookie('sinos_ui', $requested, array(
                'expires'  => time() + 60 * 60 * 24 * 30,
                'path'     => '/',
                'samesite' => 'Lax',
                'httponly' => false,
            ));
            $_COOKIE['sinos_ui'] = $requested;
        }

        // 2. Env override (saklar global).
        if ($requested === null) {
            $env = getenv('SINOS_UI');
            if ($env && in_array($env, $allowed, true)) {
                $requested = $env;
            }
        }

        // 3. Cookie.
        if ($requested === null && isset($_COOKIE['sinos_ui']) && in_array($_COOKIE['sinos_ui'], $allowed, true)) {
            $requested = $_COOKIE['sinos_ui'];
        }

        $variant = $requested ? $requested : 'v4';
        return $variant;
    }
}

if (!function_exists('ui_css_tag')) {
    /**
     * Emit the correct stylesheet <link> for the active variant,
     * cache-busted by filemtime.
     *
     * @param string|null $base Tidak dipakai; dipertahankan untuk kompatibilitas.
     * @return string
     */
    function ui_css_tag($base = null)
    {
        $file = ui_variant() === 'v4' ? 'assets/css/app.v4.css' : 'assets/css/app.css';
        $abs  = FCPATH . $file;
        $v    = file_exists($abs) ? filemtime($abs) : time();

        return '<link rel="stylesheet" href="' . base_url($file) . '?v=' . $v . '">';
    }
}

if (!function_exists('ui_js_tag')) {
    /**
     * Emit the correct Preline runtime <script src> for the active variant.
     *
     * @return string
     */
    function ui_js_tag()
    {
        $file = ui_variant() === 'v4' ? 'assets/js/preline.v5.js' : 'assets/js/preline.js';
        $abs  = FCPATH . $file;
        $v    = file_exists($abs) ? filemtime($abs) : time();

        return '<script src="' . base_url($file) . '?v=' . $v . '"></script>';
    }
}

if (!function_exists('ui_body_class')) {
    /**
     * Extra body classes that let CSS target the variant when both are loaded.
     *
     * @return string 'ui-v3'|'ui-v4'
     */
    function ui_body_class()
    {
        return ui_variant() === 'v4' ? 'ui-v4' : 'ui-v3';
    }
}
