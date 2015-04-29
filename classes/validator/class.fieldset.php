<?php

namespace ionmvc\packages\form\classes\validator;

use ionmvc\classes\config;
use ionmvc\classes\path;
use ionmvc\exceptions\app as app_exception;
use ionmvc\packages\form\classes\fieldset as fieldset_class;
use ionmvc\packages\form\classes\ruleset;
use ionmvc\packages\form\classes\validator;

class fieldset extends validator {

	protected $fieldset;

	public function __construct( fieldset_class $fieldset,ruleset $ruleset ) {
		$this->fieldset   = $fieldset;
		$this->ruleset    = $ruleset;
		$this->messages   = array_merge( config::get('form.fieldset.error_messages',array()),$ruleset->get_messages() );
		$this->validators = array_merge( config::get('form.fieldset.validators',array()),$ruleset->get_validators() );
	}

	public function run() {
		$rules = $this->ruleset->get_rules();
		$rule_keys = array_keys( $rules );
		$errors = 0;
		while( !is_null( $rule = array_shift( $rule_keys ) ) ) {
			$info = $this->run_rule( $rule,$rules[$rule] );
			if ( $info === true ) {
				continue;
			}
			if ( !isset( $this->messages[$info['message_id']] ) ) {
				throw new app_exception( 'Unable to find message with id %s',$info['message_id'] );
			}
			if ( !isset( $info['vars'] ) ) {
				$info['vars'] = array();
			}
			$this->fieldset->form->add_error( $this->fieldset->id,vsprintf( $this->messages[$info['message_id']],$info['vars'] ) );
			$errors++;
			break;
		}
		if ( $errors > 0 ) {
			return validator::failed;
		}
		return validator::passed;
	}

	private function run_rule( $rule,$data ) {
		if ( $data instanceof \Closure ) {
			return call_user_func( $data,$this->fieldset->form );
		}
		switch( $rule ) {
			case 'finalize_uploads':
				$copy = array();
				foreach( $data as $field_key ) {
					if ( !$this->fieldset->form->has_input( $field_key ) ) {
						continue;
					}
					$field_data = $this->fieldset->form->input( $field_key );
					if ( !isset( $field_data['move-to'] ) ) {
						continue;
					}
					$path = path::get( $field_data['move-to'] ) . "/{$field_data['name']}";
					$old_path = path::get( $field_data['path'] ) . "/{$field_data['name']}";
					if ( $old_path === false || !file_exists( $old_path ) ) {
						return array(
							'message_id' => 'uploads_failed'
						);
					}
					$copy[] = compact('path','old_path','field_key','field_data');
				}
				$success = true;
				foreach( $copy as $info ) {
					if ( !copy( $info['old_path'],$info['path'] ) ) {
						$success = false;
						break;
					}
					unset( $info );
				}
				foreach( $copy as $info ) {
					if ( !$success && file_exists( $info['path'] ) ) {
						unlink( $info['path'] );
					}
					elseif ( file_exists( $info['old_path'] ) ) {
						unlink( $info['old_path'] );
					}
					$field_data['path'] = rtrim( $field_data['move-to'],'/' ) . "/{$field_data['name']}";
					unset( $field_data['move-to'] );
					$field_data['updated'] = true;
					$this->fieldset->form->set_input( $field_key,$field_data );
				}
				if ( !$success ) {
					return array(
						'message_id' => 'uploads_failed'
					);
				}
				break;
			default:
				
				throw new app_exception( 'Unable to find rule %s',$rule );
				break;
		}
		return true;
	}

}

?>