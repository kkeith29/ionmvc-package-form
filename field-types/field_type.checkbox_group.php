<?php

namespace ionmvc\packages\form\field_types;

use ionmvc\classes\input as user_input;
use ionmvc\classes\html;
use ionmvc\classes\html\tag;
use ionmvc\packages\form\classes\validator;

class checkbox_group extends \ionmvc\packages\form\classes\field {

	protected $values = array();
	protected $fields = array();
	protected $config = array(
		'fill' => true
	);

	public function values( $values ) {
		$this->values = $values;
		$i = 1;
		foreach( $this->values as $value => $label ) {
			$this->fields[$i] = $this->form->field( "{$this->data['name']}[{$i}]",'input',false )->label( $label )->type('checkbox')->tag(array('value'=>$value));
			$i++;
		}
		return $this;
	}

	public function config( $config ) {
		$this->config = array_merge( $this->config,$config );
		return $this;
	}

	public function get_field( $idx ) {
		if ( isset( $this->fields[$idx] ) ) {
			return $this->fields[$idx];
		}
		return false;
	}

	public function get_fields() {
		return $this->fields;
	}

	public function validate() {
		$values = array_keys( $this->values );
		$this->ruleset->set_message('in_values','%s contains an invalid value');
		$this->ruleset->add_validator('in_values',function( $_values ) use( $values ) {
			foreach( $_values as $value ) {
				if ( !in_array( $value,$values ) ) {
					return array(
						'message_id' => 'in_values'
					);
				}
			}
			return true;
		});
		$validator = new validator\field( $this,$this->ruleset );
		return $validator->run();
	}

	public function render() {
		/*$this->tag->name( $this->data['name'] )->type( $this->config['type'] )->id( $this->data['id'] );
		if ( $this->config['fill'] && $this->type !== 'file' && ( $field_value = $this->form->prev_input( $this->data['name'] ) ) !== false ) {
			$this->tag->value( html::entity_encode( $field_value ) );
		}
		return $this->tag->render();*/
		//generate columns of checkboxes
		return '';
	}

}

?>