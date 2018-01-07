<?php

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5a21a2c90689a',
	'title' => 'Vice Image Optimizer - Settings',
	'fields' => array(
		array(
			'key' => 'field_5a21a51f1a72e',
			'label' => 'Conversion',
			'name' => 'vio_conversion',
			'type' => 'group',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'layout' => 'row',
			'sub_fields' => array(
				array(
					'key' => 'field_5a21a2dbae48c',
					'label' => 'Convert PNG to JPG',
					'name' => 'convert_png',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 1,
					'ui' => 1,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
				array(
					'key' => 'field_5a21a4a6dd8b7',
					'label' => 'Convert GIF to JPG',
					'name' => 'convert_gif',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 1,
					'ui' => 1,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
				array(
					'key' => 'field_5a2381771673f',
					'label' => 'Resize Images',
					'name' => 'resize_images',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 1,
					'ui' => 1,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
				array(
					'key' => 'field_5a45b83190f61',
					'label' => 'Force Resize',
					'name' => 'force_resize',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 0,
					'ui' => 1,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
				array(
					'key' => 'field_5a2381ab16740',
					'label' => 'Max Width',
					'name' => 'max_width',
					'type' => 'range',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => array(
						array(
							array(
								'field' => 'field_5a2381771673f',
								'operator' => '==',
								'value' => '1',
							),
						),
					),
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => 1600,
					'min' => 100,
					'max' => 4096,
					'step' => '',
					'prepend' => '',
					'append' => '',
				),
				array(
					'key' => 'field_5a23826016741',
					'label' => 'Max Height',
					'name' => 'max_height',
					'type' => 'range',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => array(
						array(
							array(
								'field' => 'field_5a2381771673f',
								'operator' => '==',
								'value' => '1',
							),
						),
					),
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => 1600,
					'min' => 100,
					'max' => 4096,
					'step' => '',
					'prepend' => '',
					'append' => '',
				),
			),
		),
		array(
			'key' => 'field_5a21b089d48ed',
			'label' => 'Usage Instructions',
			'name' => 'vio_usage_instructions',
			'type' => 'group',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'layout' => 'block',
			'sub_fields' => array(
				array(
					'key' => 'field_5a21b0a2d48ee',
					'label' => 'General Info',
					'name' => '',
					'type' => 'message',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '<style>
	.inside > .acf-field-group > .acf-label > label {
		font-weight: normal;
		font-size: 1.6em;
	}
	.goi-instructions > dl {
		margin: 0;
		padding: 0 0 4em 0;
	}
	.goi-instructions > dl > dt,
	.goi-instructions > dl > dd {
		display: block;
		float: left;
		margin: 0;
		padding: 0 0 0.8em 0;
	}
	.goi-instructions > dl > dt {
		clear: left;
		width: 33%;
	}
	.goi-instructions > dl > dd {
		width: 67%;
	}
	@media screen and (max-width: 767px) {
		.goi-instructions > dl > dt {
			width: 50%;
		}
		.goi-instructions > dl > dd {
			width: 50%;
		}
	}
</style>
<div class="goi-instructions">
	<dl>
		<dt><strong>Plugin Active</strong></dt><dd>It will compress images slowly in the background over time. Maximum rate is 10 images per hour. Rate will be slower if site has no traffic (because of how wp_cron works).</dd>
		<dt><strong>Convert to JPG</strong></dt><dd>Does not affect existing images, only new images uploaded while this option is enabled. Does not affect images that have transparency. Does not affect images whose file size would increase by converting.</dd>
		<dt><strong>Resize Images</strong></dt><dd>Affects new and existing images. Any <strong><em>original (full size)</em></strong> image larger than the specified dimensions will be resized.</dd>
		<dt><strong>Force Resize</strong></dt><dd>Affects new and existing images. Any <strong><em>generated size</em></strong> image larger than the specified dimensions will be resized.</dd>
		<dt><strong>Note</strong></dt><dd>This plugin is not intended for sites that will be adding huge amounts of images regularly, or sites that need to do bulk processing all at once. If you require that sort of thing, please take a look at other plugins like "WP Smush Pro" or "Kraken Image Optimizer".</dd>
	</dl>
</div>',
					'new_lines' => '',
					'esc_html' => 0,
				),
			),
		),
		array(
			'key' => 'field_5a21aac17fd5f',
			'label' => 'Storage',
			'name' => 'vio_storage',
			'type' => 'group',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'layout' => 'block',
			'sub_fields' => array(
				array(
					'key' => 'field_5a21aafddcb6c',
					'label' => 'Optimized',
					'name' => 'optimized',
					'type' => 'textarea',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'maxlength' => '',
					'rows' => '',
					'new_lines' => '',
				),
				array(
					'key' => 'field_5a21acb155a51',
					'label' => 'To Optimize',
					'name' => 'to_optimize',
					'type' => 'textarea',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'maxlength' => '',
					'rows' => '',
					'new_lines' => '',
				),
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'vio-settings',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'seamless',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => '',
));

endif;

?>