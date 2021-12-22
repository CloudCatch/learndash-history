import React, {useState, useEffect} from 'react';
import ReactDOM from 'react-dom';

const {__} = wp.i18n;
const learndashHistory = window.learndashhistory || {};
const el = document.getElementById( 'learndash-history-migrator' );

function LearnDashHistoryMigrator() {
	const [migrated, setMigrated] = useState( 0 );
	const [running, setRunning] = useState( false );
	const [limit, setLimit] = useState( 2500 );
	const [offset, setOffset] = useState( 0 );
	const [start, setStart] = useState( 0 );
	const [activityCount, setActivityCount] = useState( 0 );
	const [error, setError] = useState( '' );

	const run = () => {
		setError( '' );
		fetch( learndashHistory.restUrl + 'learndashhistory/v1/migrate/count', {
			method : 'GET',
			headers: {
				'X-WP-Nonce'  : learndashHistory.nonce,
				'Content-Type': 'application/json'
			}
		} )
			.then( response => response.json() )
			.then( count => {
				if ( count ) {
					setActivityCount( count );
					setRunning( true );
				}
			} )
			.catch( ( error ) => {
				console.error( __( 'LearnDash History: ' ) + error );
				setRunning( false );
			} );
	};

	const nextRun = ( data ) => {
		setOffset( offset + data.migrated );
		setStart( data.start );
		setMigrated( migrated + data.migrated );
	};

	useEffect( () => {
		migrate();
	}, [running, migrated] )

	const migrate = () => {
		if ( ! running ) {
			window.onbeforeunload = undefined;
			return;
		}

		window.onbeforeunload = function() {
			return __( 'Migrator is still running', 'learndash-history' );
		}

		fetch( learndashHistory.restUrl + 'learndashhistory/v1/migrate', {
			method : 'POST',
			headers: {
				'X-WP-Nonce'  : learndashHistory.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( {
				offset: offset,
				limit : limit,
				start : start
			} )
		} )
			.then( response => response.json() )
			.then( data => {
				if ( data.migrated ) {
					nextRun( data );
				} else if ( data.complete ) {
					setOffset( activityCount );
					setRunning( false );
				}
			} )
			.catch( ( error ) => {
				console.error( __( 'LearnDash History: ' ) + error );
				setError( error );
				setRunning( false );
			} );
	};

	return (
		<div className="learndash-history-migrate-form">
			<p>{ __( 'Sync all historical LearnDash user activity to LearnDash Activity History.', 'learndash-history' )}</p>
			<label>{ __( 'Rows per run:', 'learndash-history' ) }</label>
			<input type="number" defaultValue={limit} onChange={( e ) => setLimit( e.target.value )} step="10" min="10" />
			<button onClick={() => run()} disabled={running} className="button button-primary">{__( 'Migrate', 'learndash-history' )}</button>

			{ error ? <p className="learndash-history-error">{error.toString()}</p> : null }

			{ running && activityCount ? <div className="learndash-history-migrate-progress-bar"><span style={{maxWidth: Math.round( offset / activityCount * 100 ) + "%"}} /></div> : '' }
		</div>
	);
}

if ( el ) {
	ReactDOM.render( <LearnDashHistoryMigrator />, el );
}
