<?php
/**
 * A special page that list pages which:
 *
 * ... are in Category:<duplicate-args-category>
 * ... are not transcluded in any page that is not in Category:<duplicate-args-category>
 * ... are not transcluding any page that is in Category:<duplicate-args-category>
 *
 * so these pages are potential causes of pages using duplicate arguments in template calls.
 *
 * @ingroup SpecialPage
 */
class TemplateDuplicateArgumentsPage extends LabsPageQueryPage {

	function __construct( $name = 'TemplateDuplicateArguments' ) {
		parent::__construct( $name );
	}

	function getPageHeader() {
		return $this->msg( 'tools-templateduplicatearguments-text' )->parseAsBlock();
	}

	/**
	 * LEFT JOIN is expensive
	 *
	 * @return bool
	 */
	function isExpensive() {
		return true;
	}

	function isSyndicated() {
		return false;
	}

	/**
	 * @return bool
	 */
	function sortDescending() {
		return false;
	}

	function getQueryInfo() {
		$cat = wfMessage( 'duplicate-args-category' )->inContentLanguage()->text();
		$category = Title::makeTitleSafe( NS_CATEGORY, $cat );
		if ( !$category ) {
			return array();
		}

		$dbr = wfGetDB( DB_SLAVE );
		return array(
			'tables' => array(
				'selfp' => 'page', 'selfcl' => 'categorylinks',
			),
			'fields' => array(
				'namespace' => 'selfp.page_namespace',
				'title' => 'selfp.page_title',
				'value' => 'selfp.page_title'
			),
			'conds' => array(
				# in Category:<duplicate-args-category>
				'selfp.page_id = selfcl.cl_from',
				'selfcl.cl_to' => $category->getDBkey(),
				# not transcluded in any page that is not in Category:<duplicate-args-category>
				'NOT EXISTS (' . $dbr->selectSQLText(
					array( 'templatelinks', 'childp' => 'page', 'childcl' => 'categorylinks' ),
					'*',
					array(
						'tl_from = childp.page_id',
						'tl_namespace = selfp.page_namespace',
						'tl_title = selfp.page_title',
						'childcl.cl_from' => null,
					),
					__METHOD__,
					array(),
					array(
						'childcl' => array(
							'LEFT JOIN',
							array(
								'childcl.cl_from = childp.page_id',
								'childcl.cl_to' => $category->getDBkey(),
							),
						),
					)
				) . ')',
				# not transcluding any page that is in Category:<duplicate-args-category>
				'NOT EXISTS (' . $dbr->selectSQLText(
					array( 'templatelinks', 'parentp' => 'page', 'parentcl' => 'categorylinks' ),
					'*',
					array(
						'tl_from = selfp.page_id',
						'tl_namespace = parentp.page_namespace',
						'tl_title = parentp.page_title',
						'parentcl.cl_from = parentp.page_id',
						'parentcl.cl_to' => $category->getDBkey(),
					)
				) . ')',
			),
		);
	}

	function getOrderFields() {
		return array( 'page_namespace', 'page_title' );
	}

	protected function getGroupName() {
		return 'maintenance';
	}
}
