<?php
/**
 * A special page that list pages which:
 *
 * ... are in Category:<duplicate-args-category>
 * ... are not transcluded in any page that is not in Category:<duplicate-args-category>
 * ... are transcluded in some other pages
 * ... are not transcluding any page that is in Category:<duplicate-args-category>
 *
 * so these pages are potential causes of pages using duplicate arguments in template calls.
 *
 * @ingroup SpecialPage
 */
class TemplateDuplicateArgumentsPage extends LabsPageQueryPage {

	function __construct( $name = 'TemplateDuplicateArguments' ) {
		parent::__construct( $name );
		$this->inCategory = array();
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
		return true;
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
				'selfp' => 'page', 'selfcl' => 'categorylinks', 'selftl' => 'templatelinks',
			),
			'fields' => array(
				'namespace' => 'selfp.page_namespace',
				'title' => 'selfp.page_title',
				'value' => 'COUNT(*)',
			),
			'conds' => array(
				# in Category:<duplicate-args-category>
				'selfp.page_id = selfcl.cl_from',
				'selfcl.cl_to' => $category->getDBkey(),
				# not transcluded in any page that is not in Category:<duplicate-args-category>
				'NOT EXISTS (' . $dbr->selectSQLText(
					array( 'childtl' => 'templatelinks', 'childp' => 'page', 'childcl' => 'categorylinks' ),
					'*',
					array(
						'childtl.tl_from = childp.page_id',
						'childtl.tl_namespace = selfp.page_namespace',
						'childtl.tl_title = selfp.page_title',
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
				# transcluded in some other pages
				'selftl.tl_namespace = selfp.page_namespace',
				'selftl.tl_title = selfp.page_title',
				# not transcluding any page that is in Category:<duplicate-args-category>
				'NOT EXISTS (' . $dbr->selectSQLText(
					array( 'childtl' => 'templatelinks', 'parentp' => 'page', 'parentcl' => 'categorylinks' ),
					'*',
					array(
						'childtl.tl_from = selfp.page_id',
						'childtl.tl_namespace = parentp.page_namespace',
						'childtl.tl_title = parentp.page_title',
						'parentcl.cl_from = parentp.page_id',
						'parentcl.cl_to' => $category->getDBkey(),
					)
				) . ')',
			),
			'options' => array(
				'GROUP BY' => array( 'namespace', 'title' ),
			),
		);
	}

	function getOrderFields() {
		return array( 'page_namespace', 'page_title' );
	}

	protected function getGroupName() {
		return 'maintenance';
	}

	/**
	 * Cache page existence for performance
	 * @param DatabaseBase $db
	 * @param ResultWrapper $res
	 */
	function preprocessResults( $db, $res ) {
		if ( !$res->numRows() ) {
			return;
		}

		$cat = wfMessage( 'duplicate-args-category' )->inContentLanguage()->text();
		$category = Title::makeTitleSafe( NS_CATEGORY, $cat );

		if ( !$category ) {
			return;
		}

		foreach ( $db->select(
			array( 'page', 'categorylinks' ),
			array( 'page_namespace', 'page_title' ),
			array(
				'page_id = cl_from',
				'cl_to' => $category->getDBkey(),
				$db->makeList( array_map( function( $row ) use ( $db ) {
					return $db->makeList( array(
						'page_namespace' => $row->namespace,
						'page_title' => $row->title,
					), LIST_AND );
				}, iterator_to_array( $res ) ), LIST_OR ),
			)
		) as $row ) {
			$this->inCategory[$row->page_namespace][$row->page_title] = '';
		}

		// Back to start for display
		$res->seek( 0 );
	}

	/**
	 * Add links to the live site to page links
	 *
	 * @param Skin $skin
	 * @param object $row Result row
	 * @return string
	 */
	public function formatResult( $skin, $row ) {
		$html = parent::formatResult( $skin, $row );

		$title = Title::makeTitleSafe( $row->namespace, $row->title );
		if ( $title ) {
			$html = $this->getLanguage()->specialList( $html, $this->makeWlhLink( $title, $row ) );
		}

		$dbr = wfGetDB( DB_SLAVE );
		if ( !isset( $this->inCategory[$row->namespace][$row->title] ) ) {
			$html = Html::rawElement( 'del', array(), $html );
		}

		return $html;
	}

	/**
	 * Make a "what links here" link for a given title
	 *
	 * @param Title $title Title to make the link for
	 * @param object $result Result row
	 * @return string
	 */
	private function makeWlhLink( $title, $result ) {
		$wlh = SpecialPage::getTitleFor( 'Whatlinkshere', $title->getPrefixedText() );
		$label = $this->msg( 'nlinks' )->numParams( $result->value )->escaped();
		return Linker::link( $wlh, $label, array(), array( 'hidelinks' => 1, 'hideredirs' => 1 ) );
	}
}
