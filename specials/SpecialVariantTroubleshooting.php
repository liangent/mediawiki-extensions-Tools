<?php
/**
 * VariantTroubleshooting SpecialPage for Tools extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialVariantTroubleshooting extends SpecialPage {
	public function __construct() {
		parent::__construct( 'VariantTroubleshooting' );
	}

	/**
	 * Shows the page to the user.
	 * @param string $sub: The subpage string argument (if any).
	 *  [[Special:VariantTroubleshooting/subpage]].
	 */
	public function execute( $sub ) {
		global $wgContLang;
		$this->mMainLanguageCode = $wgContLang->getConverter()->mMainLanguageCode;

		$out = $this->getOutput();

		$out->setPageTitle( 'Variant troubleshooting' );

		$out->addHTML( Html::openElement( 'dl' ) );

		$request = $this->getRequest();
		$header = $request->getHeader( 'Accept-Language' );

		$out->addHTML( Html::element( 'dt', array(), 'Accept-Language' ) );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $header ) ) );

		$acceptLanguage = $request->getAcceptLang();

		$out->addHTML( Html::element( 'dt', array(), 'Parsed Accept-Language' ) );
		$out->addHTML( Html::rawElement( 'dd', array(), Html::element( 'pre', array(), print_r( $acceptLanguage, true ) ) ) );

		$headerVariant = $this->getHeaderVariant();

		$out->addHTML( Html::element( 'dt', array(), 'Header variant' ) );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $headerVariant ) ) );

		$user = User::newFromName( $sub );
		if ( $user ) {
			$warning = Html::rawElement( 'dd', array(), 'This value might be incorrect until <a href="https://bugzilla.wikimedia.org/show_bug.cgi?id=64115">bug 64115</a> is fixed.' );
		} else {
			$warning = '';
			$user = User::newFromID( 0 );
		}

		$out->addHTML( Html::element( 'dt', array(), 'User' ) );
		$out->addHTML( $warning );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $user->getName() ) ) );

		$userLanguage = $user->getOption( 'language' );

		$out->addHTML( Html::element( 'dt', array(), 'User language option' ) );
		$out->addHTML( $warning );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $userLanguage ) ) );

		$userVariant = $user->getOption( 'variant' );

		$out->addHTML( Html::element( 'dt', array(), 'User variant option' ) );
		$out->addHTML( $warning );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $userVariant ) ) );

		$gotUserVariant = $this->getUserVariant( $user );

		$out->addHTML( Html::element( 'dt', array(), 'User variant' ) );
		$out->addHTML( $warning );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $gotUserVariant ) ) );

		$preferredVariant = $this->getPreferredVariant( $user );

		$out->addHTML( Html::element( 'dt', array(), 'Preferred variant' ) );
		$out->addHTML( $warning );
		$out->addHTML( Html::element( 'dd', array(), $this->nullString( $preferredVariant ) ) );
	}

	private function nullString( $var ) {
		if ( $var === null ) {
			return '[NULL]';
		} elseif ( $var === false ) {
			return '[FALSE]';
		} else {
			return $var;
		}
	}

	protected function getHeaderVariant() {
		// see if some supported language variant is set in the
		// HTTP header.
		$languages = array_keys( $this->getRequest()->getAcceptLang() );
		if ( empty( $languages ) ) {
			return null;
		}

		$fallbackLanguages = array();
		foreach ( $languages as $language ) {
			$this->mHeaderVariant = $this->validateVariant( $language );
			if ( $this->mHeaderVariant ) {
				break;
			}

			// To see if there are fallbacks of current language.
			// We record these fallback variants, and process
			// them later.
			$fallbacks = $this->getVariantFallbacks( $language );
			if ( is_string( $fallbacks ) && $fallbacks !== $this->mMainLanguageCode ) {
				$fallbackLanguages[] = $fallbacks;
			} elseif ( is_array( $fallbacks ) ) {
				$fallbackLanguages =
					array_merge( $fallbackLanguages, $fallbacks );
			}
		}

		if ( !$this->mHeaderVariant ) {
			// process fallback languages now
			$fallback_languages = array_unique( $fallbackLanguages );
			foreach ( $fallback_languages as $language ) {
				$this->mHeaderVariant = $this->validateVariant( $language );
				if ( $this->mHeaderVariant ) {
					break;
				}
			}
		}

		return $this->mHeaderVariant;
	}

	protected function getUserVariant( $user ) {
		global $wgContLang;

		// memoizing this function wreaks havoc on parserTest.php
		/*
		if ( $this->mUserVariant ) {
			return $this->mUserVariant;
		}
		*/

		// Get language variant preference from logged in users
		// Don't call this on stub objects because that causes infinite
		// recursion during initialisation
		if ( $user->isLoggedIn() ) {
			if ( $this->mMainLanguageCode == $wgContLang->getCode() ) {
				$ret = $user->getOption( 'variant' );
			} else {
				$ret = $user->getOption( 'variant-' . $this->mMainLanguageCode );
			}
		} else {
			// figure out user lang without constructing wgLang to avoid
			// infinite recursion
			$ret = $user->getOption( 'language' );
		}

		$this->mUserVariant = $this->validateVariant( $ret );
		return $this->mUserVariant;
	}

	protected function getPreferredVariant( $user ) {
		global $wgDefaultLanguageVariant;

		$req = null;

		if ( $user->isLoggedIn() && !$req ) {
			$req = $this->getUserVariant( $user );
		} elseif ( !$req ) {
			$req = $this->getHeaderVariant();
		}

		if ( $wgDefaultLanguageVariant && !$req ) {
			$req = $this->validateVariant( $wgDefaultLanguageVariant );
		}

		// This function, unlike the other get*Variant functions, is
		// not memoized (i.e. there return value is not cached) since
		// new information might appear during processing after this
		// is first called.
		if ( $this->validateVariant( $req ) ) {
			return $req;
		}
		return $this->mMainLanguageCode;
	}

	public function __call( $method, $args ) {
		global $wgContLang;

		return call_user_func_array( array( $wgContLang->getConverter(), $method ), $args );
	}
}
