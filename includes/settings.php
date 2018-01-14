<?php
/**
 * WordPress Settings Framework
 *
 * @author Gilbert Pellegrom, James Kemp
 * @link https://github.com/gilbitron/WordPress-Settings-Framework
 * @license MIT
 *
 *
 * Copyright © 2012 Dev7studios
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of 
 * this software and associated documentation files (the "Software"), to deal in 
 * the Software without restriction, including without limitation the rights to 
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of 
 * the Software, and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS 
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR 
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER 
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
add_filter( 'wpsf_register_settings_vio_settings', 'vio_tabless_settings' );
function vio_tabless_settings( $wpsf_settings ) {
	// General Settings
	$wpsf_settings[] = array(
		'section_id'          => 'general',
		'section_title'       => __( 'General Settings', 'vio' ),
		'section_description' => __( 'General Settings Section.', 'vio' ),
		'section_order'       => 1,
		'fields' => array(
			array(
				'id'      => 'resize',
				'title'   => __( 'Resize Large Images', 'vio' ),
				'desc'    => __( 'Resize large images.', 'vio' ),
				'type'    => 'checkbox',
				'default' => 1,
			),
			array(
				'id'      => 'max_width',
				'title'   => __( 'Max Width', 'vio' ),
				'desc'    => __( 'The maximum width.', 'vio' ),
				'type'    => 'number',
				'default' => 1600,
			),
			array(
				'id'      => 'max_height',
				'title'   => __( 'Max Height', 'vio' ),
				'desc'    => __( 'The maximum height.', 'vio' ),
				'type'    => 'number',
				'default' => 1600,
			),
		),
	);

	return $wpsf_settings;
}
