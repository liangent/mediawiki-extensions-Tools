<?php
/**
 * CompareArticleVariants SpecialPage for Tools extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialCompareArticleVariants extends FormSpecialPage {
	public function __construct() {
		parent::__construct( 'CompareArticleVariants' );

		$this->defaultTitle = '';
		$this->data = null;
	}

	protected function getFormFields() {
		global $wgContLang;

		$variants = array();
		foreach ( $wgContLang->getVariants() as $variant ) {
			$variants[$this->msg( 'variantname-' . $variant )->text()] = $variant;
		}

		$fields = array(
			'Article' => array(
				'type' => 'title',
				'label-message' => 'tools-comparearticlevariants-article',
				'default' => $this->defaultTitle,
				'autofocus' => true,
				'required' => true
			),
			'Configuration' => array(
				'type' => 'select',
				'label-message' => 'tools-comparearticlevariants-configuration',
				'options-message' => 'tools-comparearticlevariants-configurations',
				'required' => true
			),
			'Variants' => array(
				'type' => 'multiselect',
				'label-message' => 'tools-comparearticlevariants-variants',
				'options' => $variants,
				'required' => true
			)
		);

		return $fields;
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	public function onSubmit( array $data /* $form = null */ ) {
		$this->data = $data;

		return true;
	}

	public function onSuccess() {
		$out = $this->getOutput();

		$out->addModules( 'ext.Tools.CompareArticleVariants' );

		$out->addHTML( Html::openElement( 'div', array( 'class' => 'mw-tools-cav-wrapper' ) ) );
		foreach ( $this->data['Variants'] as $variant ) {
			$variantLang = Language::factory( $variant );
			$out->addHTML( Html::element( 'div', array(
				'class' => 'mw-tools-cav-item mw-content-' . $variantLang->getDir(),
				'lang' => $variantLang->getHtmlCode(),
				'dir' => $variantLang->getDir(),
				'data-article' => $this->data['Article'],
				'data-configuration' => $this->data['Configuration'],
				'data-variant' => $variant,
			), $this->msg( 'tools-comparearticlevariants-loading' )->plain() ) );
		}
		$out->addHTML( Html::closeElement( 'div' ) );
	}

	function execute( $par ) {
		if ( $par !== null ) {
			$this->defaultTitle = $par;
		}

		parent::execute( $par );
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
