/**
 * Inspector for core/query inside the Start Theme strip: pick posts by search (REST).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useRef = wp.element.useRef;
	var addFilter = wp.hooks.addFilter;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SearchControl = wp.components.SearchControl;
	var Button = wp.components.Button;
	var Spinner = wp.components.Spinner;
	var __ = wp.i18n.__;
	var apiFetch = wp.apiFetch;
	var useSelect = wp.data.useSelect;

	var decodeEntities = wp.htmlEntities.decodeEntities;

	function isQueryInsideStrip( clientId ) {
		var select = wp.data.select( 'core/block-editor' );
		var parents = select.getBlockParents( clientId, true );
		var i;
		for ( i = 0; i < parents.length; i++ ) {
			var b = select.getBlock( parents[ i ] );
			var cn = ( b && b.attributes && b.attributes.className ) || '';
			if ( b && b.name === 'core/group' && cn.indexOf( 'st-featured-strip' ) !== -1 ) {
				return true;
			}
		}
		return false;
	}

	function isMosaicStripQuery( attributes ) {
		return ( attributes.className || '' ).indexOf( 'st-query-mosaic' ) !== -1;
	}

	/**
	 * Mirror picks into core/query `query` attr so the editor preview uses REST `include` + orderby include (see core Post Template edit).
	 * Without picks, `perPage` is left as set in the Query block (or a safe default if missing).
	 */
	function applyStripPicksToQuery( attributes, pickIds ) {
		var q = Object.assign( {}, attributes.query || {} );
		if ( pickIds.length ) {
			q.include = pickIds.slice();
			q.perPage = pickIds.length;
			q.orderBy = 'include';
		} else {
			delete q.include;
			if ( q.orderBy === 'include' ) {
				q.orderBy = 'date';
			}
			if ( ! q.perPage || Number( q.perPage ) < 1 ) {
				q.perPage = 6;
			}
		}
		return q;
	}

	function sameIntArray( a, b ) {
		if ( a.length !== b.length ) {
			return false;
		}
		var i;
		for ( i = 0; i < a.length; i++ ) {
			if ( a[ i ] !== b[ i ] ) {
				return false;
			}
		}
		return true;
	}

	function StripQueryInspector( props ) {
		var setAttributes = props.setAttributes;
		var attributes = props.attributes;

		var rawIds = Array.isArray( attributes.stStripPostIds ) ? attributes.stStripPostIds : [];
		var ids = rawIds.map( function ( x ) {
			return parseInt( x, 10 );
		} ).filter( function ( n ) {
			return n > 0;
		} );

		var maxPick = 12;

		var searchState = useState( '' );
		var search = searchState[ 0 ];
		var setSearch = searchState[ 1 ];

		var resultsState = useState( [] );
		var results = resultsState[ 0 ];
		var setResults = resultsState[ 1 ];

		var loadingState = useState( false );
		var loading = loadingState[ 0 ];
		var setLoading = loadingState[ 1 ];

		var timerRef = useRef( null );

		var qNow = attributes.query || {};
		var curInclude = Array.isArray( qNow.include )
			? qNow.include
					.map( function ( x ) {
						return parseInt( x, 10 );
					} )
					.filter( function ( n ) {
						return n > 0;
					} )
			: [];

		useEffect(
			function () {
				var needsSync = false;
				if ( ids.length ) {
					if (
						! sameIntArray( ids, curInclude ) ||
						qNow.orderBy !== 'include' ||
						Number( qNow.perPage ) !== ids.length
					) {
						needsSync = true;
					}
				} else if ( curInclude.length > 0 || qNow.orderBy === 'include' ) {
					needsSync = true;
				}
				if ( needsSync ) {
					setAttributes( {
						query: applyStripPicksToQuery( attributes, ids ),
					} );
				}
			},
			[
				ids.join( ',' ),
				curInclude.join( ',' ),
				qNow.orderBy,
				qNow.perPage,
			]
		);

		useEffect(
			function () {
				if ( timerRef.current ) {
					clearTimeout( timerRef.current );
					timerRef.current = null;
				}
				if ( ! search || search.length < 2 ) {
					setResults( [] );
					return;
				}
				setLoading( true );
				timerRef.current = setTimeout( function () {
					apiFetch( {
						path: '/wp/v2/posts?per_page=15&search=' + encodeURIComponent( search ) + '&_fields=id,title',
					} )
						.then( function ( rows ) {
							setResults( rows || [] );
						} )
						.catch( function () {
							setResults( [] );
						} )
						.finally( function () {
							setLoading( false );
						} );
				}, 300 );
				return function () {
					if ( timerRef.current ) {
						clearTimeout( timerRef.current );
					}
				};
			},
			[ search, setLoading, setResults ]
		);

		useEffect(
			function () {
				if ( ! ids.length ) {
					return;
				}
				var rs = wp.data.resolveSelect( 'core' );
				ids.forEach( function ( id ) {
					rs.getEntityRecord( 'postType', 'post', id );
				} );
			},
			[ ids.join( ',' ) ]
		);

		var resolvedTitles = useSelect(
			function ( select ) {
				return ids.map( function ( id ) {
					var post = select( 'core' ).getEntityRecord( 'postType', 'post', id );
					return {
						id: id,
						title: post && post.title && post.title.rendered ? decodeEntities( post.title.rendered ) : '#' + String( id ),
					};
				} );
			},
			[ ids.join( ',' ) ]
		);

		function addId( id ) {
			if ( ids.indexOf( id ) !== -1 ) {
				return;
			}
			if ( ids.length >= maxPick ) {
				return;
			}
			var next = ids.concat( [ id ] );
			setAttributes( {
				stStripPostIds: next,
				query: applyStripPicksToQuery( attributes, next ),
			} );
			setSearch( '' );
			setResults( [] );
		}

		function removeId( id ) {
			var next = ids.filter( function ( x ) {
				return x !== id;
			} );
			setAttributes( {
				stStripPostIds: next,
				query: applyStripPicksToQuery( attributes, next ),
			} );
		}

		function clearAll() {
			setAttributes( {
				stStripPostIds: [],
				query: applyStripPicksToQuery( attributes, [] ),
			} );
		}

		return el(
			InspectorControls,
			null,
			el(
				PanelBody,
				{
					title: __( 'Strip: picked posts', 'start-theme' ),
					initialOpen: true,
				},
				el(
					'p',
					{ className: 'description', style: { marginTop: 0 } },
					__(
						'Optional: pick posts in display order; picks sync to the Query for both the editor preview (REST) and the front. Leave empty and set “Posts per page” on the Query block for how many latest posts to show.',
						'start-theme'
					)
				),
				el( SearchControl, {
					label: __( 'Search posts', 'start-theme' ),
					value: search,
					onChange: setSearch,
					placeholder: __( 'Type at least 2 characters…', 'start-theme' ),
				} ),
				loading ? el( Spinner, null ) : null,
				results.length
					? el(
							'div',
							{ className: 'start-theme-strip-search-results', style: { marginTop: '8px' } },
							results.map( function ( row ) {
								var title = decodeEntities( row.title.rendered || '' );
								return el(
									Button,
									{
										key: 'add-' + row.id,
										isSecondary: true,
										style: {
											display: 'block',
											width: '100%',
											textAlign: 'left',
											marginBottom: '6px',
											overflow: 'hidden',
											textOverflow: 'ellipsis',
											whiteSpace: 'nowrap',
										},
										onClick: function () {
											addId( row.id );
										},
									},
									title + ' (#' + row.id + ')'
								);
							} )
					  )
					: null,
				el(
					'div',
					{ className: 'start-theme-strip-picked', style: { marginTop: '12px' } },
					el( 'strong', null, __( 'Picked:', 'start-theme' ) ),
					resolvedTitles.length
						? resolvedTitles.map( function ( row ) {
								return el(
									'div',
									{
										key: 'picked-' + row.id,
										style: {
											display: 'flex',
											alignItems: 'center',
											justifyContent: 'space-between',
											marginTop: '6px',
										},
									},
									el(
										'span',
										{
											style: {
												flex: '1 1 auto',
												minWidth: 0,
												overflow: 'hidden',
												textOverflow: 'ellipsis',
												whiteSpace: 'nowrap',
												paddingRight: '8px',
											},
										},
										row.title
									),
									el( Button, {
										isSmall: true,
										isDestructive: true,
										variant: 'tertiary',
										onClick: function () {
											removeId( row.id );
										},
										text: __( 'Remove', 'start-theme' ),
									} )
								);
						  } )
						: el( 'p', { className: 'description' }, __( 'None', 'start-theme' ) )
				),
				el( Button, {
					isLink: true,
					onClick: clearAll,
					style: { marginTop: '8px' },
					disabled: ids.length === 0,
				}, __( 'Clear all picks', 'start-theme' ) )
			)
		);
	}

	function wrapQueryEdit( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== 'core/query' ) {
				return el( BlockEdit, props );
			}
			if ( ! isQueryInsideStrip( props.clientId ) ) {
				return el( BlockEdit, props );
			}
			if ( ! isMosaicStripQuery( props.attributes ) ) {
				return el( BlockEdit, props );
			}
			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el( StripQueryInspector, props )
			);
		};
	}

	addFilter( 'editor.BlockEdit', 'start-theme/query-strip-inspector', wrapQueryEdit );

	addFilter(
		'blocks.registerBlockType',
		'start-theme/query-strip-attrs',
		function ( settings, blockName ) {
			if ( blockName !== 'core/query' ) {
				return settings;
			}
			return Object.assign( {}, settings, {
				attributes: Object.assign( {}, settings.attributes || {}, {
					stStripPostIds: {
						type: 'array',
						default: [],
					},
				} ),
			} );
		}
	);
} )( window.wp );
