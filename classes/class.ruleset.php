<?php

namespace ionmvc\packages\form\classes;

use ionmvc\classes\array_func;
use ionmvc\classes\config\string as config_string;

class ruleset {

	private $validators = array();
	private $rules = array();
	private $messages = array();

	public function __call( $rule,$args ) {
		if ( $rule === 'rule' ) {
			$rule = array_shift( $args );
		}
		$count = count( $args );
		$this->rules[$rule] = ( $count === 0 ? true : ( $count === 1 ? $args[0] : $args ) );
		return $this;
	}

	public function parse_string( $rules ) {
		$this->rules = array_func::merge_recursive_distinct( $this->rules,config_string::parse( $rules ) );
	}

	public function add_validator( $rule,\Closure $function ) {
		$this->validators[$rule] = $function;
		return $this;
	}

	public function get_validators() {
		return $this->validators;
	}

	public function has_rule( $rule ) {
		return isset( $this->rules[$rule] );
	}

	public function get_rules() {
		return $this->rules;
	}

	public function set_message( $id,$message ) {
		$this->messages[$id] = $message;
		return $this;
	}

	public function get_messages() {
		return $this->messages;
	}

}

?>