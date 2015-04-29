<?php

namespace ionmvc\packages\form\classes;

use ionmvc\classes\asset;
use ionmvc\classes\html\tag;

class view {

	const type_errors = 1;
	const type_row    = 2;
	const type_field  = 3;
	const type_html   = 4;

	protected $form;
	protected $config = array(
		'css' => 'form/css/main.css'
	);
	protected $render_order = array();
	protected $order = array();

	protected $rows = array();

	protected $errors = false;

	public function __construct( form $form ) {
		$this->form = $form;
		$this->order =& $this->render_order;
	}

	protected function setup() {}

	public function errors( $group=null ) {
		$this->order[] = array(
			'type'  => self::type_errors,
			'group' => ( is_null( $group ) ? form::error_group_main : $group )
		);
		$this->errors = true;
	}

	public function row( \Closure $function ) {
		$idx = count( $this->rows );
		$this->rows[$idx] = array(
			'order' => array()
		);
		$this->order[] = array(
			'type' => self::type_row,
			'row'  => $idx
		);
		$this->order =& $this->rows[$idx]['order'];
		call_user_func( $function,$this );
		$this->order =& $this->render_order;
		return $this;
	}

	public function field( $name,$config=array() ) {
		$this->order[] = array(
			'type'   => self::type_field,
			'field'  => $name,
			'config' => $config
		);
		return $this;
	}

	public function html( $html ) {
		$this->order[] = array(
			'type' => self::type_html,
			'html' => $html
		);
		return $this;
	}

	protected function render_form( $form,$content ) {
		$div = tag::create('div')->class_add('f-main');
		$div->inner_content( $form->open() );
		$div->inner_content( $content );
		$div->inner_content( $form->close() );
		return $div->render();
	}

	protected function render_errors( $errors ) {
		$div = tag::create('div')->class_add('f-m--errors');
		$label = tag::create('div')->class_add('f-m--e-label')->inner_content('The following errors have been encountered:');
		$list = tag::create('ul')->class_add('f-m--e-list');
		foreach( $errors as $error ) {
			$item = tag::create('li')->class_add('f-m--el-item')->inner_content( $error );
			$list->child_add( $item );
			unset( $item,$error );
		}
		$div->child_add( $label );
		$div->child_add( $list );
		unset( $label,$list );
		return $div->render();
	}

	protected function render_row( $order ) {
		$tag = tag::create('div')->class_add('f-m--row');
		$content = '';
		$c = count( $order );
		$i = 1;
		foreach( $order as $item ) {
			if ( $item['type'] !== self::type_field ) {
				throw new app_exception('Only fields allowed in a row');
			}
			$config = array_merge( $item['config'],array(
				'row_start' => ( $i === 1 ),
				'row_end'   => ( $i === $c )
			) );
			$content .= $this->render_field( $this->form->field( $item['field'] ),$config );
			$i++;
		}
		$tag->inner_content( $content );
		return $tag->render();
	}

	protected function render_field( field $field,$config ) {
		$field_div = tag::create('div')->class_add('f-m--field');
		if ( isset( $config['row_start'] ) && $config['row_start'] ) {
			$field_div->class_add('t-first');
		}
		elseif ( isset( $config['row_end'] ) && $config['row_end'] ) {
			$field_div->class_add('t-last');
		}
		$label_div = tag::create('div')->class_add('f-m--f-label');
		$label     = tag::create('label')->attr( 'for',$field->id )->inner_content( $field->label );
		$label_div->child_add( $label );
		unset( $label );
		$elemt_div = tag::create('div')->class_add('f-m--f-element')->inner_content( $field->render() );
		$field_div->child_add( $label_div );
		$field_div->child_add( $elemt_div );
		unset( $label_div,$elemt_div );
		return $field_div->render();
	}

	protected function render_order( $order ) {
		$html = '';
		foreach( $order as $item ) {
			switch( $item['type'] ) {
				case self::type_errors:
					$errors = $this->form->get_errors( $item['group'] );
					if ( count( $errors ) === 0 ) {
						break;
					}
					$html .= $this->render_errors( $errors );
				break;
				case self::type_row:
					$html .= $this->render_row( $this->rows[$item['row']]['order'] );
				break;
				case self::type_field:
					$html .= $this->render_field( $this->form->field( $item['field'] ),$item['config'] );
				break;
			}
		}
		return $html;
	}

	public function render() {
		if ( !$this->errors ) {
			array_unshift( $this->render_order,array(
				'type'  => self::type_errors,
				'group' => form::error_group_main
			) );
		}
		$this->setup();
		if ( $this->config['css'] !== false ) {
			asset::add( $this->config['css'] );
		}
		return $this->render_form( $this->form,$this->render_order( $this->render_order ) );
	}

}

?>