<?php

namespace ionmvc\packages\form\classes;

use ionmvc\classes\input;

class field {

	protected $data = array(
		'label' => '',
		'valid' => false
	);
	protected $ruleset = null;

	public function __construct( $data ) {
		$this->data = array_merge( $this->data,$data );
		$this->data['id'] = $this->data['form']->id() . '-' . str_replace( '.','_',$this->data['key'] );
		$this->ruleset = new ruleset;
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

	public function label( $text ) {
		$this->data['label'] = $text;
		return $this;
	}

	public function rules( $rules ) {
		if ( is_string( $rules ) ) {
			$this->ruleset->parse_string( $rules );
		}
		elseif ( $rules instanceof \Closure ) {
			call_user_func( $rules,$this->ruleset );
		}
		return $this;
	}

	public function get_ruleset() {
		return $this->ruleset;
	}

	public function validate() {
		$validator = new validator\field( $this,$this->ruleset );
		return $validator->run();
	}

}

?>