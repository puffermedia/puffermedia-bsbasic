<?php
/**
 * Component-only layout (e.g. ?tmpl=component, used by print views,
 * some modals, and a few third-party extensions).
 */

defined('_JEXEC') or die;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$wa = $this->getWebAssetManager();
$wa->registerAndUseStyle('tpl.bsbasic.bootstrap', 'media/vendor/bootstrap/css/bootstrap.min.css')
	->useStyle('tpl.bsbasic.template');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>
<body class="site bsbasic-component">
	<div class="container py-3">
		<jdoc:include type="message" />
		<jdoc:include type="component" />
	</div>
</body>
</html>
