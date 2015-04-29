<?php

namespace ionmvc\packages\form\field_types;

use ionmvc\classes\input as user_input;
use ionmvc\classes\html;
use ionmvc\classes\html\tag;

class textarea extends \ionmvc\packages\form\classes\field {

	public $tag;

	protected $config = array(
		'fill' => true
	);

	public function __construct( $config ) {
		parent::__construct( $config );
		$this->tag = new tag('textarea');
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

	public function config( $config ) {
		$this->config = array_merge( $this->config,$config );
		return $this;
	}

	public function render() {
		$this->tag->id( $this->id )->name( $this->name );
		if ( !$this->tag->has_attr('cols') ) {
			$this->tag->cols(50);
		}
		if ( !$this->tag->has_attr('rows') ) {
			$this->tag->rows(5);
		}
		if ( $this->config['fill'] && ( $field_value = $this->form->prev_input( $this->data['key'] ) ) !== false ) {
			$this->tag->inner_content( html::entity_encode( $field_value ) );
		}
		return $this->tag->render();
	}

}

?>