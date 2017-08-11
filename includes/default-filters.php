<?php

defined( 'ABSPATH' ) or exit;
add_filter( 'nl4wp_debug_log_level', function() { return 'debug'; } );

add_filter( 'nl4wp_form_merge_vars', 'nl4wp_guess_merge_vars' );
add_filter( 'nl4wp_integration_merge_vars', 'nl4wp_guess_merge_vars' );

add_filter( 'nl4wp_use_sslverify', '__nl4wp_use_sslverify', 1 );