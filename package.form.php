<?php

namespace ionmvc\packages;

class form extends \ionmvc\classes\package {

	const version = '1.0.0';
	const class_type_field_type = 'ionmvc.form_field_type';
	const class_type_view       = 'ionmvc.form_view';

	public function setup() {
		$this->add_type('field-type',array(
			'type' => self::class_type_field_type,
			'type_config' => array(
				'file_prefix' => 'field_type'
			),
			'path' => 'field-types'
		));
		$this->add_type('view',array(
			'type' => self::class_type_view,
			'type_config' => array(
				'file_prefix' => 'view'
			),
			'path' => 'views'
		));
	}

	public static function package_info() {
		return array(
			'author'      => 'Kyle Keith',
			'version'     => self::version,
			'description' => 'Form handler',
			'require'     => array(
				'session' => array('1.0.0','>=')
			)
		);
	}

}

?>