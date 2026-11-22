<?php

/**
 * @package     Templates.bsbasic
 * @subpackage  mod_feed override — "Tiles" layout
 *
 * Same variables as default.php (see that file for the full list).
 * Select this layout per-module-instance via:
 *   Module edit screen → Advanced tab → Layout → "Tiles"
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
    echo '<div class="alert alert-warning">' . htmlspecialchars($feed, ENT_QUOTES, 'UTF-8') . '</div>';
    return;
}

$maxItems  = (int) $params->get('rssitems', 6);
$showDesc  = (bool) $params->get('rssitemdesc', 1);
$wordCount = (int) $params->get('word_count', 22);
?>
<div class="bsbasic-feed bsbasic-feed--tiles">

    <?php if ($feed->title !== null && $params->get('rsstitle', 1)) : ?>
        <h2 class="bsbasic-feed__title">
            <a href="<?php echo htmlspecialchars($rssurl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                <?php echo htmlspecialchars($feed->title, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h2>
    <?php endif; ?>

    <?php if (!empty($feed)) : ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 bsbasic-feed__grid">
            <?php for ($i = 0, $max = min(count($feed), $maxItems); $i < $max; $i++) : ?>
                <?php
                $uri = $feed[$i]->uri || !$feed[$i]->isPermaLink ? trim($feed[$i]->uri) : trim($feed[$i]->guid);
                $uri = !$uri || stripos($uri, 'http') !== 0 ? $rssurl : $uri;

                $rawText = $feed[$i]->content !== '' ? trim($feed[$i]->content) : '';
                $image   = bsbasicFeedItemImage($rawText);

                $text = OutputFilter::stripImages($rawText);
                $text = $wordCount > 0 ? HTMLHelper::_('string.truncate', $text, $wordCount) : $text;
                $text = str_replace('&apos;', "'", $text);
                ?>
                <div class="col">
                    <div class="card h-100 bsbasic-feed__tile bsbasic-reveal">
                        <?php if ($image) : ?>
                            <a href="<?php echo htmlspecialchars($uri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="bsbasic-feed__tile-image">
                                <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h3 class="card-title h6">
                                <a href="<?php echo htmlspecialchars($uri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    <?php echo htmlspecialchars(trim($feed[$i]->title), ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h3>
                            <?php if ($params->get('rssitemdate', 0) && $feed[$i]->publishedDate !== null) : ?>
                                <div class="text-muted small mb-1">
                                    <?php echo HTMLHelper::_('date', $feed[$i]->publishedDate, Text::_('DATE_FORMAT_LC3')); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($showDesc && $text !== '') : ?>
                                <p class="card-text small text-body-secondary mb-0">
                                    <?php echo $text; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>
