<?php
/*
 * The YouTube video shown in the home page's live Haram block. The link used
 * to be fixed in header.htm, so when YouTube removed that video the block
 * showed "video unavailable" until someone edited the theme. It is now a
 * setting (HARAM_LIVE_VIDEO) and the block is hidden while none is set.
 */

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(403);
    exit;
}

/** The 11-character video id in a YouTube link or bare id, or '' if none. */
function qfa_youtube_id(string $input): string {
    $input = trim($input);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/D', $input)) {
        return $input;
    }
    if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|live/|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/]|$)~i', $input, $m)) {
        return $m[1];
    }
    return '';
}

function qfa_haram_video(): string {
    return defined('HARAM_LIVE_VIDEO') ? qfa_youtube_id((string)HARAM_LIVE_VIDEO) : '';
}
