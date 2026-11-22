<?php

/**
 * @package     Templates.bsbasic
 * @subpackage  mod_custom override — "Banner" layout (home page hero)
 *
 * Same layout name as Cassiopeia's, so a Custom module already set to
 * "Banner" (e.g. by sample data) keeps working when the template is
 * switched. Differs from Cassiopeia in the markup classes (bsbasic-hero) and
 * in using the module's Background Image as a CSS custom property that
 * scss/_hero.scss consumes; text colour, scrim and height live there.
 *
 * Available variables (set by mod_custom's Dispatcher::getLayoutData()):
 *   $module  stdClass Module record ($module->content is the editor text)
 *   $params  \Joomla\Registry\Registry  Module params (backgroundimage)
 *   $app     \Joomla\CMS\Application\CMSApplicationInterface
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$modId = 'mod-custom' . $module->id;
$hasImage = (bool) $params->get('backgroundimage');

if ($hasImage) {
    /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
    $wa = $app->getDocument()->getWebAssetManager();
    $imageUrl = Uri::root(true) . '/' . HTMLHelper::_('cleanImageURL', $params->get('backgroundimage'))->url;
    $wa->addInlineStyle(
        '#' . $modId . '{--bsbasic-hero-image: url("' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '");}',
        ['name' => $modId]
    );
}
?>

<div class="mod-custom custom bsbasic-hero<?php echo $hasImage ? ' has-image' : ''; ?>" id="<?php echo $modId; ?>">
    <div class="bsbasic-hero__overlay">
        <div class="bsbasic-hero__content">
            <?php echo $module->content; ?>
        </div>
    </div>
</div>
