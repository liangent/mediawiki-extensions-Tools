/*!
 * Special:CompareArticleVariants
 */
( function ( mw, $ ) {
	$( function () {
		$( '.mw-tools-cav-item' ).each( function() {
			var $this = $( this );
			var article = $this.data( 'article' );
			var configuration = $this.data( 'configuration' );
			var variant = $this.data( 'variant' );

			$.ajax( {
				url: mw.config.get( 'wgWMFCanonicalServer' )
					+ mw.config.get( 'wgWMFScriptPath' ) + '/api.php',
				data: {
					format: 'json',
					action: 'query',
					prop: 'revisions',
					titles: 'MediaWiki:Tools-comparearticlevariants-configuration-'
						+ configuration + '-' + variant + '.json',
					indexpageids: true,
					rvlimit: 1,
					rvprop: 'content'
				},
				dataType: 'jsonp'
			} ).done( function( data ) {
				var revisions = data.query.pages[data.query.pageids[0]].revisions;
				if ( !revisions ) {
					$this.empty().append(
						$( '<div/>' ).addClass( 'errorbox' ).text(
							mw.message( 'tools-comparearticlevariants-missing', configuration, variant ).text()
						)
					);
					return;
				}

				var json, jsonText = revisions[0]['*'];
				try {
					json = JSON.parse( jsonText );
				} catch ( e ) {
					$this.empty().append(
						$( '<div/>' ).addClass( 'errorbox' ).text(
							mw.message( 'tools-comparearticlevariants-broken', configuration, variant ).text()
						)
					);
					return;
				}

				if ( !$.isArray( json ) ) {
					$this.empty().append(
						$( '<div/>' ).addClass( 'errorbox' ).text(
							mw.message( 'tools-comparearticlevariants-broken', configuration, variant ).text()
						)
					);
					return;
				}

				$.ajax( {
					url: mw.config.get( 'wgWMFCanonicalServer' )
						+ mw.config.get( 'wgWMFScriptPath' ) + '/api.php',
					data: {
						format: 'json',
						action: 'parse',
						page: article,
						redirects: true,
						prop: 'text',
						disabletoc: true,
						variant: variant
					},
					dataType: 'jsonp'
				} ).done( function( data ) {
					$this.html( data.parse.text['*'] );
					$this.find( '.mw-editsection' ).remove();
					$this.find( 'a' ).contents().unwrap();

					$this.highlightText( $.map( json, function( val ) {
						if ( typeof val == 'string' ) {
							return val;
						}
					} ).join( ' ' ) );
				} );
			} );
		} );
	} );
}( mediaWiki, jQuery ) );
