<?php

/**
 * @package     Templates.bsbasic
 * @subpackage  Form field layout — range slider with a live millisecond readout
 *
 * Joomla's own joomla.form.field.range layout renders a bare slider with no
 * number. Used by the Animation Speed param through
 * layout="bsbasic.range-ms" and layoutIncludePath="templates/puffermedia-bsbasic/layouts".
 * The slider takes half the width of its column; the value sits beside it.
 *
 * Layout variables come from the range field (see joomla.form.field.range).
 */

defined('_JEXEC') or die;

extract($displayData);

$value   = is_numeric($value) ? (int) $value : (int) $min;
$fieldId = $id ?: $name;

$attributes = [
	!empty($description) ? 'aria-describedby="' . $fieldId . '-desc"' : '',
	$disabled ? 'disabled' : '',
	$readonly ? 'readonly' : '',
	!empty($onchange) ? 'onchange="' . $onchange . '"' : '',
	!empty($max) ? 'max="' . (int) $max . '"' : '',
	!empty($step) ? 'step="' . (int) $step . '"' : '',
	!empty($min) ? 'min="' . (int) $min . '"' : '',
	$autofocus ? 'autofocus' : '',
];
?>
<div class="d-flex align-items-center gap-3">
	<input
		type="range"
		name="<?php echo $name; ?>"
		id="<?php echo $fieldId; ?>"
		value="<?php echo $value; ?>"
		class="form-range w-50"
		oninput="document.getElementById('<?php echo $fieldId; ?>-value').textContent = this.value + ' ms'"
		<?php echo implode(' ', array_filter($attributes)); ?>>
	<output id="<?php echo $fieldId; ?>-value" for="<?php echo $fieldId; ?>" class="fw-bold text-nowrap"><?php echo $value; ?> ms</output>
</div>
