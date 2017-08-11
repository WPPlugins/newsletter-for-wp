<?php

/**
 * Echoes a sign-up checkbox.
 *
 * @since 1.0
 * @deprecated 3.0
 * @use `nl4wp_get_integration`
 * @ignore
 */
function nl4wp_checkbox() {
	_deprecated_function( __FUNCTION__, 'NewsLetter for WordPress v3.0' );
	nl4wp_get_integration('wp-comment-form')->output_checkbox();
}

/**
 * Echoes a NewsLetter for WordPress form
 *
 * @ignore
 * @since 1.0
 * @deprecated 3.0
 * @use nl4wp_show_form()
 *
 * @param   int     $id     The form ID
 *
 * @return NL4WP_Form
 *
 */
function nl4wp_form( $id = 0, $attributes = array() ) {
	_deprecated_function( __FUNCTION__, 'NewsLetter for WordPress v3.0', 'nl4wp_show_form' );
	return nl4wp_show_form( $id, $attributes );
}

