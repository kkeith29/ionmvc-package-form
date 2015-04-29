<?php

namespace ionmvc\packages\form\classes;

use ionmvc\classes\array_func;
use ionmvc\classes\autoloader;
use ionmvc\classes\func;
use ionmvc\classes\igsr;
use ionmvc\classes\input;
use ionmvc\classes\redirect;
use ionmvc\classes\uri;
use ionmvc\exceptions\app as app_exception;
use ionmvc\packages\form as form_pkg;
use ionmvc\packages\html\classes\html;
use ionmvc\packages\html\classes\tag;
use ionmvc\packages\session\classes\session;

class form {

	const type_fieldset = 1;
	const type_field = 2;

	const error_group_main = 1;

	private static $count = 0;

	private $config = array(
		'method' => 'post'
	);
	private $tags = array();
	private $fields = array();
	private $errors = array(
		'prev' => array(),
		'curr' => array()
	);
	private $input = array(
		'prev' => array(),
		'curr' => array()
	);
	private $token = array(
		'prev' => '',
		'curr' => ''
	);
	private $main_fieldset = array();
	private $fieldsets = array();
	private $fieldset;

	private $error_group = false;

	private $views = array();

	private $submitted = false;
	
	public $data;

	public function __construct( $config=array() ) {
		self::$count++;
		if ( !isset( $config['action'] ) ) {
			$config['action'] = uri::current();
		}
		if ( !isset( $config['id'] ) ) {
			$config['id'] = substr( sha1( $config['action'] . self::$count ),0,8 );
		}
		$this->config = array_merge( $this->config,$config );
		$this->tag_add('form','form');
		
		$this->tags['form']->id( $this->config['id'] )->action( $this->config['action'] )->method( $this->config['method'] );
		
		$this->tag_add('div.hidden_fields','div')->style('display:none');
		
		if ( ( $form_id = input::request('form.id') ) !== false && $form_id === $this->config['id'] ) {
			$this->submitted = true;
		}
		
		$this->data = new igsr;
		if ( session::is_set("forms.{$this->config['id']}") ) {
			$data = session::get("forms.{$this->config['id']}");
			$this->token['prev'] = $data['token'];
			$this->input = $data['input'];
			$this->input['prev'] = $this->input['curr'];
			$this->input['curr'] = array();
			$this->errors = $data['errors'];
			$this->errors['prev'] = $this->errors['curr'];
			$this->errors['curr'] = array();
		}

		$token = $this->token['prev'];
		$this->token['curr'] = sha1(uniqid(rand(),true));
		
		$this->main_fieldset = new fieldset( $this );
		$this->fieldset =& $this->main_fieldset;
		
		$this->field('form[id]','input')->label('Form ID')->type('hidden')->tag(array('value'=>$this->config['id']))->config(array('fill'=>false))->rules('required');
		$this->field('form[token]','input')->label('Form Token')->type('hidden')->tag(array('value'=>$this->token['curr']))->config(array('fill'=>false))->rules(function( $rules ) use( $token ) {
			$rules->required();
			$rules->set_message('token_invalid','%s is not valid, refresh page and try again');
			$rules->check_token(function( $value ) use( $token ) {
				if ( $value !== $token ) {
					return array(
						'message_id' => 'token_invalid'
					);
				}
				return true;
			});
		});
	}

	public function id() {
		return $this->config['id'];
	}

	public function tag_add( $name,$tag ) {
		$this->tags[$name] = new tag( $tag );
		return $this->tags[$name];
	}

	public function tag( $name ) {
		if ( !isset( $this->tags[$name] ) ) {
			throw new app_exception( 'Tag with name \'%s\' does not exist',$name );
		}
		return $this->tags[$name];
	}

	public function normalize_name( $name ) {
		return rtrim( str_replace( array('][','['),'.',$name ),']' );
	}

	public function has_field( $name ) {
		$key = $this->normalize_name( $name );
		return isset( $this->fields[$key] );
	}

	public function field( $name,$field_type=null,$track=true ) {
		$key = $this->normalize_name( $name );
		if ( !isset( $this->fields[$key] ) || !$track ) {
			if ( is_null( $field_type ) ) {
				throw new app_exception( 'Field type is required for field: %s',$name );
			}
			$instance = autoloader::class_by_type( $field_type,form_pkg::class_type_field_type,array(
				'instance' => true,
				'args'     => array(array(
					'form' => $this,
					'name' => $name,
					'key'  => $key
				))
			) );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to load form field type: %s',$field_type );
			}
			if ( isset( $this->fieldset->name ) ) {
				$instance->fieldset = $this->fieldset->name;
				$this->fieldset->add_field( $instance->key );
			}
			$instance->error_group = ( $this->error_group !== false ? $this->error_group : self::error_group_main );
			if ( !$track ) {
				return $instance;
			}
			$this->fieldset->add( self::type_field,array(
				'field' => $key
			) );
			$this->fields[$key] = $instance;
		}
		return $this->fields[$key];
	}

	public function fieldset( $name=null,$function=null ) {
		if ( is_null( $name ) ) {
			return $this->fieldset;
		}
		if ( !is_null( $function ) && !( $function instanceof \Closure ) ) {
			throw new app_exception('Second parameter must be a closure');
		}
		if ( !isset( $this->fieldsets[$name] ) ) {
			$this->fieldsets[$name] = new fieldset( $this,$name );
			$this->fieldset =& $this->fieldsets[$name];
			call_user_func( $function,$this );
			$this->fieldset =& $this->main_fieldset;
			$this->fieldset->add( self::type_fieldset,array(
				'fieldset' => $name
			) );
		}
		return $this->fieldsets[$name];
	}

	public function error_group( $name,\Closure $function ) {
		$this->error_group = $name;
		$this->error_groups[$name] = array();
		call_user_func( $function,$this );
		$this->error_group = false;
		return $this;
	}

	public function add_error( $field_key,$text,$error_group=self::error_group_main ) {
		if ( !isset( $this->errors['curr'][$error_group] ) ) {
			$this->errors['curr'][$error_group] = array();
		}
		$this->errors['curr'][$error_group][$field_key] = $text;
	}

	public function get_errors( $error_group=self::error_group_main ) {
		if ( !isset( $this->errors['prev'][$error_group] ) ) {
			return array();
		}
		return $this->errors['prev'][$error_group];
	}

	protected static function get( $array,$key,$retval=false,$sep='.' ) {
		if ( is_null( $key ) ) {
			return $array;
		}
		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}
		foreach( explode( $sep,$key ) as $_key ) {
			if ( !is_array( $array ) || !array_key_exists( $_key,$array ) || ( !is_array( $array[$_key] ) && strlen( trim( $array[$_key] ) ) === 0 ) || ( is_array( $array[$_key] ) && count( $array[$_key] ) === 0 ) ) {
				return $retval;
			}
			$array = $array[$_key];
		}
		return $array;
	}

	public static function set( &$array,$key,$value,$sep='.' ) {
		if ( isset( $array[$key] ) ) {
			$array[$key] = $value;
			return;
		}
		$keys = explode( $sep,$key );
		while( count( $keys ) > 1 ) {
			$key = array_shift( $keys );
			if ( !isset( $array[$key] ) || !is_array( $array[$key] ) ) {
				$array[$key] = array();
			}
			$array =& $array[$key];
		}
		$array[array_shift( $keys )] = $value;
	}

	public function set_field( $name,$value,$bypass=false ) {
		$name = $this->normalize_name( $name );
		if ( $bypass == true || self::get( $this->input['prev'],$name,false ) === false ) {
			self::set( $this->input['prev'],$name,( is_string( $value ) ? html::entity_decode( $value ) : $value ) );
		}
		return $this;
	}

	public function prev_input( $name,$retval=false ) {
		$key = $this->normalize_name( $name );
		return self::get( $this->input['prev'],$key,$retval );
	}

	public function input( $name,$retval=false ) {
		$key = $this->normalize_name( $name );
		return self::get( $this->input['curr'],$key,$retval );
	}

	public function has_input( $name ) {
		$key = $this->normalize_name( $name );
		return ( self::get( $this->input['curr'],$key,false ) !== false );
	}

	public function set_input( $field_key,$value ) {
		self::set( $this->input['curr'],$field_key,$value );
	}

	public function pressed( $name,$value=null ) {
		if ( ( $input_value = input::request( $name ) ) === false || ( !is_null( $value ) && $value !== $input_value ) ) {
			return false;
		}
		return true;
	}

	public function is_valid( $fieldset=null ) {
		if ( !$this->submitted ) {
			return false;
		}
		if ( !is_null( $fieldset ) && !isset( $this->fieldsets[$fieldset] ) ) {
			throw new app_exception( 'Unable to find fieldset: %s',$fieldset );
		}
		$fieldset = ( !is_null( $fieldset ) ? $this->fieldsets[$fieldset] : $this->main_fieldset );
		$result = $fieldset->validate();
		if ( $result === validator::passed ) {
			return true;
		}
		$this->save();
		redirect::current_page();
	}

	public function save( $config=array() ) {
		$this->data->set( 'token',$this->token[( isset( $config['token_persist'] ) && $config['token_persist'] ? 'prev' : 'curr' )] );
		$this->data->set( 'input',$this->input );
		$this->data->set( 'errors',$this->errors );
		$data = $this->data->get_data();
		session::set( "forms.{$this->config['id']}",$data );
	}

	public function reset() {
		session::remove("forms.{$this->config['id']}");
	}

	public function debug() {
		func::debug( $this->input );
	}

	public function open() {
		return $this->tags['form']->render_start() . PHP_EOL;
	}

	public function close() {
		$this->save();

		$this->tags['div.hidden_fields']->inner_content( $this->field('form[id]')->render() );
		$this->tags['div.hidden_fields']->inner_content( $this->field('form[token]')->render() );
		return $this->tags['div.hidden_fields']->render() . $this->tags['form']->render_end() . PHP_EOL;
	}

	public function view( $name ) {
		if ( !isset( $this->views[$name] ) ) {
			$this->views[$name] = autoloader::class_by_type( $name,form_pkg::class_type_view,array(
				'instance' => true,
				'args' => array(
					$this
				)
			) );
			if ( $this->views[$name] === false ) {
				throw new app_exception( 'Unable to load form view: %s',$view );
			}
		}
		return $this->views[$name];
	}

}

?>