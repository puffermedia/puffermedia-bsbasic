<?php
/**
 * Error page (404 / 403 / 500). Shows any modules published to the
 * error-404 or error-403 positions, matching Cassiopeia's behaviour.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */
/** @var Throwable $this->error */

$wa = $this->getWebAssetManager();
$wa->registerAndUseStyle('tpl.bsbasic.bootstrap', 'media/vendor/bootstrap/css/bootstrap.min.css')
	->useStyle('tpl.bsbasic.template');

$code = (int) $this->error->getCode();
$this->setTitle((string) $code);

$position = ($code === 404) ? 'error-404' : 'error-403';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
</head>
<body class="site bsbasic-error">
	<div class="container py-5 text-center">
		<h1 class="display-3 mb-3"><?php echo $code; ?></h1>
		<p class="lead mb-4"><?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
		<a class="btn btn-primary" href="<?php echo Uri::root(); ?>">
			<?php echo htmlspecialchars(Text::_('TPL_BSBASIC_BACK_HOME'), ENT_QUOTES, 'UTF-8'); ?>
		</a>

		<div class="mt-5">
			<jdoc:include type="modules" name="<?php echo $position; ?>" style="none" />
		</div>
	</div>
</body>
</html>
