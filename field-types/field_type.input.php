<?php

namespace ionmvc\packages\form\field_types;

use ionmvc\classes\input as user_input;
use ionmvc\classes\html;
use ionmvc\classes\html\tag;

class input extends \ionmvc\packages\form\classes\field {

	public $tag;

	protected $config = array(
		'type' => 'text',
		'fill' => true
	);

	public function __construct( $config ) {
		parent::__construct( $config );
		$this->tag = new tag('input');
	}

	public function type( $type ) {
		$this->config['type'] = $type;
		return $this;
	}

	public function config( $config ) {
		$this->config = array_merge( $this->config,$config );
		return $this;
	}

	public function tag( $attrs ) {
		if ( is_array( $attrs ) ) {
			$this->tag->attrs( $attrs );
		}
		elseif ( $attrs instanceof \Closure ) {
			call_user_func( $attrs,$this->tag );
		}
		return $this;
	}

	public function render() {
		$this->tag->name( $this->data['name'] )->type( $this->config['type'] )->id( $this->data['id'] );
		if ( $this->config['fill'] && ( $field_value = $this->form->prev_input( $this->data['key'] ) ) !== false ) {
			switch( $this->config['type'] ) {
				case 'text':
				case 'password':
					$this->tag->value( html::entity_encode( $field_value ) );
				break;
				case 'checkbox':
				case 'radio':
					if ( !isset( $this->tag->value ) ) {
						break;
					}
					if ( $field_value === (string) $this->tag->value ) {
						$this->tag->checked();
					}
				break;
			}
		}
		return $this->tag->render();
	}

}

?>