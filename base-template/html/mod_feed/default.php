<?php

/**
 * @package     Templates.bsbasic
 * @subpackage  mod_feed override — "List" layout
 *
 * Available variables (set by mod_feed's Dispatcher::getLayoutData()):
 *   $rssurl   string                    Feed URL
 *   $rssrtl   int                       RTL flag
 *   $feed     \Joomla\CMS\Feed\Feed|string   Parsed feed, or an error string
 *   $params   \Joomla\Registry\Registry Module params
 *   $module   stdClass                  Module record
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Filter\OutputFilter;

require_once __DIR__ . '/helper.php';

if (empty($rssurl)) {
    echo '<div class="alert alert-warning">' . Text::_('MOD_FEED_ERR_NO_URL') . '</div>';
    return;
}

if (!empty($feed) && is_string($feed)) {
    // getFeedInformation() returned an error message string.
    echo '<div class="alert alert-warning">' . htmlspecialchars($feed, ENT_QUOTES, 'UTF-8') . '</div>';
    return;
}

$maxItems    = (int) $params->get('rssitems', 5);
$showDesc    = (bool) $params->get('rssitemdesc', 1);
$wordCount   = (int) $params->get('word_count', 30);
$showThumbs  = true; // this override's whole point — set false to fall back to text-only
?>
<div class="bsbasic-feed bsbasic-feed--list">

    <?php if ($feed->title !== null && $params->get('rsstitle', 1)) : ?>
        <h2 class="bsbasic-feed__title">
            <a href="<?php echo htmlspecialchars($rssurl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                <?php echo htmlspecialchars($feed->title, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h2>
    <?php endif; ?>

    <?php if ($params->get('rssdate', 1) && $feed->publishedDate !== null) : ?>
        <p class="bsbasic-feed__date text-muted small">
            <?php echo HTMLHelper::_('date', $feed->publishedDate, Text::_('DATE_FORMAT_LC3')); ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($feed)) : ?>
        <ul class="bsbasic-feed__items list-group list-group-flush">
            <?php for ($i = 0, $max = min(count($feed), $maxItems); $i < $max; $i++) : ?>
                <?php
                $uri = $feed[$i]->uri || !$feed[$i]->isPermaLink ? trim($feed[$i]->uri) : trim($feed[$i]->guid);
                $uri = !$uri || stripos($uri, 'http') !== 0 ? $rssurl : $uri;

                $rawText = $feed[$i]->content !== '' ? trim($feed[$i]->content) : '';
                $image   = $showThumbs ? bsbasicFeedItemImage($rawText) : null;

                $text = OutputFilter::stripImages($rawText);
                $text = $wordCount > 0 ? HTMLHelper::_('string.truncate', $text, $wordCount) : $text;
                $text = str_replace('&apos;', "'", $text);
                ?>
                <li class="list-group-item bsbasic-feed__item d-flex gap-3 py-3">
                    <?php if ($image) : ?>
                        <div class="bsbasic-feed__item-thumb flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="bsbasic-feed__item-body">
                        <h3 class="bsbasic-feed__item-title h6 mb-1">
                            <a href="<?php echo htmlspecialchars($uri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                <?php echo htmlspecialchars(trim($feed[$i]->title), ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h3>
                        <?php if ($params->get('rssitemdate', 0) && $feed[$i]->publishedDate !== null) : ?>
                            <div class="bsbasic-feed__item-date text-muted small mb-1">
                                <?php echo HTMLHelper::_('date', $feed[$i]->publishedDate, Text::_('DATE_FORMAT_LC3')); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($showDesc && $text !== '') : ?>
                            <div class="bsbasic-feed__item-desc small text-body-secondary">
                                <?php echo $text; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>

</div>
