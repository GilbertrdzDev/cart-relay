<?php

defined( 'ABSPATH' ) || exit;

$attributes = isset( $attributes ) ? (string) $attributes : '';
$options    = isset( $options ) && is_array( $options ) ? $options : [];
$value      = isset( $value ) ? (string) $value : '';
?>
<select <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $options as $option_value => $option_label ) : ?>
		<option value="<?php echo esc_attr( (string) $option_value ); ?>" <?php selected( $value, (string) $option_value ); ?>>
			<?php echo esc_html( (string) $option_label ); ?>
		</option>
	<?php endforeach; ?>
</select>
