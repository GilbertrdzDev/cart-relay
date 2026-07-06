import Swal from 'sweetalert2';

class WoocartBridgeHelpers {

	static swalShowLoading( title = 'Loading...' ) {

		Swal.fire( {
			// title             : "Success",
			title,
			didOpen           : () => {
				Swal.showLoading();
			},
			showConfirmButton : false,
			icon              : 'info',
		} );

	}

	static pluralizeOnlySuffix( count, suffix = 's' ) {

		return count !== 1 ? suffix : '';

	}

	static ajaxErrorHandler( jqXhr, textStatus, errorThrown = '' ) {

		let text = '';

		if ( jqXhr.status === 0 ) {

			text = 'Not connect: Verify Network.';

		} else if ( jqXhr.status === 404 ) {

			text = 'Requested page not found [404].';

		} else if ( jqXhr.status === 500 ) {

			text = 'Internal Server Error [500].';

		} else if ( textStatus === 'parsererror' ) {

			text = 'Requested JSON parse failed.';

		} else if ( textStatus === 'timeout' ) {

			text = 'Time out error.';

		} else if ( textStatus === 'abort' ) {

			text = 'Ajax request aborted.';

		} else {

			text = 'Uncaught Error: ' + jqXhr.responseText;

		}

		/**
		 * @var {object} Swal
		 * @param {Function} Swal.fire
		 */

		if ( jqXhr.status === 422 ) {

			let errs       = jqXhr.responseJSON,
				{ errors } = errs;

			console.log( `Error ${jqXhr.status}: ${errorThrown}`, errors );

			let sanitizedErrorsHtml = '<ul class="list-group" style="text-align: left">',
				count      = 0;

			$.each( errors, function ( index, value ) {

				count++;
				sanitizedErrorsHtml += `
                    <li class="list-group-item alert alert-danger">
                        <span class="round round-primary q-round">${count}</span>${ WoocartBridgeHelpers.escapeHtml( String( value ) )}
                    </li>
                `;

			} );

			sanitizedErrorsHtml += '</ul>';

			Swal.fire( {
				title             : `Missing data to add and review`,
				html              : sanitizedErrorsHtml,
				width             : 'auto',
				confirmButtonText : 'Ok',
				type              : 'warning'
			} );

		} else {

			Swal.fire( {
				type   : 'error',
				title  : 'Oops...',
				text,
				footer : '<a href>Why do I have this issue?</a>'
			} );

		}

	}

	static escapeHtml( value ) {
		return value
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	static objectLength( object ) {
		return Object.keys( object ).length;
	}

	static getUrlParameters( url ) {

		// get query string from url (optional) or window
		var queryString = url ? url.split( '?' )[ 1 ] : window.location.search.slice( 1 );

		// we'll store the parameters here
		var obj = {};

		// if query string exists
		if ( queryString ) {

			// stuff after # is not part of query string, so get rid of it
			queryString = queryString.split( '#' )[ 0 ];

			// split our query string into its component parts
			var arr = queryString.split( '&' );

			for ( var i = 0; i < arr.length; i++ ) {
				// separate the keys and the values
				var a = arr[ i ].split( '=' );

				// set parameter name and value (use 'true' if empty)
				var paramName = a[ 0 ];
				var paramValue = typeof (a[ 1 ]) === 'undefined' ? true : a[ 1 ];

				// (optional) keep case consistent
				paramName = paramName.toLowerCase();
				if ( typeof paramValue === 'string' ) paramValue = paramValue.toLowerCase();

				// if the paramName ends with square brackets, e.g. colors[] or colors[2]
				if ( paramName.match( /\[(\d+)?]$/ ) ) {

					// create key if it doesn't exist
					var key = paramName.replace( /\[(\d+)?]/, '' );
					if ( !obj[ key ] ) obj[ key ] = [];

					// if it's an indexed array e.g. colors[2]
					if ( paramName.match( /\[\d+]$/ ) ) {
						// get the index value and add the entry at the appropriate position
						var index = /\[(\d+)]/.exec( paramName )[ 1 ];
						obj[ key ][ index ] = paramValue;
					} else {
						// otherwise add the value to the end of the array
						obj[ key ].push( paramValue );
					}
				} else {
					// we're dealing with a string
					if ( !obj[ paramName ] ) {
						// if it doesn't exist, create property
						obj[ paramName ] = paramValue;
					} else if ( obj[ paramName ] && typeof obj[ paramName ] === 'string' ) {
						// if property does exist and it's a string, convert it to an array
						obj[ paramName ] = [ obj[ paramName ] ];
						obj[ paramName ].push( paramValue );
					} else {
						// otherwise add the property
						obj[ paramName ].push( paramValue );
					}
				}
			}
		}

		return obj;

	}

	static getUrlParameterString( url ) {

		let finalUrl = '?',
			c         = 0;

		for ( let key in url ) {

			if ( c > 0 ) {
				finalUrl += '&';
			}

			finalUrl += `${key}=${url[ key ]}`;
			c++;

		}

		return finalUrl;

	}


}

export { WoocartBridgeHelpers };
