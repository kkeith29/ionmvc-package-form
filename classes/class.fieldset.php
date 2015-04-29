<?php

namespace ionmvc\packages\form\classes;

class fieldset {

	protected static $count = 0;

	protected $data = array(
		'valid' => false
	);
	protected $ruleset = null;
	protected $order = array();
	protected $fields = array();
	protected $upload_fields = array();

	public function __construct( $form,$name=false ) {
		$this->data['form'] = $form;
		if ( $name !== false ) {
			$this->data['name'] = $name;
		}
		$this->data['id'] = ++self::$count;
	}

	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	public function __get( $key ) {
		if ( !isset( $this->data[$key] ) ) {
			return false;
		}
		return $this->data[$key];
	}

	public function __set( $key,$value ) {
		$this->data[$key] = $value;
	}

	public function __unset( $key ) {
		if ( !isset( $this->data[$key] ) ) {
			return;
		}
		unset( $this->data[$key] );
	}

	public function add( $type,$data ) {
		$data['type'] = $type;
		$this->order[] = $data;
	}

	public function add_field( $field_key ) {
		$this->fields[] = $field_key;
	}

	public function get_fields() {
		return $this->fields;
	}

	public function add_upload_field( $field_key ) {
		$this->upload_fields[] = $field_key;
	}

	public function get_upload_fields() {
		return $this->upload_fields;
	}

	public function get_ruleset() {
		if ( is_null( $this->ruleset ) ) {
			return false;
		}
		return $this->ruleset;
	}

	public function rules( $rules ) {
		if ( is_null( $this->ruleset ) ) {
			$this->ruleset = new ruleset;
		}
		if ( is_string( $rules ) ) {
			$this->ruleset->parse_string( $rules );
		}
		elseif ( $rules instanceof \Closure ) {
			call_user_func( $rules,$this->ruleset );
		}
		return $this;
	}

	public function validate() {
		$passed = true;
		foreach( $this->order as $item ) {
			switch( $item['type'] ) {
				case form::type_fieldset:
					$fieldset = $this->form->fieldset( $item['fieldset'] );
					$result = $fieldset->validate();
					if ( $result !== validator::passed ) {
						$passed = false;
						break;
					}
					/*if ( ( $ruleset = $fieldset->get_ruleset() ) !== false ) {
						$validator = new validator\fieldset( $fieldset,$ruleset );
						$result = $validator->run();
						if ( $result !== validator::passed ) {
							$passed = false;
							break;
						}
					}
					$fieldset->valid = true;*/
				break;
				case form::type_field:
					$field = $this->form->field( $item['field'] );
					$result = $field->validate();
					if ( $result !== validator::passed ) {
						$passed = false;
						break;
					}
					$field->valid = true;
				break;
			}
		}
		if ( !$passed ) {
			return validator::failed;
		}
		$upload_fields = $this->upload_fields;
		if ( count( $upload_fields ) > 0 ) {
			if ( is_null( $this->ruleset ) ) {
				$this->rules(function( $rules ) use( $upload_fields ) {
					$rules->finalize_uploads( $upload_fields );
				});
			}
			else {
				$this->ruleset->finalize_uploads( $upload_fields );
			}
		}
		if ( !is_null( $this->ruleset ) ) {
			$validator = new validator\fieldset( $this,$this->ruleset );
			$result = $validator->run();
			if ( $result !== validator::passed ) {
				$passed = false;
			}
		}
		if ( !$passed ) {
			return validator::failed;
		}
		$this->valid = true;
		return validator::passed;
	}

}

?>