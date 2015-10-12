<?php
/**
 * CompareArticleVariants SpecialPage for Tools extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialCompareArticleVariants extends SpecialPage {
	public function __construct() {
		parent::__construct( 'CompareArticleVariants' );
	}

	/**
	 * Shows the page to the user.
	 * @param string $sub: The subpage string argument (if any).
	 *  [[Special:CompareArticleVariants/subpage]].
	 */
	public function execute( $sub ) {
		$out = $this->getOutput();

		$out->setPageTitle( 'Compare article variants' );
		$out->addHTML( 'Hello' );
	}
}
