<?php

/**
 * @package     Templates.bsbasic
 * @subpackage  mod_menu override — "Default" layout
 *
 * Identical to Joomla core's modules/mod_menu/tmpl/default.php. Exists so
 * this template has its own entry in every menu module's "Alternative
 * Layout" dropdown (e.g. "puffermedia-bsbasic - Default"), alongside
 * whatever other installed templates provide (Cassiopeia ships its own
 * "Collapsible Dropdown" / "Dropdown Metismenu" overrides there too —
 * Joomla's layout picker lists options from every installed template, not
 * just the active one). Picking one of those instead of core's plain list
 * produces markup our CSS-only hover/focus dropdown (scss/_header.scss)
 * was never built for. This gives admins an unambiguous, correctly
 * labelled choice instead of relying on remembering to pick "Default".
 *
 * Available variables (set by mod_menu's Dispatcher::getLayoutData()):
 *   $list        array    Flattened, pre-ordered menu items (deeper/shallower/level_diff)
 *   $params      \Joomla\Registry\Registry  Module params
 *   $module      stdClass Module record
 *   $default_id  int      Id of the default (Home) menu item
 *   $active_id   int      Id of the active menu item
 *   $path        array    Ids of items in the active item's ancestor path
 *   $class_sfx   string   Deprecated Joomla core variable, kept for parity with core's layout
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_menu');
$wa->usePreset('mod_menu.menu');

$tagId      = $params->get('tag_id', '') ?: 'mod-menu' . $module->id;
$id         = ' id="' . htmlspecialchars($tagId, ENT_QUOTES, 'UTF-8') . '"';
?>
<ul<?php echo $id; ?> class="mod-menu mod-list nav <?php echo $class_sfx; ?>">
<?php foreach ($list as $i => &$item) {
	$itemParams = $item->getParams();
	$class      = 'nav-item item-' . $item->id;

	if ($item->id == $default_id) {
		$class .= ' default';
	}

	if ($item->id == $active_id || ($item->type === 'alias' && $itemParams->get('aliasoptions') == $active_id)) {
		$class .= ' current';
	}

	if (in_array($item->id, $path)) {
		$class .= ' active';
	} elseif ($item->type === 'alias') {
		$aliasToId = $itemParams->get('aliasoptions');

		if (count($path) > 0 && $aliasToId == $path[count($path) - 1]) {
			$class .= ' active';
		} elseif (in_array($aliasToId, $path)) {
			$class .= ' alias-parent-active';
		}
	}

	if ($item->type === 'separator') {
		$class .= ' divider';
	}

	if ($item->deeper) {
		$class .= ' deeper';
	}

	if ($item->parent) {
		$class .= ' parent';
	}

	echo '<li class="' . $class . '">';

	// The next item is deeper - add toggle only here it is a heading or separator
	if ($item->deeper && in_array($item->type, ['separator', 'heading'])) {
		echo '<button class="mod-menu__toggle-sub" aria-expanded="false">';
	}

	switch ($item->type) :
		case 'separator':
		case 'component':
		case 'heading':
		case 'url':
			require ModuleHelper::getLayoutPath('mod_menu', 'default_' . $item->type);
			break;

		default:
			require ModuleHelper::getLayoutPath('mod_menu', 'default_url');
			break;
	endswitch;

	// The next item is deeper.
	if ($item->deeper) {
		switch ($item->type) {
			case 'heading':
			case 'separator':
				echo '<span class="icon-chevron-down" aria-hidden="true">' .
					'</span></button>';
				break;

			default:
				echo '<button class="mod-menu__toggle-sub" aria-expanded="false">' .
				'<span class="icon-chevron-down" aria-hidden="true"></span>' .
				'<span class="visually-hidden">' . Text::sprintf('MOD_MENU_TOGGLE_SUBMENU_LABEL', $item->title) . '</span>' .
				'</button>';
		}
		echo '<ul class="mod-menu__sub list-unstyled small">';
	} elseif ($item->shallower) {
		// The next item is shallower.
		echo '</li>';
		echo str_repeat('</ul></li>', $item->level_diff);
	} else {
		// The next item is on the same level.
		echo '</li>';
	}
}
?></ul>
