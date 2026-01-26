/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * Custom Dual Range Control Component
 *
 * A range slider with two handles for selecting start and end levels.
 */
function DualRangeControl( { startLevel, endLevel, maxLevel, onChange } ) {
	const [ isDragging, setIsDragging ] = useState( null );
	const [ trackRef, setTrackRef ] = useState( null );
	
	const levelLabels = [
		__( 'Continent', 'gatherpress-venue-hierarchy' ),
		__( 'Country', 'gatherpress-venue-hierarchy' ),
		__( 'State', 'gatherpress-venue-hierarchy' ),
		__( 'City', 'gatherpress-venue-hierarchy' ),
		__( 'Street', 'gatherpress-venue-hierarchy' ),
		__( 'Number', 'gatherpress-venue-hierarchy' ),
	];
	
	const getPositionFromLevel = ( level ) => {
		return ( ( level - 1 ) / ( maxLevel - 1 ) ) * 100;
	};
	
	const getLevelFromPosition = ( clientX ) => {
		if ( ! trackRef ) return 1;
		
		const rect = trackRef.getBoundingClientRect();
		const position = ( clientX - rect.left ) / rect.width;
		const level = Math.round( position * ( maxLevel - 1 ) ) + 1;
		
		return Math.max( 1, Math.min( maxLevel, level ) );
	};
	
	const handleMouseDown = ( handle ) => ( e ) => {
		e.preventDefault();
		setIsDragging( handle );
	};
	
	const handleMouseMove = ( e ) => {
		if ( ! isDragging ) return;
		
		const newLevel = getLevelFromPosition( e.clientX );
		
		if ( isDragging === 'start' ) {
			if ( newLevel <= endLevel ) {
				onChange( { startLevel: newLevel, endLevel } );
			}
		} else if ( isDragging === 'end' ) {
			if ( newLevel >= startLevel ) {
				onChange( { startLevel, endLevel: newLevel } );
			}
		}
	};
	
	const handleMouseUp = () => {
		setIsDragging( null );
	};
	
	useEffect( () => {
		if ( isDragging ) {
			document.addEventListener( 'mousemove', handleMouseMove );
			document.addEventListener( 'mouseup', handleMouseUp );
			
			return () => {
				document.removeEventListener( 'mousemove', handleMouseMove );
				document.removeEventListener( 'mouseup', handleMouseUp );
			};
		}
	}, [ isDragging, startLevel, endLevel ] );
	
	const startPos = getPositionFromLevel( startLevel );
	const endPos = getPositionFromLevel( endLevel );
	
	return (
		<div className="dual-range-control">
			<div className="dual-range-control__labels">
				{ levelLabels.slice( 0, maxLevel ).map( ( label, index ) => (
					<span key={ index } className="dual-range-control__label">
						{ label }
					</span>
				) ) }
			</div>
			
			<div className="dual-range-control__track-container">
				<div 
					ref={ setTrackRef }
					className="dual-range-control__track"
				>
					<div 
						className="dual-range-control__range"
						style={ {
							left: `${ startPos }%`,
							width: `${ endPos - startPos }%`,
						} }
					/>
					
					<button
						type="button"
						className={ `dual-range-control__handle dual-range-control__handle--start ${ isDragging === 'start' ? 'is-dragging' : '' }` }
						style={ { left: `${ startPos }%` } }
						onMouseDown={ handleMouseDown( 'start' ) }
						aria-label={ __( 'Start level', 'gatherpress-venue-hierarchy' ) }
					/>
					
					<button
						type="button"
						className={ `dual-range-control__handle dual-range-control__handle--end ${ isDragging === 'end' ? 'is-dragging' : '' }` }
						style={ { left: `${ endPos }%` } }
						onMouseDown={ handleMouseDown( 'end' ) }
						aria-label={ __( 'End level', 'gatherpress-venue-hierarchy' ) }
					/>
				</div>
			</div>
			
			<div className="dual-range-control__output">
				<span className="dual-range-control__output-label">
					{ __( 'Showing:', 'gatherpress-venue-hierarchy' ) }
				</span>
				<strong>
					{ levelLabels[ startLevel - 1 ] || '' }
					{ startLevel !== endLevel && (
						<>
							{ ' ' + __( 'to', 'gatherpress-venue-hierarchy' ) + ' ' }
							{ levelLabels[ endLevel - 1 ] || '' }
						</>
					) }
				</strong>
			</div>
		</div>
	);
}

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const { startLevel, endLevel, enableLinks, showVenue } = attributes;
	const [ locationHierarchy, setLocationHierarchy ] = useState( '' );
	const [ venueName, setVenueName ] = useState( '' );
	const [ maxDepth, setMaxDepth ] = useState( 7 );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	
	// Get the current post ID from the editor store
	const currentPostId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );
	
	// Get the current post type from the editor store
	const currentPostType = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostType();
	}, [] );
	
	// Use context values as fallback, but prefer the editor store values
	const postId = currentPostId || context.postId;
	const postType = currentPostType || context.postType;
	
	// Check if we're in a GatherPress event context
	if ( postType && postType !== 'gatherpress_event' ) {
		return (
			<p { ...useBlockProps() }>
				{ __( 'This block must be used within a GatherPress event', 'gatherpress-venue-hierarchy' ) }
			</p>
		);
	}
	
	useEffect( () => {
		if ( ! postId ) {
			setLocationHierarchy( __( 'No post ID available', 'gatherpress-venue-hierarchy' ) );
			setIsLoading( false );
			return;
		}
		
		const fetchLocationData = async () => {
			try {
				setIsLoading( true );
				setError( null );
				
				// Fetch terms directly via REST API
				const terms = await apiFetch( {
					path: `/wp/v2/gatherpress-location?post=${ postId }&per_page=100`,
				} );
				
				// Fetch event data to get venue information
				let eventVenueName = '';
				try {
					const event = await apiFetch( {
						path: `/wp/v2/gatherpress_event/${ postId }`,
					} );
					
					if ( event && event.venue ) {
						eventVenueName = event.venue.name || '';
					}
				} catch ( venueError ) {
					console.log( 'Could not fetch venue information:', venueError );
				}
				
				setVenueName( eventVenueName );
				
				if ( ! terms || terms.length === 0 ) {
					if ( showVenue && eventVenueName ) {
						setLocationHierarchy( eventVenueName );
					} else {
						setLocationHierarchy( __( 'No location hierarchy available for this event', 'gatherpress-venue-hierarchy' ) );
					}
					setIsLoading( false );
					return;
				}
				
				const buildTermPath = ( term, allTerms ) => {
					const path = [];
					let currentTerm = term;
					
					while ( currentTerm ) {
						path.unshift( currentTerm.name );
						
						if ( currentTerm.parent && currentTerm.parent !== 0 ) {
							currentTerm = allTerms.find( t => t.id === currentTerm.parent );
						} else {
							break;
						}
					}
					
					return path;
				};
				
				// Find leaf terms (deepest terms in each hierarchy)
				const termIds = terms.map( t => t.id );
				const parentIds = terms.map( t => t.parent );
				const leafTerms = terms.filter( term => ! parentIds.includes( term.id ) );
				
				const hierarchyPaths = leafTerms.map( term => buildTermPath( term, terms ) );
				
				// Calculate the maximum depth
				const calculatedMaxDepth = Math.max( ...hierarchyPaths.map( path => path.length ) );
				setMaxDepth( Math.min( calculatedMaxDepth, 7 ) ); // Cap at 7 levels
				
				// Filter paths based on start and end levels
				const filteredPaths = hierarchyPaths.map( path => {
					const actualStartLevel = Math.max( 1, startLevel );
					const actualEndLevel = Math.min( endLevel, path.length );
					
					if ( actualStartLevel > path.length ) {
						return '';
					}
					
					return path.slice( actualStartLevel - 1, actualEndLevel ).join( ' > ' );
				} ).filter( path => path !== '' );
				
				if ( filteredPaths.length > 0 ) {
					let hierarchyText = filteredPaths.join( ', ' );
					
					// Add venue name if requested and available
					if ( showVenue && eventVenueName ) {
						hierarchyText += ' > ' + eventVenueName;
					}
					
					setLocationHierarchy( hierarchyText );
				} else {
					// If no filtered paths but venue is requested, show just venue
					if ( showVenue && eventVenueName ) {
						setLocationHierarchy( eventVenueName );
					} else {
						setLocationHierarchy( __( 'No location hierarchy available at selected levels', 'gatherpress-venue-hierarchy' ) );
					}
				}
				setIsLoading( false );
			} catch ( err ) {
				console.error( 'Error fetching location data:', err );
				setError( err.message || 'Unknown error' );
				
				// Even on error, try to show venue if requested
				if ( showVenue && venueName ) {
					setLocationHierarchy( venueName );
				} else {
					setLocationHierarchy( __( 'Error loading location hierarchy', 'gatherpress-venue-hierarchy' ) );
				}
				setIsLoading( false );
			}
		};
		
		fetchLocationData();
	}, [ postId, startLevel, endLevel, showVenue ] );
	
	if ( error ) {
		return (
			<p { ...useBlockProps() }>
				{ __( 'Error: ', 'gatherpress-venue-hierarchy' ) } { error }
			</p>
		);
	}
	
	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Hierarchy Levels', 'gatherpress-venue-hierarchy' ) }
					initialOpen={ true }
				>
					<DualRangeControl
						startLevel={ startLevel }
						endLevel={ Math.min( endLevel, maxDepth ) }
						maxLevel={ maxDepth }
						onChange={ ( { startLevel: newStart, endLevel: newEnd } ) => {
							setAttributes( {
								startLevel: newStart,
								endLevel: newEnd,
							} );
						} }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Display Options', 'gatherpress-venue-hierarchy' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __( 'Show venue', 'gatherpress-venue-hierarchy' ) }
						help={ __( 'Display the venue name at the end of the location hierarchy', 'gatherpress-venue-hierarchy' ) }
						checked={ showVenue }
						onChange={ ( value ) => setAttributes( { showVenue: value } ) }
					/>
					<ToggleControl
						label={ __( 'Enable term links', 'gatherpress-venue-hierarchy' ) }
						help={ __( 'Link each location term to its archive page', 'gatherpress-venue-hierarchy' ) }
						checked={ enableLinks }
						onChange={ ( value ) => setAttributes( { enableLinks: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ isLoading ? <Spinner /> : locationHierarchy }
			</p>
		</>
	);
}