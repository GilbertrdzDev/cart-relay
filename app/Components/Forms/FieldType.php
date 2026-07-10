<?php

namespace WoocartBridge\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Field types currently supported by the plugin admin form layer.
 */
enum FieldType: string {

	case TEXT = 'text';
	case SELECT = 'select';
	case TOGGLE = 'toggle';
	case HIDDEN = 'hidden';

}
