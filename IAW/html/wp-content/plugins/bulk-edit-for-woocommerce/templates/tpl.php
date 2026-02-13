<script type="text/html" id="tpl-pbe-string-actions">
	<select id="pbe-action-edit-action" name="{{ data.name }}">
		<?php foreach ( pbe()->conditions->get_string_actions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-number-actions">
	<select id="pbe-action-edit-action" name="{{ data.name }}">
		<?php foreach ( pbe()->conditions->get_number_actions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-tax-actions">
	<select id="pbe-action-edit-action" name="{{ data.name }}">
		<?php foreach ( pbe()->conditions->get_tax_actions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-number-extra-actions"></script>


<script type="text/html" id="tpl-pbe-select">
	<# 
	if ( ! data.multiple  ) {
		data.multiple  = false;
	} 
	#>
	<select name="{{ data.name }}" <# if ( data.multiple ) { #>  multiple="multiple" <# } #> class="{{ data.class }} input-select">
		<# _.each( data.options, function( label, key ){  #>
		<option value="{{ key }}">{{ label }}</option>
		<# } ); #>
	</select>
</script>


<script type="text/html" id="tpl-pbe-select-tax">
	<# 
	if ( ! data.multiple  ) {
		data.multiple  = false;
	} 
	#>
	<div class="pbe-select-tax">
		<p>
		<select name="{{ data.name }}" <# if ( data.multiple ) { #>  multiple="multiple" <# } #> class="{{ data.class }} input-select">
			<# _.each( data.options, function( label, key ){  #>
			<option value="{{ key }}">{{ label }}</option>
			<# } ); #>
		</select>
		</p>
		<# if( data.settings.edit_attribute ) { #>
		<p>
			<label><input type="checkbox" value="yes" name="edit_field_extra[pa_is_visible]"/><?php _e( 'Visible on the product page', 'pbe' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" value="yes" name="edit_field_extra[pa_is_variation]"/><?php _e( 'Used for variations', 'pbe' ); ?></label>
		</p>
		<# } #>
	</div>
</script>

<script type="text/html" id="tpl-pbe-input">
	<# 

	if ( ! data.type  ) {
		data.type  = 'text';
	} 
	#>
	<div class="pbe-input-normal">
		<p>
			<# if ( data.label ) { #> 
			<label class="input-label">{{ data.label  }}</label>
			<# } #>
			<input class="{{ data.class }} input-text" autocomplete="off" placeholder="{{ data.placeholder }}" value="{{ data.value }}" type="{{ data.type }}" name="{{ data.name }}"/>
		</p>
		<# if ( data.id_type === 'number' ) { #> 
			<div class="number-extra-actions action-row"> 
				<p>
					<label><input type="checkbox" value="yes" name="edit_field_extra[number_round]"/><?php _e( 'And round to nearest', 'pbe' ); ?></label>
					<input type="text" name="edit_field_extra[number_nearest]" class="pbe-small-input" placeholder=".99" value=".99" /> <label><?php _e( 'After decimal points.', 'pbe' ); ?></label>
				</p>
				<# if ( data.set_price ) { #>
				<p>
					<label><input type="checkbox" value="yes" name="edit_field_extra[set_sale_price]"/><?php _e( 'And set old value as sale price if new value greater than old value.', 'pbe' ); ?></label>
				</p>
				<# } #>
			</div>
		<# } #>
	</div>

</script>

<script type="text/html" id="tpl-pbe-replace-input">
	<input class="{{ data.class }} input-find-text input-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Find text', 'pbe' ); ?>" value="{{ data.value_find }}" type="{{ data.type }}" name="{{ data.name }}_old"/>
	<input class="{{ data.class }} input-val-text input-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Replace with text', 'pbe' ); ?>" value="{{ data.value_replace }}" type="{{ data.type }}" name="{{ data.name }}"/>
</script>

<script type="text/html" id="tpl-pbe-image">
	<div class="pbe-edit-image {{ data.class }}">
		<input class="input-text attachment-id" value="{{ data.value }}" type="hidden" name="{{ data.name }}"/>
		<div class="preview-image pbe-image-preview">
			<span class="dashicons dashicons-format-image"></span>
		</div>
	</div>
	<p>
		<label><input name="edit_field_extra[use_image_same_sku]" value="yes" type="checkbox"/> <?php _e( 'Use image which has title same product sku as product thumbnail.', 'pbe' ); ?></label>
	</p>
</script>


<script type="text/html" id="tpl-pbe-date">
	<# 
	if ( ! data.type  ) {
		data.type  = 'text';
	} 
	#>
	<# if ( data.label ) { #> 
	<label>{{ data.label  }}</label>
	<# } #>
	<input class="{{ data.class }} input-text" placeholder="{{ data.placeholder }}" value="{{ data.value }}" type="{{ data.type }}" name="{{ data.name }}"/>
	<input class="{{ data.class }} input-text" placeholder="{{ data.placeholder }}" value="{{ data.value }}" type="{{ data.type }}" name="{{ data.name }}"/>
</script>



<script type="text/html" id="tpl-pbe-find-field">
<# console.log( 'data.fields', data.fields ); #>
	<div class="pbe-find-input-field" data-id="{{ data.id }}">
		<div class="pbe-input-wrap field-type">
			<select name="{{ data.groupName }}[{{ data.id }}][field]" id="f-{{ data.id }}" class="input-field-type wide">
				<# _.each( data.fields, function( args, id ){ #>
					<# if( typeof args.edit_only === "undefined" || ! args.edit_only ){ #>
					<option value="{{ id }}">{{ args.title }}</option>
					<# } #>
				<# }); #>
			</select>
		</div>
		<div class="pbe-input-wrap field-cond">
		</div>
		<div class="pbe-input-wrap field-val">
		</div>
		<div class="pbe-input-wrap pde-remove-col">
			<span class="pde-remove-cond"><span class="dashicons dashicons-no-alt"></span></span>
		</div>
	</div>
</script>




<script type="text/html" id="tpl-pbe-input-select">
	<select class="input-field-val input-select wide" name="{{ data.groupName }}[{{ data.id }}][val]">
	</select>
</script>

<script type="text/html" id="tpl-pbe-input-text">
	<input class="input-field-val input-text wide" name="{{ data.groupName }}[{{ data.id }}][val]"/>
</script>

<script type="text/html" id="tpl-pbe-val-between">
	<input class="input-field-val input-between half input_from" type="number" placeholder="<?php esc_attr_e( 'From', 'pbe' ); ?>" name="fields[{{ data.id }}][val][from]"/>
	<input class="input-field-val input-between half input_to" type="number" placeholder="<?php esc_attr_e( 'To', 'pbe' ); ?>" name="fields[{{ data.id }}][val][to]"/>
</script>

<script type="text/html" id="tpl-pbe-number-conditions">
	<select class="input-field-cond wide" name="{{ data.groupName }}[{{ data.id }}][cond]">
		<?php foreach ( pbe()->conditions->get_filter_number_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-date-conditions">
	<select class="input-field-cond wide" name="{{ data.groupName }}[{{ data.id }}][cond]">
		<?php foreach ( pbe()->conditions->get_filter_date_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-string-conditions">
	<select name="{{ data.groupName }}[{{ data.id }}][cond]" class="input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_filter_string_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-meta_string-conditions">
	<select name="{{ data.groupName }}[{{ data.id }}][cond]" class="input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_filter_meta_string_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-product_type-conditions">
	<select name="{{ data.groupName }}[{{ data.id }}][cond]" class="input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_filter_select_types_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-product_type-values">
	<select name="{{ data.groupName }}[{{ data.id }}][val][]" multiple="multiple" class="input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_product_type_types() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-tax-conditions">
	<select name="{{ data.groupName }}[{{ data.id }}][cond]" class="input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_filter_tax_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>


<script type="text/html" id="tpl-pbe-tax-select">
	<select name="{{ data.groupName }}[{{ data.id }}][val]" class="select2 input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_filter_tax_conditions() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>


<script type="text/html" id="tpl-pbe-meta_data_types-select">
	<select name="{{ data.groupName }}[{{ data.id }}][meta_type]" class="select2 input-field-meta-type wide">
		<?php foreach ( pbe()->conditions->get_filter_meta_data_types() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-custom_fields-select">
	<select name="{{ data.groupName }}[{{ data.id }}][meta_key]" class="select2 input-field-cond wide">
		<?php foreach ( pbe()->conditions->get_custom_fields() as $id => $label ) { ?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>

<script type="text/html" id="tpl-pbe-custom-val-custom_select">
	<select name="{{ data.groupName }}[{{ data.id }}][val]" class="select2 input-field-val input-select wide">
		<# _.each( data.options, function( label, key ){  #>
		<option value="{{ key }}">{{ label }}</option>
		<# } ); #>
	</select>
</script>


<!-- if_is_premium -->
<script type="text/html" id="tpl-pbe-gallery">
	<div class="pbe-edit-gallery {{ data.class }}">
		<input class="input-text attachment-id" value="{{ data.value }}" type="hidden" name="{{ data.name }}"/>
		<div class="preview-image pbe-image-preview">
			<a href="#" class="g-edit"><span class="dashicons dashicons-format-gallery"></span></a>
		</div>
	</div>
	<p>
		<label><input name="edit_field_extra[use_image_same_sku]" value="yes" type="checkbox"/> <?php _e( 'Use all images which have title start by product sku as gallery.', 'pbe' ); ?></label>
	</p>
</script>

<script type="text/html" id="tpl-pbe-files">
	<div class="pbe-edit-files {{ data.class }}">
		<input class="input-text attachment-id" value="{{ data.value }}" type="hidden" name="{{ data.name }}"/>
		<table class="widefat">
				<thead>
					<tr>
						<th class="sort">&nbsp;</th>
						<th><?php _e( 'File Name', 'pbe' ); ?></th>
						<th colspan="2"><?php _e( 'File URL', 'pbe' ); ?></th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody class="ui-sortable">
					<tr class="pbe-edit-file">
						<td class="sort"></td>
						<td class="file_name">
							<input type="text" class="input_text attachment-name" placeholder="<?php echo esc_attr_e( 'File name', 'pbe' ); ?>" name="{{ data.name }}[names][]" value="">
						</td>
						<td class="file_url"><input type="text" class="input_text attachment-url" placeholder="http://" name="{{ data.name }}[urls][]" value=""></td>
						<td class="file_url_choose" width="1%"><a href="#" class="button upload_file_button"><?php _e( 'Choose file', 'pbe' ); ?></a></td>
						<td width="1%"><a href="#" class="delete"><?php _e( 'Delete', 'pbe' ); ?></a></td>
					</tr>
				</tbody>

				<tfoot>
					<tr>
						<th colspan="5">
							<a href="#" class="button insert" data-row=""><?php _e( 'Add File', 'pbe' ); ?></a>
						</th>
					</tr>
				</tfoot>
			</table>

	</div>
</script>

<script type="text/html" id="tpl-pbe-files-actions">
	<select id="pbe-action-edit-action" name="{{ data.name }}">
		<?php
		foreach ( array(
			'append'  => __( 'Append', 'pbe' ),
			'prepend' => __( 'Prepend', 'pbe' ),
			'set_new' => __( 'Set new', 'pbe' ),
		) as $id => $label ) {
			?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>


<script type="text/html" id="tpl-pbe-products-actions">
	<select id="pbe-action-edit-action" name="{{ data.name }}">
		<?php
		foreach ( array(
			'set_new' => __( 'Set new', 'pbe' ),
			'append'  => __( 'Append', 'pbe' ),
			'prepend' => __( 'Prepend', 'pbe' ),
		) as $id => $label ) {
			?>
		<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php } ?>
	</select>
</script>


<script type="text/html" id="tpl-pbe-delete">
	<div class="pbe-edit-delete {{ data.class }}"></div>
</script>

<script type="text/html" id="tpl-pbe-products">
	<select name="{{ data.name }}[]" multiple="multiple" class="select-products select2 input-field-cond wide"> </select>
</script>

<!-- /if_is_premium -->
