<?php

namespace ionmvc\packages\form\field_types;

use ionmvc\classes\input as user_input;
use ionmvc\classes\html;
use ionmvc\classes\html\tag;
use ionmvc\exceptions\app as app_exception;
use ionmvc\packages\form\classes\validator;

class select extends \ionmvc\packages\form\classes\field {

	public $tag;

	protected $config = array(
		'empty_option'       => true,
		'empty_option_value' => '',
		'select'             => true,
		'multiple'           => false
	);
	protected $options = null;
	protected $optgroups = null;

	public function __construct( $config ) {
		parent::__construct( $config );
		$this->tag = new tag('select');
	}

	public function config( $config ) {
		$this->config = array_merge( $this->config,$config );
		return $this;
	}

	public function multiple() {
		$this->config['multiple'] = true;
		return $this;
	}

	public function options( $options ) {
		if ( !is_array( $options ) ) {
			throw new app_exception('Options must be an array');
		}
		$this->options = $options;
		return $this;
	}

	public function optgroup( $title,$options ) {
		if ( !is_array( $options ) ) {
			throw new app_exception('Options must be an array');
		}
		$this->optgroups[] = compact('title','options');
	}

	public function optgroups( $options ) {
		foreach( $options as $group ) {
			if ( !isset( $group['title'] ) ) {
				throw new app_exception('Option group is not in proper format, no title key found');
			}
			if ( !isset( $group['options'] ) ) {
				throw new app_exception('Option group is not in proper format, no options key found');
			}
			$this->optgroup( $group['title'],$group['options'] );
		}
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

	public function validate() {
		if ( !is_null( $this->options ) ) {
			$options = array_keys( $this->options );
		}
		elseif ( !is_null( $this->optgroups ) ) {
			$options = array();
			foreach( $this->optgroups as $group ) {
				foreach( $group['options'] as $value => $label ) {
					$options[] = $value;
				}
			}
		}
		else {
			throw new app_exception('Options or optgroups need to be defined');
		}
		$this->ruleset->set_message('in_options','%s is not a valid option');
		$this->ruleset->add_validator('in_options',function( $value ) use( $options ) {
			if ( !in_array( $value,$options ) ) {
				return array(
					'message_id' => 'in_options'
				);
			}
			return true;
		});
		$validator = new validator\field( $this,$this->ruleset );
		return $validator->run();
	}

	protected function handle_options( tag $parent,$options ) {
		$field_value = (string) $this->form->prev_input( $this->data['name'] );
		foreach( $options as $value => $label ) {
			$value = (string) $value;
			$option = tag::create('option')->value( html::entity_encode( $value ) )->inner_content( $label );
			if ( $this->config['select'] ) {
				if ( ( $this->config['multiple'] && in_array( $value,$field_value ) ) || $value === $field_value ) {
					$option->selected();
				}
			}
			$parent->child_add( $option );
			unset( $option,$value,$label );
		}
		unset( $field_value );
	}

	public function render() {
		$this->tag->id( $this->data['id'] )->name( $this->data['name'] . ( $this->config['multiple'] ? '[]' : '' ) );
		if ( $this->config['empty_option'] ) {
			$this->tag->child_add( tag::create('option')->value('')->inner_content( $this->config['empty_option_value'] ) );
		}
		if ( !is_null( $this->options ) ) {
			$this->handle_options( $this->tag,$this->options );
		}
		elseif ( !is_null( $this->optgroups ) ) {
			foreach( $this->optgroups as $group ) {
				$group_tag = tag::create('optgroup')->label( $group['title'] );
				$this->handle_options( $group_tag,$group['options'] );
				$this->tag->child_add( $group_tag );
				unset( $group_tag,$group );
			}
		}
		return $this->tag->render();
	}

}

?>