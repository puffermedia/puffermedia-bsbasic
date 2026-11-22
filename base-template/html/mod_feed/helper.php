<?php

/**
 * Bootstrap Basic — mod_feed override helper.
 *
 * Joomla's core mod_feed layout always strips <img> tags out of every feed
 * item before display. To show images in a tile/list layout we have to pull
 * the first image out of the raw item content ourselves, before handing the
 * text off to Joomla's stripImages()/truncate() helpers for the intro text.
 */

defined('_JEXEC') or die;

if (!function_exists('bsbasicFeedItemImage')) {
    /**
     * Extract the first <img> src from a feed item's raw HTML, if any.
     *
     * @param   string  $html  Raw item content/description HTML.
     *
     * @return  string|null
     */
    function bsbasicFeedItemImage(string $html): ?string
    {
        if ($html === '') {
            return null;
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            $src = trim($matches[1]);

            // Ignore obvious tracking pixels / spacer gifs.
            if ($src !== '' && stripos($src, '1x1') === false) {
                return $src;
            }
        }

        return null;
    }
}
