<?php

$config = array(
	'form' => array(
		'default_profile' => 'default',
		'profiles' => array(
			'default' => array(
				'assets' => array(
					'css' => 'ionmvc-asset-css:form.css',
					'tab-css' => 'ionmvc-asset-css:form/tabs.css',
					'reorder-css' => 'ionmvc-asset-css:form/reorder.css',
					'reorder-js' => 'ionmvc-asset-js:form/reorder.js'
				),
				'security' => array(
					'timeout-max' => false
				)
			)
		),
		'field' => array(
			'error_messages' => array(
				'required'            => '%s is required',
				'min_length'          => '%s must be longer than or equal to %d characters',
				'max_length'          => '%s must be shorter than or equal to %d characters',
				'exact_length'        => '%s must be exactly %d characters long',
				'matches_field'       => '%s does not match %s',
				'numeric'             => '%s must be numeric',
				'phone'               => '%s must be in the format: %s',
				'email'               => '%s is invalid',
				'url'                 => '%s is not a valid url',
				'password'            => '%s is not valid - reason: %s',
				'password_reason_1'   => 'must be at least %s characters long',
				'password_reason_2'   => 'uppercase letter',
				'password_reason_3'   => 'lowercase letter',
				'password_reason_4'   => 'number',
				'password_reason_5'   => 'special character',
				'upload_choose_file'  => '%s - Please choose a file to upload',
				'upload_invalid_file' => '%s - File is not valid',
				'upload_invalid_extn' => '%s - File extension not allowed. Only %s allowed.',
				'upload_invalid_img'  => '%s - File is not a valid image',
				'upload_invalid_size' => '%s - File exceeds the maximum file size of %s',
				'upload_image_small'  => '%s - Image dimensions must be greater than %spx x %spx',
				'upload_image_small_width'  => '%s - Image width must be greater than %spx',
				'upload_image_small_height' => '%s - Image height must be greater than %spx',
				'upload_image_large'  => '%s - Image dimensions must be less than %spx x %spx',
				'upload_image_large_width'  => '%s - Image width must be less than %spx',
				'upload_image_large_height' => '%s - Image height must be less than %spx',
				'upload_copy_error'   => '%s - Could not upload the file, please try again'
			),
			'validators' => array()
		),
		'fieldset' => array(
			'error_messages' => array(
				'uploads_failed' => 'File upload has failed, please try again'
			),
			'validators' => array()
		)
	)
);

?>