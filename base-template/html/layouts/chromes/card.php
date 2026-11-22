<?php

/**
 * @package     Templates.bsbasic
 * @subpackage  Module chrome override — "card" style
 *
 * Joomla core does not ship a "card" chrome for site templates (it's a
 * Cassiopeia-only convention, defined in its own html/layouts/chromes/).
 * Without this override, style="card" in index.php silently falls through
 * to un-chromed raw module content (ModuleHelper::renderModule() leaves
 * $module->content untouched when the requested chrome layout isn't found).
 *
 * Available variables:
 *   $displayData['module']   stdClass                  Module record (content, title, showtitle, position)
 *   $displayData['params']   \Joomla\Registry\Registry  Module params
 */

defined('_JEXEC') or die;

$module = $displayData['module'];
$params = $displayData['params'];

if (trim((string) $module->content) === '') {
	return;
}

$moduleClassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$showTitle      = $module->showtitle && trim((string) $module->title) !== '';
?>
<div class="<?php echo htmlspecialchars($module->position, ENT_QUOTES, 'UTF-8'); ?> card bsbasic-reveal<?php echo $moduleClassSfx; ?>">
	<?php if ($showTitle) : ?>
	<h3 class="card-header"><?php echo htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8'); ?></h3>
	<?php endif; ?>
	<div class="card-body">
		<?php echo $module->content; ?>
	</div>
</div>
