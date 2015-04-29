<?php

namespace ionmvc\packages\form\classes\validator;

use ionmvc\classes\config;
use ionmvc\classes\file;
use ionmvc\classes\func;
use ionmvc\classes\input;
use ionmvc\classes\path;
use ionmvc\exceptions\app as app_exception;
use ionmvc\packages\form\classes\field as field_class;
use ionmvc\packages\form\classes\ruleset;
use ionmvc\packages\form\classes\validator;

class field extends validator {

	protected $field;
	protected $config = array(
		'file' => false
	);

	public function __construct( field_class $field,ruleset $ruleset,$config=array() ) {
		$this->field      = $field;
		$this->ruleset    = $ruleset;
		$this->messages   = array_merge( config::get('form.field.error_messages',array()),$ruleset->get_messages() );
		$this->validators = array_merge( config::get('form.field.validators',array()),$ruleset->get_validators() );
		$this->config     = array_merge( $this->config,$config );
	}

	public function run() {
		$rules = $this->ruleset->get_rules();
		if ( isset( $rules['upload'] ) && !( $rules['upload'] instanceof \Closure ) ) {
			$fieldset = ( isset( $this->field->fieldset ) ? $this->field->fieldset : null );
			$this->field->form->fieldset( $fieldset )->add_upload_field( $this->field->key );
		}
		if ( !$this->config['file'] ) {
			$field_value = input::request( $this->field->key );
			if ( ( !is_array( $field_value ) && strlen( trim( $field_value ) ) === 0 ) || ( is_array( $field_value ) && count( $field_value ) === 0 ) ) {
				$field_value = false;
			}
			if ( !isset( $rules['required'] ) && !isset( $rules['required_if'] ) && !isset( $rules['fieldset_required'] ) && $field_value === false ) {
				/*if ( isset( $this->input[$name] ) ) {
					unset( $this->input[$name] );
				}*/
				return validator::passed;
			}
		}
		else {
			$field_value = input::file( $this->field->key );
			if ( $field_value === false || (int) $field_value['error'] === UPLOAD_ERR_NO_FILE ) {
				if ( !isset( $rules['required'] ) ) {
					return validator::passed;
				}
				if ( ( $prev_value = $this->field->form->prev_input( $this->field->key ) ) !== false ) {
					$this->field->form->set_input( $this->field->key,$prev_value );
					return validator::passed;
				}
			}
		}
		$rule_keys = array_keys( $rules );
		$errors = 0;
		while( !is_null( $rule = array_shift( $rule_keys ) ) ) {
			$info = $this->run_rule( $field_value,$rule,( isset( $rules[$rule] ) ? $rules[$rule] : false ) );
			if ( $info === true ) {
				continue;
			}
			if ( isset( $info['stop'] ) && $info['stop'] ) {
				break;
			}
			if ( !isset( $this->messages[$info['message_id']] ) ) {
				throw new app_exception( 'Unable to find message with id %s',$info['message_id'] );
			}
			if ( !isset( $info['vars'] ) ) {
				$info['vars'] = array();
			}
			array_unshift( $info['vars'],$this->field->label );
			$this->field->form->add_error( $this->field->key,vsprintf( $this->messages[$info['message_id']],$info['vars'] ),$this->field->error_group );
			$errors++;
			break;
		}
		if ( $errors > 0 ) {
			return validator::failed;
		}
		$this->field->form->set_input( $this->field->key,$field_value );
		return validator::passed;
	}

	private function run_rule( &$field_value,$rule,$data ) {
		if ( $data instanceof \Closure ) {
			return call_user_func( $data,$field_value );
		}
		if ( isset( $this->validators[$rule] ) ) {
			return call_user_func( $this->validators[$rule],$field_value );
		}
		switch( $rule ) {
			case 'required':
				if ( !$this->config['file'] ) {
					if ( $field_value === false || ( !is_array( $field_value ) && strlen( trim( $field_value ) ) === 0 ) || ( is_array( $field_value ) && count( $field_value ) === 0 ) ) {
						return array(
							'message_id' => $rule
						);
					}
					break;
				}
				if ( $field_value === false || $field_value['error'] !== UPLOAD_ERR_OK || empty( $field_value['name'] ) ) {
					return array(
						'message_id' => 'upload_choose_file'
					);
				}
				break;
			case 'fieldset_required':
				if ( !isset( $this->field->fieldset ) ) {
					throw new app_exception( 'Field must be defined in fieldset to use the %s rule',$rule );
				}
				if ( $field_value !== false && $field_value !== '' ) {
					return true;
				}
				$fields = $this->field->form->fieldset( $this->field->fieldset )->get_fields();
				foreach( $fields as $field ) {
					if ( $field == $this->field->key || ( $ruleset = $this->field->form->field( $field )->get_ruleset() ) === false || !$ruleset->has_rule( $rule ) ) {
						continue;
					}
					if ( ( $input = input::request( $field,false ) ) !== false && $input !== '' ) {
						return $this->run_rule( $field_value,'required',false );
					}
				}
				return array(
					'stop' => true
				);
				break;
			case 'min_length':
				if ( !is_numeric( $data ) ) {
					throw new app_exception( '%s rule requires a numeric parameter',$rule );
				}
				if ( strlen( $field_value ) < (int) $data ) {
					return array(
						'message_id' => $rule,
						'vars'       => array( $data )
					);
				}
				break;
			case 'max_length':
				if ( !is_numeric( $data ) ) {
					throw new app_exception( '%s rule requires a numeric parameter',$rule );
				}
				if ( strlen( $field_value ) > (int) $data ) {
					return array(
						'message_id' => $rule,
						'vars'       => array( $data )
					);
				}
				break;
			case 'exact_length':
				if ( !is_numeric( $data ) ) {
					throw new app_exception( '%s rule requires a numeric parameter',$rule );
				}
				if ( strlen( $field_value ) !== (int) $data ) {
					return array(
						'message_id' => $rule,
						'vars'       => array( $data )
					);
				}
				break;
			case 'numeric':
				if ( !is_numeric( $field_value ) ) {
					return array(
						'message_id' => $rule
					);
				}
				break;
			case 'phone':
				preg_match_all( '#[X]+#',$data,$matches );
				if ( !isset( $matches[0] ) || count( $matches[0] ) == 0 ) {
					throw new app_exception('Invalid phone number format');
				}
				$parts = preg_split( '#[X]+#',$data );
				if ( !isset( $parts[0] ) ) {
					throw new app_exception('Invalid phone number format');
				}
				$format = array();
				foreach( $parts as &$part ) {
					$part = str_replace( array('[','\\','^','$','.','|','?','*','+','(',')','{','}'),array('\[','\\\\','\^','\$','\.','\|','\?','\*','\+','\(','\)','\{','\}'),$part );
				}
				foreach( $matches[0] as &$match ) {
					$match = '[0-9]{' . strlen( $match ) . '}';
				}
				$array_one = $parts;
				$array_two = $matches[0];
				for( $i=0,$e_i=0,$o_i=0;$i < ( count( $array_one ) + count( $array_two ) );$i++ ) {
					if ( $i % 2 == 0 ) {
						if ( !isset( $array_one[$e_i] ) ) { //first
							continue;
						}
						$format[] = $array_one[$e_i];
						$e_i++;
					}
					else {
						if ( !isset( $array_two[$o_i] ) ) { //second
							continue;
						}
						$format[] = $array_two[$o_i];
						$o_i++;
					}
				}
				$format = implode( '',array_filter( $format ) );
				if ( preg_match( "#^{$format}$#",$field_value ) !== 1 ) {
					return array(
						'message_id' => $rule,
						'vars' => array(
							$data
						)
					);
				}
				break;
			case 'boolean':
				
				break;
			case 'matches_field':
				if ( $data === true ) {
					throw new app_exception('Field required to compare with');
				}
				if ( !$this->field->form->has_field( $data ) ) {
					throw new app_exception( 'Field %s does not exist',$data );
				}
				$field = $this->field->form->field( $data );
				if ( !$field->valid || !$this->field->form->has_input( $field->key ) || $this->field->form->input( $field->key ) !== $field_value ) {
					return array(
						'message_id' => $rule,
						'vars' => array(
							$field->label
						)
					);
				}
				break;
			case 'email':
				if ( !func::validate_email( $field_value ) ) {
					return array(
						'message_id' => $rule
					);
				}
				break;
			case 'url':
				//very basic for now
				if ( strpos( $field_value,'http://' ) === false && strpos( $field_value,'https://' ) === false ) {
					return array(
						'message_id' => $rule
					);
				}
				break;
			case 'password':
				if ( !is_numeric( $data ) ) {
					throw new app_exception( '%s rule requires a numeric parameter',$rule );
				}
				$_errmsg = array();
				if ( strlen( $field_value ) < (int) $data ) {
					$_errmsg['length'] = sprintf( $this->messages['password_reason_1'],$data );
				}
				if ( preg_match( '/[A-Z]/',$field_value ) === 0 ) {
					$_errmsg['uppercase'] = $this->messages['password_reason_2'];
				}
				if ( preg_match( '/[a-z]/',$field_value ) === 0 ) {
					$_errmsg['lowercase'] = $this->messages['password_reason_3'];
				}
				if ( preg_match( '/[0-9]/',$field_value ) === 0 ) {
					$_errmsg['number'] = $this->messages['password_reason_4'];
				}
				if ( preg_match( '/[^a-zA-Z0-9]/',$field_value ) === 0 ) {
					$_errmsg['special'] = $this->messages['password_reason_5'];
				}
				$errstr = '';
				if ( isset( $_errmsg['length'] ) ) {
					$errstr .= $_errmsg['length'];
				}
				if ( isset( $_errmsg['uppercase'] ) || isset( $_errmsg['lowercase'] ) || isset( $_errmsg['number'] ) || isset( $_errmsg['special'] ) ) {
					$errstr .= ( isset( $_errmsg['length'] ) ? ' and ' : ' must ' ) . 'have at least one '; //add these to language array
				}
				if ( isset( $_errmsg['length'] ) ) {
					unset( $_errmsg['length'] );
				}
				$errstr .= implode( ', ',$_errmsg );
				if ( $errstr !== '' ) {
					return array(
						'message_id' => $rule,
						'vars'       => array( $errstr )
					);
				}
				break;
			case 'upload':
				$upload_config = $data;
				if ( count( ( $diff = array_diff( array('exts','max_size','directory','temp_directory','filename_length'),array_keys( $upload_config ) ) ) ) > 0 ) {
					throw new app_exception( 'Missing config vars: %s',implode( ', ',$diff ) );
				}
				if ( ( $max_size = file::to_bytes( $upload_config['max_size'] ) ) === false ) {
					throw new app_exception('Invalid max file size');
				}
				$upload_config['temp_directory'] = rtrim( $upload_config['temp_directory'],'/' );
				$upload_config['max_size'] = $max_size;
				if ( !is_array( $upload_config['exts'] ) ) {
					$upload_config['exts'] = explode( ',',$upload_config['exts'] );
				}
				if ( !is_uploaded_file( $field_value['tmp_name'] ) ) {
					return array(
						'message_id' => 'upload_invalid_file'
					);
				}
				$extn = strtolower( file::get_extension( $field_value['name'] ) );
				if ( !in_array( $extn,$upload_config['exts'] ) ) {
					return array(
						'message_id' => 'upload_invalid_extn',
						'vars' => array(
							implode( ', ',$upload_config['exts'] )
						)
					);
				}
				$image = false;
				if ( in_array( $extn,array('jpg','jpeg','gif','png') ) ) {
					$image = true;
				}
				if ( $image && false === ( $info = getimagesize( $field_value['tmp_name'] ) ) ) {
					return array(
						'message_id' => 'upload_invalid_img'
					);
				}
				if ( filesize( $field_value['tmp_name'] ) > $upload_config['max_size'] ) {
					return array(
						'message_id' => 'upload_invalid_size',
						'vars' => array(
							file::format_filesize( $upload_config['max_size'] )
						)
					);
				}
				if ( $image && isset( $info ) ) {
					list( $width,$height ) = $info;
					//checking minimum dimensions
					if ( isset( $upload_config['min_dimensions'] ) ) {
						if ( count( $upload_config['min_dimensions'] ) !== 2 ) {
							throw new app_exception('Rule \'upload:min_dimensions\' requires two parameters: [width][height]');
						}
						list( $upload_config['min_width'],$upload_config['min_height'] ) = $upload_config['min_dimensions'];
					}
					$min_width = $min_height = false;
					if ( isset( $upload_config['min_width'] ) && $width < $upload_config['min_width'] ) {
						$min_width = true;
					}
					if ( isset( $upload_config['min_height'] ) && $height < $upload_config['min_height'] ) {
						$min_height = true;
					}
					if ( $min_width && $min_height ) {
						return array(
							'message_id' => 'upload_image_small',
							'vars' => array(
								$upload_config['min_width'],
								$upload_config['min_height']
							)
						);
					}
					if ( $min_width ) {
						return array(
							'message_id' => 'upload_image_small_width',
							'vars' => array(
								$upload_config['min_width']
							)
						);
					}
					if ( $min_height ) {
						return array(
							'message_id' => 'upload_image_small_height',
							'vars' => array(
								$upload_config['min_height']
							)
						);
					}
					if ( $min_width || $min_height ) {
						break;
					}
					//checking maximum dimensions
					if ( isset( $upload_config['max_dimensions'] ) ) {
						if ( count( $upload_config['max_dimensions'] ) !== 2 ) {
							throw new app_exception('Rule \'upload:max_dimensions\' requires two parameters: [width][height]');
						}
						list( $upload_config['max_width'],$upload_config['max_height'] ) = $upload_config['max_dimensions'];
					}
					$max_width = $max_height = false;
					if ( isset( $upload_config['max_width'] ) && $width > $upload_config['max_width'] ) {
						$max_width = true;
					}
					if ( isset( $upload_config['max_height'] ) && $height > $upload_config['max_height'] ) {
						$max_height = true;
					}
					if ( $max_width && $max_height ) {
						return array(
							'message_id' => 'upload_image_large',
							'vars' => array(
								$upload_config['max_width'],
								$upload_config['max_height']
							)
						);
					}
					if ( $max_width ) {
						return array(
							'message_id' => 'upload_image_large_width',
							'vars' => array(
								$upload_config['max_width']
							)
						);
					}
					if ( $max_height ) {
						return array(
							'message_id' => 'upload_image_large_height',
							'vars' => array(
								$upload_config['max_height']
							)
						);
					}
					if ( $max_width || $max_height ) {
						break;
					}
					$field_value['type'] = $info['mime'];
				}
				$file = time() . '_' . func::rand_string( $upload_config['filename_length'],'alpha,numeric' ) . ".{$extn}";
				if ( !copy( $field_value['tmp_name'],path::get( $upload_config['temp_directory'] ) . "/{$file}" ) ) {
					return array(
						'message_id' => 'upload_copy_error'
					);
				}
				unlink( $field_value['tmp_name'] );
				$field_value = array(
					'path'          => $upload_config['temp_directory'],
					'name'          => $file,
					'original_name' => file::sanitize_name( basename( $field_value['name'] ) ),
					'type'          => $field_value['type'],
					'size'          => $field_value['size'],
					'move-to'       => $upload_config['directory']
				);
				break;
			default:
				throw new app_exception( 'Unable to find rule %s',$rule );
				break;
		}
		return true;
	}

}

?>