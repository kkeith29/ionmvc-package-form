<?php

namespace ionmvc\packages\form\field_types;

use ionmvc\classes\asset;
use ionmvc\classes\file as file_class;
use ionmvc\classes\input as user_input;
use ionmvc\classes\html;
use ionmvc\classes\html\tag;
use ionmvc\packages\form\classes\validator;
use ionmvc\packages\jquery\classes\jquery;

class file extends \ionmvc\packages\form\classes\field {

	protected static $asset_loaded = false;

	public $tag;

	protected $config = array(
		'filename_length' => 20,
		'temp_directory'  => 'storage:files/temp'
	);

	public function __construct( $config ) {
		parent::__construct( $config );
		$this->form->tag('form')->enctype('multipart/form-data');
		$this->tag = new tag('input');
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

	public function validate() {
		if ( !$this->ruleset->has_rule('upload') ) {
			$this->ruleset->upload( $this->config );
		}
		$validator = new validator\field( $this,$this->ruleset,array(
			'file' => true
		) );
		return $validator->run();
	}

	public function render() {
		$this->tag->name( $this->data['name'] )->type('file')->id( $this->data['id'] );
		if ( ( $field_value = $this->form->prev_input( $this->data['key'] ) ) !== false ) {
			if ( !self::$asset_loaded ) {
				jquery::load();
				asset::add('form/field-types/file/script.js');
				self::$asset_loaded = true;
			}
			$div = tag::create('div')->class_add('f--file');
			$name = tag::create('div')->class_add('f--f-name')->inner_content( $field_value['original_name'] );
			$size = tag::create('div')->class_add('f--f-size')->inner_content( file_class::format_filesize( $field_value['size'] ) );
			$delete = tag::create('a')->class_add('f--f-delete')->data('id',$this->data['id'])->inner_content('Delete');
			$div->child_add( $name );
			$div->child_add( $size );
			$div->child_add( $delete );
			$this->form->set_input( $this->data['key'],$field_value );
			return $div->render() . $this->tag->style('display:none')->render();
		}
		return $this->tag->render();
	}

}

?>