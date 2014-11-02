<?php
/**
 * Hooks for Tools extension
 *
 * @file
 * @ingroup Extensions
 */

class ToolsHooks {

	public static function onwgQueryPages( &$qp ) {
		$qp[] = array( 'TemplateDuplicateArgumentsPage', 'TemplateDuplicateArguments' );

		return true;
	}

}
