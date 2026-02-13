<?php

class PBE_Conditions
{

	protected $filter_fields = null;

	function get_filter_fields()
	{

		if (!is_null($this->filter_fields)) {
			return $this->filter_fields;
		}

		$upsell_text = sprintf(__('Available on <a href="%s">Pro version</a>', 'pbe'), PBE_PLUGIN_PRO_URL);

		$post_status = array(
			'any' => __('Any', 'pbe'),
			'publish' => __('Publish', 'pbe'),
			'private' => __('Private', 'pbe'),
			'future' => __('Future', 'pbe'),
			'draft' => __('Draft', 'pbe'),
		);

		$post_status_edit = array(
			'publish' => __('Publish', 'pbe'),
			'private' => __('Private', 'pbe'),
			'future' => __('Future', 'pbe'),
			'draft' => __('Draft', 'pbe'),
		);

		$filter_fields = array();

		$filter_fields['status'] = array(
			'title' => __('Product status', 'pbe'),
			'type' => 'custom_select',
			'source' => array(
				'type' => 'post',
				'field' => 'post_status', // Apply for type as post.
				'view' => 'get_status',
				'edit' => 'set_status',
			),
			'options' => $post_status,
			'edit_options' => $post_status_edit,
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
			'skip_column' => true,
		);

		$filter_fields['product_type'] = array(
			'title' => __('Product type', 'pbe'),
			'type' => 'product_type',
			'source' => array(
				'type' => 'tax',
				'taxonomy' => 'product_type', // Apply for type as tax.
				'field' => 'slug', // Apply for type as tax: term_id|slug|name.
			),
			'edit' => false,
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
			'skip_variations' => true,
		);

		$filter_fields['name'] = array(
			'title' => __('Product title', 'pbe'),
			'type' => 'string',
			'source' => array(
				'type' => 'post',
				'field' => 'post_title', // Apply for type as post.
				'field_type' => 'string',
				'view' => 'get_name',
				'edit' => 'set_name',
			),
			'skip_variations' => true,
		);

		$filter_fields['slug'] = array(
			'title' => __('Product slug', 'pbe'),
			'type' => 'string',
			'source' => array(
				'type' => 'post',
				'field' => 'post_name', // Apply for type as post.
				'view' => 'get_slug',
				'edit' => 'set_slug',
			),
			// 'options' => array(),
			// 'edit_options' => array(),
			'edit_only' => true, // do not show in find conditions.
			// 'edit_action' => false,
			// 'edit_action_label' => __( 'Set as', 'pbe' ),
			// 'skip_column' => true,
		);

		$filter_fields['short_description'] = array(
			'title' => __('Short description', 'pbe'),
			'type' => 'string',
			'source' => array(
				'type' => 'post',
				'field' => 'post_excerpt', // Apply for type as post.
				'field_type' => 'string',
				'view' => 'get_short_description',
				'edit' => 'set_short_description',
			),
			'editor' => true,
		);

		$filter_fields['description'] = array(
			'title' => __('Description', 'pbe'),
			'type' => 'string',
			'source' => array(
				'type' => 'post',
				'field' => 'post_content', // Apply for type as post.
				'field_type' => 'string',
				'view' => 'get_description',
				'edit' => 'set_description',
			),
			'editor' => true,
		);

		$filter_fields['date_created'] = array(
			'title' => __('Date published ', 'pbe'),
			'type' => 'date',
			'source' => array(
				'type' => 'post',
				'field' => 'post_date', // Apply for type as post.
				'field_type' => 'date',
				'view' => 'get_date_created',
				'edit' => 'set_date_created',
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$filter_fields['regular_price'] = array(
			'title' => __('Regular price', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_regular_price', // Apply for type as post_meta.
				'meta_type' => 'DECIMAL(15,5)', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
			),
		);

		$filter_fields['sale_price'] = array(
			'title' => __('Sale price', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sale_price', // Apply for type as post_meta.
				'meta_type' => 'DECIMAL(15,5)', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
			),
		);

	


			$filter_fields['date_on_sale_from'] = array(
				'title' => __('Sale dates from', 'pbe'),
				'type' => 'date',
				'source' => array(
					'type' => 'post_meta',
					'meta_key' => '_sale_price_dates_from', // Apply for type as post_meta.
					'meta_type' => 'NUMERIC', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
					'value_callback' => 'date_to_timestamp_start',
					'edit_val_callback' => array(pbe(), 'strtotime'),
				),
				'edit_action' => false,
				'edit_action_label' => __('Set as', 'pbe'),
				'action_show_only' => $upsell_text,
			);

			$filter_fields['date_on_sale_to'] = array(
				'title' => __('Sale dates end', 'pbe'),
				'type' => 'date',
				'source' => array(
					'type' => 'post_meta',
					'meta_key' => '_sale_price_dates_to', // Apply for type as post_meta.
					'meta_type' => 'NUMERIC', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
					'value_callback' => 'date_to_timestamp_end',
					'edit_val_callback' => array(pbe(), 'strtotime'),
				),
				'edit_action' => false,
				'edit_action_label' => __('Set as', 'pbe'),
				'action_show_only' => $upsell_text,
			);
		

		$filter_fields['sku'] = array(
			'title' => __('SKU', 'pbe'),
			'type' => 'meta_string',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sku', // Apply for type as post_meta.
				'meta_type' => '', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
			),
		);

		$filter_fields['image_id'] = array(
			'title' => __('Thumbnail', 'pbe'),
			'type' => 'image',
			'edit_only' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_thumbnail_id',
				'meta_type' => 'NUMERIC',
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

	
			$filter_fields['gallery_image_ids'] = array(
				'title' => __('Gallery', 'pbe'),
				'type' => 'gallery',
				'edit_only' => true,
				'source' => array(
					'type' => '',
					'meta_key' => '',
					'meta_type' => '',
				),
				'action_show_only' => $upsell_text,
				'skip_variations' => true,
			);
		

		$filter_fields['category_ids'] = array(
			'title' => __('Categories', 'pbe'),
			'type' => 'tax',
			'taxonomy' => 'product_cat',
			'source' => array(
				'type' => 'tax',
				'taxonomy' => 'product_cat', // Apply for type as tax.
			),
			'skip_variations' => true,
		);

		$filter_fields['tag_ids'] = array(
			'title' => __('Tags', 'pbe'),
			'type' => 'tax',
			'taxonomy' => 'product_tag',
			'source' => array(
				'type' => 'tax',
				'taxonomy' => 'product_tag', // Apply for type as tax.
			),
			'skip_variations' => true,
		);

		$filter_fields['virtual'] = array(
			'title' => __('Virtual', 'pbe'),
			'type' => 'custom_select',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_virtual',
				'meta_compare' => '=',
			),
			'options' => array(
				'no' => __('No', 'pbe'),
				'yes' => __('Yes', 'pbe'),
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$filter_fields['downloadable'] = array(
			'title' => __('Downloadable', 'pbe'),
			'type' => 'custom_select',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_downloadable',
				'meta_compare' => '=',
			),
			'options' => array(
				'no' => __('No', 'pbe'),
				'yes' => __('Yes', 'pbe'),
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$filter_fields['download_limit'] = array(
			'title' => __('Download limit', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_download_limit',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$filter_fields['download_expiry'] = array(
			'title' => __('Download expiry', 'pbe'),
			'type' => 'date',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_download_expiry',
				'edit_val_callback' => array(pbe(), 'strtotime'),
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

	
			$filter_fields['downloads'] = array(
				'title' => __('Download files', 'pbe'),
				'type' => 'files',
				'edit_only' => true,
				'source' => array(
					'type' => '',
					'meta_key' => '',
					'meta_type' => '',
				),
				'action_show_only' => $upsell_text,
			);
		

		$filter_fields['width'] = array(
			'title' => __('Width', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_width',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);


		$filter_fields['height'] = array(
			'title' => __('Height', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_height',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$filter_fields['weight'] = array(
			'title' => __('Weight', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_weight',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$filter_fields['length'] = array(
			'title' => __('Length', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_length',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$filter_fields['manage_stock'] = array(
			'title' => __('Manage stock', 'pbe'),
			'type' => 'custom_select',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_manage_stock',
				'meta_compare' => '=',
			),
			'options' => array(
				'no' => __('No', 'pbe'),
				'yes' => __('Yes', 'pbe'),
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$filter_fields['stock_quantity'] = array(
			'title' => __('Stock quantity', 'pbe'),
			'type' => 'number',
			'skip_column' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_stock', // Apply for type as post_meta.
				'meta_type' => 'NUMERIC', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
			),
		);

		$filter_fields['stock_status'] = array(
			'title' => __('Stock status', 'pbe'),
			'type' => 'custom_select',
			'edit' => false, // Disable for edit field.
			'skip_column' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_stock_status', // Apply for type as post_meta.
				'meta_type' => 'CHAR',
				'meta_compare' => '=',
			),
			'options' => array(
				'instock'  => __('Instock', 'pbe'),
				'outofstock' => __('Out of stock', 'pbe'),
			),
		);

		$filter_fields['backorders'] = array(
			'title' => __('Allow backorders', 'pbe'),
			'type' => 'custom_select',
			'skip_column' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_backorders', // Apply for type as post_meta.
				'meta_compare' => '=',
				'meta_type' => 'CHAR',
			),
			'options' => wc_get_product_backorder_options(),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$filter_fields['total_sales'] = array(
			'title' => __('Total sales', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => 'total_sales',
				'meta_type' => 'NUMERIC',
			),
		);

		$filter_fields['reviews_allowed'] = array(
			'title' => __('Review status', 'pbe'),
			'type' => 'string',
			'source' => array(
				'type' => 'post',
				'field' => 'comment_status',
				'field_type' => 'string',
			),
			'options' => array(
				'open' => __('Open', 'pbe'),
				'closed' => __('Closed', 'pbe'),
			),
			'type' => 'custom_select',
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$filter_fields['average_rating'] = array(
			'title' => __('Average rating', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_wc_average_rating',
				'meta_type' => 'DECIMAL(15,5)',
			),
			'skip_variations' => true,
		);

		$filter_fields['purchase_note'] = array(
			'title' => __('Purchase note', 'pbe'),
			'type' => 'string',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_purchase_note',
			),
			'skip_variations' => true,
		);

	
			$filter_fields['tax_status'] = array(
				'title' => __('Tax status', 'pbe'),
				'type' => 'custom_select',
				'source' => array(
					'type' => 'post_meta',
					'meta_key' => '_tax_status',
					'meta_compare' => '=',
				),
				'options' => array(
					'taxable'  => __('Taxable', 'pbe'),
					'shipping' => __('Shipping only', 'pbe'),
					'none'     => _x('None', 'Tax status', 'pbe'),
				),
				'edit_action' => false,
				'edit_action_label' => __('Set as', 'pbe'),
				'action_show_only' => $upsell_text,
			);
		

		$options = array(
			'' => __('Standard', 'pbe'),
		);

		$tax_classes = WC_Tax::get_tax_classes();

		if (!empty($tax_classes)) {
			foreach ($tax_classes as $class) {
				$options[sanitize_title($class)] = esc_html($class);
			}
		}

	
			$filter_fields['tax_class'] = array(
				'title' => __('Tax class', 'pbe'),
				'type' => 'custom_select',
				'source' => array(
					'type' => 'post_meta',
					'meta_key' => '_tax_class',
					'meta_compare' => '=',
				),
				'options' => $options,
				'edit_action' => false,
				'edit_action_label' => __('Set as', 'pbe'),
				'action_show_only' => $upsell_text,
			);
		

		$filter_fields['sold_individually'] = array(
			'title' => __('Sold individually', 'pbe'),
			'type' => 'custom_select',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sold_individually',
				'meta_compare' => '=',
			),
			'options' => array(
				'no'  => __('No', 'pbe'),
				'yes' => __('Yes', 'pbe'),
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

	
			foreach ((array) wc_get_attribute_taxonomies() as $att) {
				$tax = 'pa_' . $att->attribute_name;
				$filter_fields[$tax] = array(
					'title' => sprintf(__('Attribute: %s', 'pbe'), $att->attribute_label),
					'type' => 'tax',
					'taxonomy' => $tax,
					'source' => array(
						'type' => 'tax',
						'type' => 'tax',
						'taxonomy' => $tax,
					),
					'edit' => false,
					'edit_attribute' => 1,
					'skip_variations' => true,
					'action_show_only' => $upsell_text,
				);
			}
		

	

			$filter_fields['upsell_ids'] = array(
				'title' => __('Upsells', 'pbe'),
				'type' => 'products',
				'edit_only' => true, // do not show in find conditions.
				'source' => array(
					'type' => '',
					'meta_key' => '',
					'meta_type' => '',
				),
				'action_show_only' => $upsell_text,
			);

			$filter_fields['cross_sell_ids'] = array(
				'title' => __('Cross-sells', 'pbe'),
				'type' => 'products',
				'edit_only' => true, // do not show in find conditions.
				'source' => array(
					'type' => '',
					'meta_key' => '',
					'meta_type' => '',
				),
				'skip_variations' => true, // do not apply for variations.
				'action_show_only' => $upsell_text,
			);

			$filter_fields['_delete'] = array(
				'title' => __('Delete products', 'pbe'),
				'type' => 'delete',
				'edit_only' => true, // do not show in find conditions.
				'edit_action' => false,
				'edit_action_label' => '',
				'source' => array(
					'type' => 'delete',
				),
				'action_show_only' => $upsell_text,
			);
		

		$filter_fields['custom_field'] = array(
			'title' => __('Custom field', 'pbe'),
			'type' => 'custom_field',
			'edit' => false,
			'source' => array(
				'type' => 'custom_field',
			),
		);

		foreach ($filter_fields as $id => $field) {
			$filter_fields[$id]['_id'] = $id;
			if (!isset($field['skip_column'])) {
				$filter_fields[$id]['skip_column'] = false;
			}
		}

		$this->filter_fields = $filter_fields;

		return $filter_fields;
	}

	function get_variable_filter_fields()
	{

		$variable_filter_fields = array();

		$variable_filter_fields['all'] = array(
			'title' => __('All variantions', 'pbe'),
			'type' => 'none',
			'source' => array(
				'type' => 'none',
				'field' => 'none',
				'field_type' => 'none',
			),
		);

		$variable_filter_fields['sku'] = array(
			'title' => __('Variation SKU', 'pbe'),
			'type' => 'meta_string',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sku',
				'meta_type' => 'CHAR',
			),
		);

		$variable_filter_fields['regular_price'] = array(
			'title' => __('Variation regular price', 'pbe'),
			'type' => 'number',
			// 'edit_only' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_price',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$variable_filter_fields['sale_price'] = array(
			'title' => __('Variation sale price', 'pbe'),
			'type' => 'number',
			// 'edit_only' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sale_price',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$variable_filter_fields['date_on_sale_from'] = array(
			'title' => __('Variation sale dates from', 'pbe'),
			'type' => 'date',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sale_price_dates_from', // Apply for type as post_meta.
				'meta_type' => 'NUMERIC', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
				'value_callback' => 'date_to_timestamp_start',
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$variable_filter_fields['date_on_sale_to'] = array(
			'title' => __('Variation sale dates end', 'pbe'),
			'type' => 'date',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_sale_price_dates_to', // Apply for type as post_meta.
				'meta_type' => 'NUMERIC', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
				'value_callback' => 'date_to_timestamp_end',
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$variable_filter_fields['width'] = array(
			'title' => __('Variation width', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_width',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$variable_filter_fields['height'] = array(
			'title' => __('Variation height', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_height',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$variable_filter_fields['weight'] = array(
			'title' => __('Variation weight', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_weight',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$variable_filter_fields['length'] = array(
			'title' => __('Variation length', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_length',
				'meta_type' => 'DECIMAL(15,5)',
			),
		);

		$variable_filter_fields['manage_stock'] = array(
			'title' => __('Variation manage stock', 'pbe'),
			'type' => 'custom_select',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_manage_stock',
				'meta_compare' => '=',
			),
			'options' => array(
				'no' => __('No', 'pbe'),
				'yes' => __('Yes', 'pbe'),
			),
			'edit_action' => false,
			'edit_action_label' => __('Set as', 'pbe'),
		);

		$variable_filter_fields['stock_quantity'] = array(
			'title' => __('Variation stock quantity', 'pbe'),
			'type' => 'number',
			// 'skip_column' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_stock', // Apply for type as post_meta.
				'meta_type' => 'NUMERIC', // Apply for type as post_meta https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters.
			),
		);

		$variable_filter_fields['stock_status'] = array(
			'title' => __('Variation stock status', 'pbe'),
			'type' => 'custom_select',
			'edit' => false, // Disable for edit field.
			// 'skip_column' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_stock_status', // Apply for type as post_meta.
				'meta_type' => 'CHAR',
				'meta_compare' => '=',
			),
			'options' => array(
				'instock'  => __('Instock', 'pbe'),
				'outofstock' => __('Out of stock', 'pbe'),
			),
		);

		$variable_filter_fields['backorders'] = array(
			'title' => __('Variation allow backorders', 'pbe'),
			'type' => 'custom_select',
			// 'skip_column' => true,
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => '_backorders', // Apply for type as post_meta.
				'meta_compare' => '=',
				'meta_type' => 'CHAR',
			),
			'options' => wc_get_product_backorder_options(),
			// 'edit_action' => false,
			// 'edit_action_label' => __( 'Set as', 'pbe' ),
		);

		$variable_filter_fields['total_sales'] = array(
			'title' => __('Variation total sales', 'pbe'),
			'type' => 'number',
			'source' => array(
				'type' => 'post_meta',
				'meta_key' => 'total_sales',
				'meta_type' => 'NUMERIC',
			),
		);

		$variable_filter_fields['none'] = array(
			'title' => __('Skip all variantions', 'pbe'),
			'type' => 'none',
			'source' => array(
				'type' => 'none',
				'field' => 'none',
				'field_type' => 'none',
			),
		);

		foreach ($variable_filter_fields as $k => $f) {
			$variable_filter_fields[$k]['variable'] = true;
		}

		return $variable_filter_fields;
	}

	function get_filter_number_conditions()
	{
		$condtions = array(
			'=' => __('Equal (=)', 'pbe'),
			'>' => __('Greater than (>)', 'pbe'),
			'<' => __('Smaller than (<)', 'pbe'),
			'>=' => __('Greater than or equal (>=)', 'pbe'),
			'<=' => __('Smaller than or equal (<=)', 'pbe'),
			'between' => __('Between', 'pbe'),
			'not_between' => __('Not between', 'pbe'),
		);
		return $condtions;
	}

	function get_filter_date_conditions()
	{
		$condtions = array(
			'=' => __('Equal (=)', 'pbe'),
			'>' => __('Greater than (>)', 'pbe'),
			'<' => __('Smaller than (<)', 'pbe'),
			'>=' => __('Greater than or equal (>=)', 'pbe'),
			'<=' => __('Smaller than or equal (<=)', 'pbe'),
			'between' => __('Between', 'pbe'),
			'not_between' => __('Not between', 'pbe'),
		);
		return $condtions;
	}

	function get_filter_string_conditions()
	{
		$condtions = array(
			'containts' => __('Containts', 'pbe'),
			'not_containts' => __('Does not containts', 'pbe'),
			'start_with' => __('Start with', 'pbe'),
			'end_with' => __('End with', 'pbe'),
			'empty' => __('Empty', 'pbe'),
		);
		return $condtions;
	}

	function get_filter_meta_string_conditions()
	{
		$condtions = array(
			'containts' => __('Containts', 'pbe'),
			'not_containts' => __('Does not containts', 'pbe'),
			'empty' => __('Empty', 'pbe'),
		);
		return $condtions;
	}

	function get_filter_select_types_conditions()
	{
		$condtions = array(
			'in' => __('In', 'pbe'),
			'not_in' => __('Not in', 'pbe'),
		);
		return $condtions;
	}

	function get_filter_tax_conditions()
	{
		$condtions = array(
			'in' => __('In', 'pbe'),
			'not_in' => __('Not in', 'pbe'),
			'not_set' => __('Not set', 'pbe'),
		);
		return $condtions;
	}

	function get_custom_fields()
	{
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT DISTINCT(meta_key)
			FROM {$wpdb->postmeta}
			ORDER BY meta_key ASC"
		);

		$data = array();
		foreach ((array) $rows as $key => $meta) {
			$data[$meta->meta_key] = $meta->meta_key;
		}
		return $data;
	}

	function get_filter_meta_data_types()
	{
		$condtions = array(
			'number' => __('Number', 'pbe'),
			'meta_string' => __('Text', 'pbe'),
		);
		return $condtions;
	}

	function get_product_type_types()
	{
		return wc_get_product_types();
	}

	function get_number_actions()
	{
		$condtions = array(
			'set_new'          => __('Set new', 'pbe'),
			'increase_val'     => __('Increase by fixed amount', 'pbe'),
			'increase_percent' => __('Increase by %', 'pbe'),
			'decrease_val'     => __('Decrease by fixed amount', 'pbe'),
			'decrease_percent'  => __('Decrease by %', 'pbe'),
			'set_null'         => __('Set null', 'pbe'),
		);
		return $condtions;
	}

	function get_string_actions()
	{
		$condtions = array(
			'append'  => __('Append', 'pbe'),
			'prepend' => __('Prepend', 'pbe'),
			'replace'  => __('Replace', 'pbe'),
			'set_new' => __('Set new', 'pbe'),
		);
		return $condtions;
	}

	function get_tax_actions()
	{
		$condtions = array(
			'add'      => __('Add', 'pbe'),
			'remove'   => __('Remove', 'pbe'),
			'set_new'  => __('Set new', 'pbe'),
			'set_null' => __('Set null', 'pbe'),
		);
		return $condtions;
	}


	public function readable_conditions($conditions)
	{
		if (!is_array($conditions)) {
			return;
		}

		foreach ($conditions as $condition) {
			echo '<div class="cond-row">';
			$this->readable_condition($condition);
			echo '</div>';
		}
	}

	function readable_value($value)
	{

		if (empty($value) || !$value) {
			echo '<span class="cond-val val-empty">' . __('Empty', 'pbe') . '</span>';
		}
		if (!is_array($value)) {
			echo '<span class="cond-val">' . esc_html($value) . '</span>';
		} else {
			echo '<span class="cond-val">';
			$values = array();
			foreach ($value as $k => $v) {
				$values[] = '<span class="sub-val">' . esc_html($v) . '</span>';
			}
			echo join('<span class="sub-glue">' . __('and', 'pbe') . '</span>', $values);
			echo '</span>';
		}
	}

	function readable_task_val($task)
	{
		if ('replace' == $task->task_action) {
			echo '<span class="task-action-val">';
			echo esc_html($task->task_old_val);
			echo '</span> ';
			_e('width', 'pbe');
		}
		echo ' <span class="task-action-val">';
		$val = pbe()->maybe_json_decode($task->task_new_val);
		if (empty($val)) {
			echo '<span class="val-empty">' . __('Empty', 'pbe') . '</span>';
		} elseif (!is_array($val)) {
			echo esc_html($val);
		} else {
			echo esc_html(json_encode($val));
		}
		echo '</span>';
	}

	function readable_task_action($task)
	{
		$action_number = pbe()->conditions->get_number_actions();
		$action_string = pbe()->conditions->get_string_actions();
		if ('add' == $task->task_action) {
			echo __('Add', 'pbe');
		} elseif ('remove' == $task->task_action) {
			echo __('Remove', 'pbe');
		} elseif ('number' == $task->task_val_type) {
			echo __('Set to', 'pbe');
		} elseif ('number' == $task->task_val_type) {
			echo esc_html(isset($action_number[$task->task_action]) ? $action_number[$task->task_action] : $task->task_action);
		} else {
			echo esc_html(isset($action_string[$task->task_action]) ? $action_string[$task->task_action] : $task->task_action);
		}
	}

	function readable_task_field($task)
	{
		if (is_array($task->task_edit_field) && !empty($task->task_edit_field)) {
			echo esc_html($task->task_edit_field['title']);
		} else {
			_e('Nothing', 'pbe');
		}
	}

	public function readable_task_conditions($task)
	{
		pbe()->conditions->readable_conditions($task->task_find_fields);
		if (!empty($task->task_variable_fields) && !pbe()->task->is_skip_vartiations($task)) {
			echo _e('Variation fields:', 'pbe');
			pbe()->conditions->readable_conditions($task->task_variable_fields);
		}
		if ($task->task_extra['skip_parent']) {
			echo '<div class="cond-row">' . __('Apply for product variations only.', 'pbe') . '</div>';
		}
	}

	public function readable_condition($condition)
	{
		$condition = wp_parse_args(
			$condition,
			array(
				'field' => '',
				'cond' => '',
				'val' => '',
				'meta_key' => '',
				'meta_type' => '',
			)
		);

		if ('all' == $condition['field']) {
			echo '<span class="cond-title">' . __('All', 'pbe') . '</span>';
			return;
		}

		$filter_fields = $this->get_filter_fields();
		$string_conds = $this->get_filter_string_conditions();
		$number_conds = $this->get_filter_number_conditions();

		if (!$condition['field']) {
			return;
		}
		$field = isset($filter_fields[$condition['field']]) ? $filter_fields[$condition['field']] : false;
		if (!$field) {
		} else {

			echo '<span class="cond-title">' . esc_attr($field['title']) . '</span>';
			switch ($field['type']) {
				case 'number':
					echo '<span class="cond-label">' . esc_html($number_conds[$condition['cond']]) . '</span>';
					$this->readable_value($condition['val']);
					break;
				case 'string':
				case 'meta_string':
					if (isset($string_conds[$condition['cond']])) {
						echo '<span class="cond-label">' . esc_html($string_conds[$condition['cond']]) . '</span>';
					}
					$this->readable_value($condition['val']);
					break;
				case 'custom_select':
					$this->readable_value($condition['val']);
					break;
				case 'custom_field':
					echo '<span class="cond-meta-key">' . esc_html($condition['meta_key']) . '</span>';
					if ('meta_string' == $condition['meta_type'] || 'string' == $condition['meta_type']) {
						echo '<span class="cond-label">' . esc_html($string_conds[$condition['cond']]) . '</span>';
					} else {
						echo '<span class="cond-label">' . esc_html($number_conds[$condition['cond']]) . '</span>';
					}
					$this->readable_value($condition['val']);
					break;
				default:
					echo '<span class="cond-label">' . esc_html($condition['cond']) . '</span>';
					$this->readable_value($condition['val']);
			}
		}
	}
}
