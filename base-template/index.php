<?php
/**
 * Bootstrap Basic — a straightforward Bootstrap 5 front-end template for Joomla 6.
 *
 * Module positions (match core Cassiopeia, so switching templates keeps your
 * existing module assignments):
 *   topbar, below-top, menu, search, banner, top-a, top-b, main-top,
 *   main-bottom, breadcrumbs, sidebar-left, sidebar-right, bottom-a,
 *   bottom-b, footer, error-403, error-404, debug
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */

if (!function_exists('bsbasicShade')) {
	/**
	 * Lighten/darken a hex color by a percentage. Negative = darker, positive = lighter.
	 */
	function bsbasicShade(string $hex, float $percent): string
	{
		$hex = ltrim($hex, '#');

		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
			return '#' . $hex;
		}

		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));

		$r = max(0, min(255, (int) round($r + ($r * $percent / 100))));
		$g = max(0, min(255, (int) round($g + ($g * $percent / 100))));
		$b = max(0, min(255, (int) round($b + ($b * $percent / 100))));

		return sprintf('#%02x%02x%02x', $r, $g, $b);
	}
}

if (!function_exists('bsbasicHexRgb')) {
	/**
	 * "#rrggbb" or "#rgb" as [r, g, b], or null when it is not a hex color.
	 */
	function bsbasicHexRgb(string $hex): ?array
	{
		$hex = ltrim(trim($hex), '#');

		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
			return null;
		}

		return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
	}

	/**
	 * Sass mix(): $weight percent of $with mixed into $rgb, as [r, g, b].
	 */
	function bsbasicMix(array $rgb, array $with, float $weight): array
	{
		$w = $weight / 100;

		return array_map(
			static fn ($c, $m) => (int) round($m * $w + $c * (1 - $w)),
			$rgb,
			$with
		);
	}

	function bsbasicHex(array $rgb): string
	{
		return vsprintf('#%02x%02x%02x', $rgb);
	}

	/**
	 * Text color for a background, by WCAG contrast (Bootstrap's color-contrast()).
	 */
	function bsbasicContrast(array $rgb): array
	{
		$lin = array_map(static function ($c) {
			$c /= 255;

			return $c <= .04045 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4;
		}, $rgb);
		$lum = .2126 * $lin[0] + .7152 * $lin[1] + .0722 * $lin[2];

		// Bootstrap's minimum ratio for white text is 4.5.
		return (1.05 / ($lum + .05)) >= 4.5 ? [255, 255, 255] : [0, 0, 0];
	}

	/**
	 * CSS for one Bootstrap theme color: the --bs-* variables (into $vars) and the
	 * rules the precompiled bootstrap.min.css hard-codes per color (returned).
	 */
	function bsbasicThemeColor(string $name, array $rgb, string &$vars, bool $buttons = true): string
	{
		$white    = [255, 255, 255];
		$black    = [0, 0, 0];
		$text     = bsbasicContrast($rgb);
		$darkText = $text === $black;
		$list     = implode(',', $rgb);
		$linkHov  = implode(',', bsbasicMix($rgb, $black, 20));

		$vars .= '--bs-' . $name . ':' . bsbasicHex($rgb) . ';--bs-' . $name . '-rgb:' . $list . ';';

		if (!in_array($name, ['light', 'dark'], true)) {
			$vars .= '--bs-' . $name . '-text-emphasis:' . bsbasicHex(bsbasicMix($rgb, $black, 60)) . ';'
				. '--bs-' . $name . '-bg-subtle:' . bsbasicHex(bsbasicMix($rgb, $white, 80)) . ';'
				. '--bs-' . $name . '-border-subtle:' . bsbasicHex(bsbasicMix($rgb, $white, 60)) . ';';
		}

		$css = '.text-bg-' . $name . '{color:' . bsbasicHex($text) . '!important;}'
			. '.link-' . $name . ':focus,.link-' . $name . ':hover{'
			. 'color:RGBA(' . $linkHov . ',var(--bs-link-opacity,1))!important;'
			. 'text-decoration-color:RGBA(' . $linkHov . ',var(--bs-link-underline-opacity,1))!important;}';

		if (!$buttons) {
			return $css;
		}

		// Same rule as Bootstrap: dark-text colors lighten on hover, the rest darken.
		$toward = $darkText ? $white : $black;
		$hex    = bsbasicHex($rgb);
		$txt    = bsbasicHex($text);
		$hover  = bsbasicHex(bsbasicMix($rgb, $toward, 15));
		$hovBdr = bsbasicHex(bsbasicMix($rgb, $toward, $darkText ? 10 : 20));
		$active = bsbasicHex(bsbasicMix($rgb, $toward, 20));
		$actBdr = bsbasicHex(bsbasicMix($rgb, $toward, $darkText ? 10 : 25));
		$focus  = implode(',', bsbasicMix($rgb, $text, 15));

		$css .= '.btn-' . $name . '{--bs-btn-color:' . $txt . ';--bs-btn-bg:' . $hex . ';--bs-btn-border-color:' . $hex . ';'
			. '--bs-btn-hover-color:' . $txt . ';--bs-btn-hover-bg:' . $hover . ';--bs-btn-hover-border-color:' . $hovBdr . ';'
			. '--bs-btn-focus-shadow-rgb:' . $focus . ';'
			. '--bs-btn-active-color:' . $txt . ';--bs-btn-active-bg:' . $active . ';--bs-btn-active-border-color:' . $actBdr . ';'
			. '--bs-btn-disabled-color:' . $txt . ';--bs-btn-disabled-bg:' . $hex . ';--bs-btn-disabled-border-color:' . $hex . ';}'
			. '.btn-outline-' . $name . '{--bs-btn-color:' . $hex . ';--bs-btn-border-color:' . $hex . ';'
			. '--bs-btn-hover-color:' . $txt . ';--bs-btn-hover-bg:' . $hex . ';--bs-btn-hover-border-color:' . $hex . ';'
			. '--bs-btn-focus-shadow-rgb:' . $list . ';'
			. '--bs-btn-active-color:' . $txt . ';--bs-btn-active-bg:' . $hex . ';--bs-btn-active-border-color:' . $hex . ';'
			. '--bs-btn-disabled-color:' . $hex . ';--bs-btn-disabled-border-color:' . $hex . ';}';

		return $css;
	}

	/**
	 * .table-{name} the way Bootstrap's table-variant() builds it: a tinted background
	 * (Light and Dark use the color itself) and striped/hover/active/border shades
	 * mixed toward the contrasting text color.
	 */
	function bsbasicTableVariant(string $name, array $rgb): string
	{
		$bg   = in_array($name, ['light', 'dark'], true) ? $rgb : bsbasicMix($rgb, [255, 255, 255], 80);
		$text = bsbasicContrast($bg);
		$txt  = bsbasicHex($text);

		return '.table-' . $name . '{--bs-table-color:' . $txt . ';--bs-table-bg:' . bsbasicHex($bg) . ';'
			. '--bs-table-border-color:' . bsbasicHex(bsbasicMix($bg, $text, 20)) . ';'
			. '--bs-table-striped-bg:' . bsbasicHex(bsbasicMix($bg, $text, 5)) . ';--bs-table-striped-color:' . $txt . ';'
			. '--bs-table-active-bg:' . bsbasicHex(bsbasicMix($bg, $text, 10)) . ';--bs-table-active-color:' . $txt . ';'
			. '--bs-table-hover-bg:' . bsbasicHex(bsbasicMix($bg, $text, 7.5)) . ';--bs-table-hover-color:' . $txt . ';}';
	}

	/**
	 * .list-group-item-{name} from one color, with the same subtle/emphasis shades as
	 * bsbasicThemeColor(). Scoped to the list group so the global --bs-* set is untouched.
	 */
	function bsbasicListGroupVariant(string $name, array $rgb): string
	{
		$emphasis = bsbasicHex(bsbasicMix($rgb, [0, 0, 0], 60));
		$bg       = bsbasicHex(bsbasicMix($rgb, [255, 255, 255], 80));
		$border   = bsbasicHex(bsbasicMix($rgb, [255, 255, 255], 60));

		return '.list-group-item-' . $name . '{--bs-list-group-color:' . $emphasis . ';--bs-list-group-bg:' . $bg . ';'
			. '--bs-list-group-border-color:' . $border . ';'
			. '--bs-list-group-action-hover-bg:' . $border . ';--bs-list-group-action-active-bg:' . $border . ';'
			. '--bs-list-group-active-color:' . $bg . ';--bs-list-group-active-bg:' . $emphasis . ';'
			. '--bs-list-group-active-border-color:' . $emphasis . ';}';
	}
}

$app    = Factory::getApplication();
$wa     = $this->getWebAssetManager();
$params = $this->params;

// Use the Bootstrap 5 build that ships with Joomla core itself — no extra
// framework download, and it stays in sync with whatever version Joomla bundles.
$wa->registerAndUseStyle('tpl.bsbasic.bootstrap', 'media/vendor/bootstrap/css/bootstrap.min.css')
	->useStyle('tpl.bsbasic.template');

// Mobile Menu: Bootstrap collapse (default) or an offcanvas panel from either side.
$mobileMenu = (string) $params->get('mobileMenu', 'collapse');
$offcanvas  = in_array($mobileMenu, ['offcanvas-end', 'offcanvas-start'], true);

// Joomla ships Bootstrap's scripts as separate assets, not as a bundle. Collapse
// or Offcanvas drives the hamburger button; the drop-down menu is CSS-only.
$wa->useScript($offcanvas ? 'bootstrap.offcanvas' : 'bootstrap.collapse');

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');

$isBoxed       = ($params->get('layoutWidth', 'fluid') === 'boxed');
// Boxed From: Bootstrap's responsive containers stay fluid below their breakpoint.
$boxedFrom     = (string) $params->get('boxedFrom', 'container');

if (!in_array($boxedFrom, ['container', 'container-md', 'container-lg', 'container-xl', 'container-xxl'], true)) {
	$boxedFrom = 'container';
}

$container     = $isBoxed ? $boxedFrom : 'container-fluid';
$pageclassSfx  = trim((string) $params->get('pageclass_sfx', ''));
$customHead    = trim((string) $params->get('customCodeHead', ''));
$customCss     = trim((string) $params->get('customCss', ''));
$collapseBp    = (string) $params->get('collapseBreakpoint', 'lg');
// Menu Alignment (Bootstrap margin utility) and Menu Style (header modifier).
$menuAligns    = ['end' => 'ms-auto', 'center' => 'mx-auto', 'start' => 'me-auto'];
$menuAlign     = $menuAligns[(string) $params->get('menuAlign', 'end')] ?? 'ms-auto';
$menuStyle     = (string) $params->get('menuStyle', 'template');
$menuStyle     = in_array($menuStyle, ['pills', 'underline'], true) ? $menuStyle : '';
$customBodyEnd = trim((string) $params->get('customCodeBodyEnd', ''));

$logoImage = ltrim(trim((string) $params->get('logoImage', '')), '/');
$logoAlt   = trim((string) $params->get('logoAlt', ''));
$tagline   = trim((string) $params->get('tagline', ''));

if ($logoAlt === '') {
	$logoAlt = $app->get('sitename');
}

$copyrightText = trim((string) $params->get('copyrightText', '© {year} {sitename}'));

if ($copyrightText !== '') {
	$copyrightText = str_replace(
		['{year}', '{sitename}'],
		[date('Y'), $app->get('sitename')],
		$copyrightText
	);
}

$primaryColor = trim((string) $params->get('primaryColor', ''));
$accentColor  = trim((string) $params->get('accentColor', ''));
$linkColor    = trim((string) $params->get('linkColor', ''));
$bodyBgColor  = trim((string) $params->get('bodyBgColor', ''));
$containerBg  = trim((string) $params->get('containerBgColor', ''));
// With a Container Background Color one wrapper carries the container class
// (top-a to bottom-b), so the bands inside must not add a second one.
$inner        = $containerBg !== '' ? '' : $container;
$containerPad = (string) $params->get('containerPadding', 'default');
$borderRadius = (string) $params->get('borderRadius', 'soft');
$contentIndent = (string) $params->get('contentIndent', 'default');
$headingColor = trim((string) $params->get('headingColor', ''));
$headerLinkColor = trim((string) $params->get('headerLinkColor', ''));
$footerLinkColor = trim((string) $params->get('footerLinkColor', ''));
$bodyFont     = (string) $params->get('bodyFont', 'default');
$headingFont  = (string) $params->get('headingFont', 'default');
$stickyHeader = (bool) $params->get('stickyHeader', 0);
$backToTop    = (bool) $params->get('backToTop', 1);

$headerScheme = (string) $params->get('headerScheme', 'light');
$footerColor  = (string) $params->get('footerColor', 'dark');
$boxedWidth   = (int) $params->get('boxedMaxWidth', 1320);
$readingWidth = (string) $params->get('readingWidth', 'default');
$sidebarWidth = (int) $params->get('sidebarWidth', 3);
$headerSpacing = (string) $params->get('headerSpacing', 'default');
$headerShadow = (string) $params->get('headerShadow', 'subtle');
$bttPosition  = (string) $params->get('backToTopPosition', 'right');
$bttThreshold = (int) $params->get('backToTopThreshold', 300);
$stickyMode   = (string) $params->get('stickyBehavior', 'fixed');
$bandWidth    = (string) $params->get('bandWidth', 'contained');
$sidebarMenu  = ((string) $params->get('sidebarMenu', 'standard') === 'collapsible');
$animHover    = (string) $params->get('animHover', 'lift');
$animEntrance = (string) $params->get('animEntrance', 'none');
$animTargets  = (string) $params->get('animTargets', 'both');
$animSpeed    = max(200, min(2000, (int) $params->get('animSpeed', 600)));
// Gap between cards revealed together: 80ms at the default 600ms.
$animStep     = (int) round($animSpeed * 2 / 15);
$animStagger  = (bool) $params->get('animStagger', 1);
$animBlogHover = ((string) $params->get('animBlog', 'entrance') === 'entrance-hover');

if (!in_array($animHover, ['none', 'lift', 'zoom', 'lift-zoom'], true)) {
	$animHover = 'lift';
}

if (!in_array($animEntrance, ['fade', 'slide-up', 'zoom-in'], true)) {
	$animEntrance = '';
}

if (!in_array($animTargets, ['both', 'cards', 'sections'], true)) {
	$animTargets = 'both';
}

if (!in_array($headerScheme, ['light', 'dark', 'primary', 'accent'], true)) {
	$headerScheme = 'light';
}

if (!in_array($footerColor, ['dark', 'light', 'primary', 'accent'], true)) {
	$footerColor = 'dark';
}

// 1320 is Bootstrap's own xxl container, so only other widths need a rule.
if (!in_array($collapseBp, ['md', 'lg', 'xl'], true)) {
	$collapseBp = 'lg';
}

if (!in_array($bttThreshold, [100, 300, 600, 1000], true)) {
	$bttThreshold = 300;
}

if (!in_array($stickyMode, ['fixed', 'shrink', 'autohide'], true)) {
	$stickyMode = 'fixed';
}

// Top bar, header and footer: Full width frees them from the boxed container.
$bandContainer = ($isBoxed && $bandWidth === 'full') ? 'container-fluid' : $container;

if (!in_array($sidebarWidth, [2, 3, 4], true)) {
	$sidebarWidth = 3;
}

// Grid: breakpoint where the sidebars stop stacking, and the row gutters.
$stackBp      = (string) $params->get('sidebarStack', 'lg');
$contentFirst = (bool) $params->get('contentFirst', 0);
$gridGutter   = (string) $params->get('gridGutter', 'default');

if (!in_array($stackBp, ['md', 'lg', 'xl'], true)) {
	$stackBp = 'lg';
}

if (!in_array($gridGutter, ['0', '1', '2', '3', '4', '5'], true)) {
	$gridGutter = '';
}

// Extra Classes (Bootstrap tab): class names only, so nothing can leave the attribute.
$extraCls = static function (string $name) use ($params): string {
	$value = preg_replace('/[^A-Za-z0-9_:\-\s]/', '', (string) $params->get($name, ''));
	$value = trim((string) preg_replace('/\s+/', ' ', (string) $value));

	return $value !== '' ? ' ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
};

if (!in_array($boxedWidth, [1140, 1320, 1440, 1600], true)) {
	$boxedWidth = 1320;
}

$fontStacks = [
	'classic-sans' => 'Helvetica, Arial, sans-serif',
	'humanist'     => '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
	'serif'        => 'Georgia, "Times New Roman", Times, serif',
	'monospace'    => '"SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace',
];

$rootVars = '';

if ($primaryColor !== '') {
	$rootVars .= '--bsbasic-primary:' . $primaryColor . ';';
}

if ($accentColor !== '') {
	$accentDark = bsbasicShade($accentColor, -15);
	$rootVars  .= '--bsbasic-accent:' . $accentColor . ';--bsbasic-accent-dark:' . $accentDark . ';';
}

if ($linkColor !== '') {
	$rootVars .= '--bsbasic-link:' . $linkColor . ';';
}

if ($bodyBgColor !== '') {
	$rootVars .= '--bsbasic-paper:' . $bodyBgColor . ';';
}

if ($containerBg !== '') {
	$rootVars .= '--bsbasic-container-bg:' . $containerBg . ';';

	// "default" (1.5rem) is the fallback in scss/_layout.scss, so it emits nothing.
	$containerPads = ['none' => '0', 'small' => '2.5rem', 'large' => '5rem'];

	if (isset($containerPads[$containerPad])) {
		$rootVars .= '--bsbasic-container-pad:' . $containerPads[$containerPad] . ';';
	}
}

// Presets for the Border Radius and Content Indent params; "soft" and
// "default" match the values in scss/_base.scss, so they emit nothing.
$readings = ['narrow' => '40rem', 'wide' => '54rem', 'xwide' => '64rem'];
$spacings = ['compact' => '.5rem', 'roomy' => '1.5rem'];
$radii   = ['square' => '0', 'round' => '1rem'];
$indents = ['none' => '0', 'small' => '2.5rem', 'large' => '7.5rem'];

if (isset($radii[$borderRadius])) {
	$rootVars .= '--bsbasic-radius:' . $radii[$borderRadius] . ';';
}

if ($isBoxed) {
	// The box edge already frames the content, so an indent reads as an offset.
	$rootVars .= '--bsbasic-indent:0;';
} elseif (isset($indents[$contentIndent])) {
	$rootVars .= '--bsbasic-indent:' . $indents[$contentIndent] . ';';
}

// 600ms is the fallback in scss/_animations.scss, so the default emits nothing.
if ($animEntrance !== '' && $animSpeed !== 600) {
	$rootVars .= '--bsbasic-anim-dur:' . $animSpeed . 'ms;';
}

if (isset($readings[$readingWidth])) {
	$rootVars .= '--bsbasic-reading-width:' . $readings[$readingWidth] . ';';
}

if (isset($spacings[$headerSpacing])) {
	$rootVars .= '--bsbasic-header-pad:' . $spacings[$headerSpacing] . ';';
}

if ($headingColor !== '') {
	$rootVars .= '--bsbasic-heading-color:' . $headingColor . ';';
}

if ($bodyFont !== 'default' && isset($fontStacks[$bodyFont])) {
	$rootVars .= '--bsbasic-body-font:' . $fontStacks[$bodyFont] . ';';
}

if ($headingFont !== 'default' && isset($fontStacks[$headingFont])) {
	$rootVars .= '--bsbasic-heading-font:' . $fontStacks[$headingFont] . ';';
}

// Bootstrap theme colors. Primary (Primary, else Accent) gets the --bs-* set only;
// its buttons follow --bsbasic-primary in scss/_base.scss. The default red's set
// is static there too, so nothing is emitted until a color is picked.
$themeCss    = '';
$primaryHex  = $primaryColor !== '' ? $primaryColor : $accentColor;
$primaryRgb  = $primaryHex !== '' ? bsbasicHexRgb($primaryHex) : null;

if ($primaryRgb !== null) {
	$themeCss .= bsbasicThemeColor('primary', $primaryRgb, $rootVars, false);
}

$bsSet = [];

foreach (['secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark'] as $bsName) {
	$bsRgb = bsbasicHexRgb((string) $params->get('bs' . ucfirst($bsName), ''));

	if ($bsRgb !== null) {
		$bsSet[$bsName] = $bsRgb;
		$themeCss      .= bsbasicThemeColor($bsName, $bsRgb, $rootVars);
	}
}

// Tables & List Groups (Bootstrap tab). bootstrap.min.css hard-codes .table-*;
// list groups read the --bs-*-subtle set above, except Light and Dark (fixed grays).
// Primary always differs from Bootstrap's blue (the default accent is red).
$paletteRgb = ['primary' => $primaryRgb ?? bsbasicHexRgb('#ff0000')] + $bsSet;

if ((bool) $params->get('tableVariants', 0)) {
	foreach ($paletteRgb as $bsName => $bsRgb) {
		$themeCss .= bsbasicTableVariant($bsName, $bsRgb);
	}
}

if ((bool) $params->get('listGroupVariants', 1)) {
	foreach (array_intersect_key($bsSet, ['light' => 1, 'dark' => 1]) as $bsName => $bsRgb) {
		$themeCss .= bsbasicListGroupVariant($bsName, $bsRgb);
	}
} else {
	// Back to Bootstrap's own list-group colors for every color the palette changed.
	$bsDefaults = [
		'primary'   => '#0d6efd',
		'secondary' => '#6c757d',
		'success'   => '#198754',
		'info'      => '#0dcaf0',
		'warning'   => '#ffc107',
		'danger'    => '#dc3545',
	];

	foreach (array_intersect_key($bsDefaults, $paletteRgb) as $bsName => $bsHex) {
		$themeCss .= bsbasicListGroupVariant($bsName, bsbasicHexRgb($bsHex));
	}
}

// template.css reads these custom properties, so the params only need to
// override variables (the defaults live in scss/_base.scss).
$inlineCss = ($rootVars !== '' ? ':root{' . $rootVars . '}' : '') . $themeCss;

// Scoped one element deeper than the scheme modifiers so they cannot win; the
// drop-down panel redefines --bsbasic-nav-fg itself and stays unaffected.
if ($headerLinkColor !== '') {
	$inlineCss .= 'header.bsbasic-header{--bsbasic-nav-fg:' . $headerLinkColor . ';}';
}

if ($footerLinkColor !== '') {
	$inlineCss .= 'footer.bsbasic-footer{--bsbasic-footer-link:' . $footerLinkColor . ';}';
}

if ($isBoxed && $boxedWidth !== 1320) {
	$inlineCss .= '.' . $boxedFrom . '{max-width:' . $boxedWidth . 'px;}';
}

if ($inlineCss !== '') {
	$wa->addInlineStyle($inlineCss);
}

// Last, so it wins over the template and the params above; style tags are
// dropped so pasted markup cannot break the block or the first selector.
$customCss = trim((string) preg_replace('#</?style\b[^>]*>#i', '', $customCss));

if ($customCss !== '') {
	$wa->addInlineStyle($customCss);
}

if ($backToTop) {
	$wa->useScript('tpl.bsbasic.backtotop');
}

if ($stickyHeader) {
	$wa->useScript('tpl.bsbasic.sticky');
}

if ($sidebarMenu) {
	$wa->useScript('tpl.bsbasic.sidebarmenu');
}

if ($animEntrance !== '') {
	$wa->useScript('tpl.bsbasic.animate');
}

// Bootstrap JS Components. Most work through Bootstrap's data API once loaded;
// tooltip, popover and toast need initializing, which Joomla's helpers do for a selector.
$bsInit = [
	'tooltip' => '[data-bs-toggle="tooltip"]',
	'popover' => '[data-bs-toggle="popover"]',
	'toast'   => '.toast',
];

// The style's list loads on every page; the companion plugin (plg_system_bsbasicog)
// adds a Bootstrap tab to menu items whose list loads on that item's pages only.
// Read only while the plugin is enabled, so a hidden tab can't leave scripts behind.
$bsComponents = (array) $params->get('bsComponents', []);
$activeItem   = $app->getMenu()->getActive();

if ($activeItem !== null && PluginHelper::isEnabled('system', 'bsbasicog')) {
	$bsComponents = array_merge($bsComponents, (array) $activeItem->getParams()->get('bsbasic_bs_components', []));
}

foreach (array_unique(array_map('strval', $bsComponents)) as $bsComponent) {
	if (isset($bsInit[$bsComponent])) {
		HTMLHelper::_('bootstrap.' . $bsComponent, $bsInit[$bsComponent]);
	} elseif (in_array($bsComponent, ['alert', 'carousel', 'dropdown', 'modal', 'offcanvas', 'scrollspy', 'tab'], true)) {
		$wa->useScript('bootstrap.' . $bsComponent);
	}
}

// Joomla's bundled Font Awesome (media/vendor/fontawesome-free), no external request.
if ((bool) $params->get('fontAwesome', 0)) {
	$wa->useStyle('fontawesome');
}

// Breakpoint Indicator: only while System Debug is on, so it never reaches a live site.
$bpIndicator = (bool) $params->get('bpIndicator', 0) && (bool) $app->get('debug');

// Media field values look like "images/x.png#joomlaImage://..."; keep the path.
$mediaPath = static function (string $value): string {
	$path = ltrim(trim(explode('#', $value)[0]), '/');

	return implode('/', array_map('rawurlencode', explode('/', rawurldecode($path))));
};

$favicon = $mediaPath((string) $params->get('favicon', ''));

if ($favicon !== '') {
	$faviconTypes = [
		'ico'  => 'image/vnd.microsoft.icon',
		'png'  => 'image/png',
		'svg'  => 'image/svg+xml',
		'gif'  => 'image/gif',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'webp' => 'image/webp',
	];
	$faviconExt = strtolower(pathinfo($favicon, PATHINFO_EXTENSION));

	if (isset($faviconTypes[$faviconExt])) {
		$this->addFavicon(Uri::root(true) . '/' . $favicon, $faviconTypes[$faviconExt]);
	}
}

// Browser bar color. Primary falls back to Accent, then the built-in red.
$themeColor = (string) $params->get('themeColor', 'none');
$themeValue = '';

if ($themeColor === 'primary') {
	$themeValue = $primaryColor !== '' ? $primaryColor : ($accentColor !== '' ? $accentColor : '#ff0000');
} elseif ($themeColor === 'accent') {
	$themeValue = $accentColor !== '' ? $accentColor : '#ff0000';
} elseif ($themeColor === 'custom') {
	$themeValue = trim((string) $params->get('themeColorCustom', ''));
}

if ($themeValue !== '') {
	$this->setMetaData('theme-color', $themeValue);
}

// Open Graph defaults. A tag another extension already set is kept.
if ((bool) $params->get('socialTags', 0)) {
	$shareImage = $mediaPath((string) $params->get('socialImage', ''));
	$ogTags     = [
		'og:site_name'   => (string) $app->get('sitename'),
		'og:type'        => 'website',
		'og:title'       => (string) $this->getTitle(),
		'og:description' => (string) $this->getDescription(),
		'og:url'         => Uri::current(),
		'og:image'       => $shareImage !== '' ? Uri::root() . $shareImage : '',
	];

	foreach ($ogTags as $ogName => $ogValue) {
		if ($ogValue !== '' && $this->getMetaData($ogName, 'property') === '') {
			$this->setMetaData($ogName, $ogValue, 'property');
		}
	}

	if ((bool) $params->get('twitterCard', 0) && $this->getMetaData('twitter:card') === '') {
		$this->setMetaData('twitter:card', $shareImage !== '' ? 'summary_large_image' : 'summary');
	}
}

// Optional client overrides: css/user.css is not shipped, so it survives upgrades.
if (is_file(JPATH_THEMES . '/' . $this->template . '/css/user.css')) {
	$wa->useStyle('tpl.bsbasic.user');
}

// bsbasic-anim-pending hides the animation targets from the first paint; animate.js
// removes it once it has taken over, and CSS reveals them anyway after a few seconds.
$htmlClass = trim(
	($stickyHeader ? 'bsbasic-has-sticky ' : '')
	. ($animEntrance !== '' ? 'bsbasic-anim-pending' : '')
);

$hasLeft  = (bool) $this->countModules('sidebar-left');
$hasRight = (bool) $this->countModules('sidebar-right');
$mainCols = 12 - ($hasLeft ? $sidebarWidth : 0) - ($hasRight ? $sidebarWidth : 0);
// Content First: the article leads on small screens, the left sidebar returns at $stackBp.
$leftOrder = $contentFirst ? 'order-2 order-' . $stackBp . '-1' : 'order-1';
$mainOrder = $contentFirst ? 'order-1 order-' . $stackBp . '-2' : 'order-2';
$sidebarCls = 'bsbasic-sidebar'
	. ($sidebarMenu ? ' bsbasic-sidebar--collapsible' : '')
	. ($params->get('sidebarDividers', 'off') === 'on' ? ' bsbasic-sidebar--divided' : '');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>"<?php echo $htmlClass !== '' ? ' class="' . $htmlClass . '"' : ''; ?><?php echo $animEntrance !== '' ? ' data-bsbasic-anim="' . $animEntrance . '" data-bsbasic-anim-targets="' . $animTargets . '" data-bsbasic-anim-stagger="' . ($animStagger ? '1' : '0') . '" data-bsbasic-anim-step="' . $animStep . '"' : ''; ?>>
<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
	<?php if ($customHead) : ?>
		<?php echo $customHead; ?>
	<?php endif; ?>
</head>
<body class="site bsbasic-hover-<?php echo $animHover; ?><?php echo $animBlogHover ? ' bsbasic-blog-hover' : ''; ?><?php echo $pageclassSfx ? ' ' . $pageclassSfx : ''; ?><?php echo $extraCls('clsBody'); ?>">

<?php if ($this->countModules('debug')) : ?>
	<jdoc:include type="modules" name="debug" style="none" />
<?php endif; ?>

<?php if ($this->countModules('topbar')) : ?>
<div class="bsbasic-topbar">
	<div class="<?php echo $bandContainer; ?>">
		<jdoc:include type="modules" name="topbar" style="none" />
	</div>
</div>
<?php endif; ?>

<?php if ($this->countModules('below-top')) : ?>
<div class="bsbasic-below-top py-2">
	<div class="<?php echo $container; ?>">
		<jdoc:include type="modules" name="below-top" style="card" />
	</div>
</div>
<?php endif; ?>

<header class="bsbasic-header bsbasic-header--bp-<?php echo $collapseBp; ?><?php echo $headerScheme !== 'light' ? ' bsbasic-header--' . $headerScheme : ''; ?><?php echo $headerShadow === 'none' ? ' bsbasic-header--flat' : ''; ?><?php echo $menuStyle !== '' ? ' bsbasic-header--nav-' . $menuStyle : ''; ?><?php echo $stickyHeader ? ' sticky-top' : ''; ?><?php echo $extraCls('clsHeader'); ?>"<?php echo $stickyHeader ? ' data-sticky="' . $stickyMode . '"' : ''; ?>>
	<nav class="navbar navbar-expand-<?php echo $collapseBp; ?><?php echo $extraCls('clsNavbar'); ?>"<?php echo $headerScheme !== 'light' ? ' data-bs-theme="dark"' : ''; ?>>
		<div class="<?php echo $bandContainer; ?>">
			<a class="navbar-brand d-flex align-items-center" href="<?php echo Uri::root(); ?>">
				<?php if ($logoImage) : ?>
					<img
						src="<?php echo Uri::root() . htmlspecialchars($logoImage, ENT_QUOTES, 'UTF-8'); ?>"
						alt="<?php echo htmlspecialchars($logoAlt, ENT_QUOTES, 'UTF-8'); ?>"
						class="bsbasic-logo"
					>
				<?php else : ?>
					<span><?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8'); ?></span>
				<?php endif; ?>
				<?php if ($tagline) : ?>
					<small class="bsbasic-tagline d-none d-md-inline"><?php echo htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8'); ?></small>
				<?php endif; ?>
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="<?php echo $offcanvas ? 'offcanvas' : 'collapse'; ?>" data-bs-target="#bsbasicNav" aria-controls="bsbasicNav"<?php echo $offcanvas ? '' : ' aria-expanded="false"'; ?> aria-label="<?php echo htmlspecialchars(Text::_('TPL_BSBASIC_TOGGLE_NAV'), ENT_QUOTES, 'UTF-8'); ?>">
				<span class="navbar-toggler-icon"></span>
			</button>
			<?php if ($offcanvas) : ?>
			<div class="offcanvas <?php echo $mobileMenu; ?>" tabindex="-1" id="bsbasicNav" aria-labelledby="bsbasicNavLabel">
				<div class="offcanvas-header">
					<div class="offcanvas-title h5 mb-0" id="bsbasicNavLabel"><?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8'); ?></div>
					<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo htmlspecialchars(Text::_('TPL_BSBASIC_CLOSE_MENU'), ENT_QUOTES, 'UTF-8'); ?>"></button>
				</div>
				<div class="offcanvas-body flex-grow-1">
			<?php else : ?>
			<div class="collapse navbar-collapse" id="bsbasicNav">
			<?php endif; ?>
				<div class="<?php echo $menuAlign; ?> d-flex flex-column flex-<?php echo $collapseBp; ?>-row align-items-<?php echo $collapseBp; ?>-center gap-2 gap-<?php echo $collapseBp; ?>-3">
					<jdoc:include type="modules" name="menu" style="none" />
					<?php if ($this->countModules('search')) : ?>
						<div class="bsbasic-search">
							<jdoc:include type="modules" name="search" style="none" />
						</div>
					<?php endif; ?>
				</div>
			<?php if ($offcanvas) : ?>
				</div>
			<?php endif; ?>
			</div>
		</div>
	</nav>
</header>

<?php if ($this->countModules('banner')) : ?>
<div class="bsbasic-banner bsbasic-reveal bsbasic-reveal--section">
	<div class="bsbasic-banner__inner <?php echo $container; ?>">
		<jdoc:include type="modules" name="banner" style="none" />
	</div>
</div>
<?php endif; ?>

<?php if ($containerBg !== '') : ?>
<div class="bsbasic-main-inner <?php echo $container; ?>">
<?php endif; ?>

<?php if ($this->countModules('top-a')) : ?>
<div class="bsbasic-top-a bsbasic-reveal bsbasic-reveal--section py-3">
	<div class="<?php echo $inner; ?>">
		<jdoc:include type="modules" name="top-a" style="card" />
	</div>
</div>
<?php endif; ?>

<?php if ($this->countModules('top-b')) : ?>
<div class="bsbasic-top-b bsbasic-reveal bsbasic-reveal--section py-3">
	<div class="<?php echo $inner; ?>">
		<jdoc:include type="modules" name="top-b" style="card" />
	</div>
</div>
<?php endif; ?>

<div class="bsbasic-main py-5<?php echo $extraCls('clsMainArea'); ?>">
	<div class="<?php echo $inner; ?>">

		<jdoc:include type="message" />

		<?php if ($this->countModules('breadcrumbs')) : ?>
		<div class="bsbasic-breadcrumbs mb-3">
			<jdoc:include type="modules" name="breadcrumbs" style="none" />
		</div>
		<?php endif; ?>

		<?php if ($this->countModules('main-top')) : ?>
		<div class="row mb-3">
			<div class="col-12">
				<jdoc:include type="modules" name="main-top" style="card" />
			</div>
		</div>
		<?php endif; ?>

		<div class="row<?php echo $gridGutter !== '' ? ' gx-' . $gridGutter : ''; ?>">
			<?php if ($hasLeft) : ?>
			<div class="col-<?php echo $stackBp; ?>-<?php echo $sidebarWidth; ?> <?php echo $leftOrder; ?> mb-4">
				<aside class="<?php echo $sidebarCls . $extraCls('clsSidebar'); ?>" aria-label="<?php echo htmlspecialchars(Text::_('TPL_BSBASIC_SIDEBAR_LEFT'), ENT_QUOTES, 'UTF-8'); ?>">
					<jdoc:include type="modules" name="sidebar-left" style="card" />
				</aside>
			</div>
			<?php endif; ?>

			<div class="col-<?php echo $stackBp; ?>-<?php echo $mainCols; ?> <?php echo $mainOrder; ?>">
				<main class="bsbasic-content<?php echo ($hasLeft || $hasRight) ? '' : ' bsbasic-content--solo'; ?><?php echo $extraCls('clsMain'); ?>">
					<jdoc:include type="component" />
				</main>
			</div>

			<?php if ($hasRight) : ?>
			<div class="col-<?php echo $stackBp; ?>-<?php echo $sidebarWidth; ?> order-3 mb-4">
				<aside class="<?php echo $sidebarCls . $extraCls('clsSidebar'); ?>" aria-label="<?php echo htmlspecialchars(Text::_('TPL_BSBASIC_SIDEBAR_RIGHT'), ENT_QUOTES, 'UTF-8'); ?>">
					<jdoc:include type="modules" name="sidebar-right" style="card" />
				</aside>
			</div>
			<?php endif; ?>
		</div>

		<?php if ($this->countModules('main-bottom')) : ?>
		<div class="row mt-3">
			<div class="col-12">
				<jdoc:include type="modules" name="main-bottom" style="card" />
			</div>
		</div>
		<?php endif; ?>

	</div>
</div>

<?php if ($this->countModules('bottom-a') || $this->countModules('bottom-b')) : ?>
<div class="bsbasic-bottom bsbasic-reveal bsbasic-reveal--section py-5">
	<div class="<?php echo $inner; ?>">
		<div class="row g-<?php echo $gridGutter !== '' ? $gridGutter : '4'; ?><?php echo $extraCls('clsBottom'); ?>">
			<?php if ($this->countModules('bottom-a')) : ?>
			<div class="col-lg bsbasic-bottom-a">
				<jdoc:include type="modules" name="bottom-a" style="card" />
			</div>
			<?php endif; ?>

			<?php if ($this->countModules('bottom-b')) : ?>
			<div class="col-lg bsbasic-bottom-b">
				<jdoc:include type="modules" name="bottom-b" style="card" />
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>
<?php if ($containerBg !== '') : ?>
</div>
<?php endif; ?>

<footer class="bsbasic-footer<?php echo $footerColor !== 'dark' ? ' bsbasic-footer--' . $footerColor : ''; ?> py-5 mt-auto<?php echo $extraCls('clsFooter'); ?>">
	<div class="<?php echo $bandContainer; ?>">
		<jdoc:include type="modules" name="footer" style="html5" />
		<?php if ($copyrightText !== '') : ?>
		<p class="bsbasic-copyright mb-0"><?php echo $copyrightText; ?></p>
		<?php endif; ?>
	</div>
</footer>

<?php if ($backToTop) : ?>
<a href="#" id="bsbasicBackToTop" data-threshold="<?php echo $bttThreshold; ?>" class="btn btn-primary rounded-circle bsbasic-back-to-top<?php echo $bttPosition === 'left' ? ' bsbasic-back-to-top--left' : ''; ?>" aria-label="<?php echo htmlspecialchars(Text::_('TPL_BSBASIC_BACK_TO_TOP'), ENT_QUOTES, 'UTF-8'); ?>">&uarr;</a>
<?php endif; ?>

<?php if ($bpIndicator) : ?>
<div class="bsbasic-bp" aria-hidden="true">
	<span class="d-sm-none">xs</span>
	<span class="d-none d-sm-inline d-md-none">sm</span>
	<span class="d-none d-md-inline d-lg-none">md</span>
	<span class="d-none d-lg-inline d-xl-none">lg</span>
	<span class="d-none d-xl-inline d-xxl-none">xl</span>
	<span class="d-none d-xxl-inline">xxl</span>
</div>
<?php endif; ?>

<?php if ($customBodyEnd) : ?>
	<?php echo $customBodyEnd; ?>
<?php endif; ?>

</body>
</html>
