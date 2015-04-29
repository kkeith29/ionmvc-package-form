<?php

namespace ionmvc\packages\form\classes;

class validator {

	const passed = 1;
	const failed = 2;
	const stop   = 3;

	protected $ruleset;
	protected $validators = array();
	protected $messages = array();

}

?>