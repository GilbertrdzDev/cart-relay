import Swal from 'sweetalert2';
import { __ } from '@helpers/utils/i18n';

type AjaxErrors = Record<string, unknown> | unknown[];

interface AjaxErrorResponse {
	status: number;
	responseText?: string;
	responseJSON?: {
		errors?: AjaxErrors;
	};
}

type UrlParameterValue = string | boolean | Array<string | boolean>;
type UrlParameters = Record<string, UrlParameterValue>;

class CartRelayHelpers {

	static swalShowLoading( title = __( 'Loading...', 'cart-relay' ) ): void {
		Swal.fire( {
			title,
			didOpen: () => {
				Swal.showLoading();
			},
			showConfirmButton: false,
			icon: 'info',
		} );
	}

	static pluralizeOnlySuffix( count: number, suffix = 's' ): string {
		return count !== 1 ? suffix : '';
	}

	static ajaxErrorHandler( jqXhr: AjaxErrorResponse, textStatus: string, errorThrown = '' ): void {
		let text = '';

		if ( jqXhr.status === 0 ) {
			text = __( 'Not connected. Verify your network connection.', 'cart-relay' );
		} else if ( jqXhr.status === 404 ) {
			text = __( 'Requested page not found [404].', 'cart-relay' );
		} else if ( jqXhr.status === 500 ) {
			text = __( 'Internal server error [500].', 'cart-relay' );
		} else if ( textStatus === 'parsererror' ) {
			text = __( 'Requested JSON parse failed.', 'cart-relay' );
		} else if ( textStatus === 'timeout' ) {
			text = __( 'Timeout error.', 'cart-relay' );
		} else if ( textStatus === 'abort' ) {
			text = __( 'Ajax request aborted.', 'cart-relay' );
		} else {
			text = `${__( 'Uncaught error:', 'cart-relay' )} ${jqXhr.responseText ?? ''}`;
		}

		if ( jqXhr.status === 422 ) {
			const errors = jqXhr.responseJSON?.errors ?? {};
			const errorValues = Array.isArray( errors ) ? errors : Object.values( errors );

			console.log( `Error ${jqXhr.status}: ${errorThrown}`, errors );

			const sanitizedErrorsHtml = errorValues.reduce( ( html, value, index ) => {
				return `${html}
					<li class="list-group-item alert alert-danger">
						<span class="round round-primary q-round">${index + 1}</span>${CartRelayHelpers.escapeHtml( String( value ) )}
					</li>
				`;
			}, '<ul class="list-group" style="text-align: start">' ) + '</ul>';

			Swal.fire( {
				title: __( 'Review the highlighted issues', 'cart-relay' ),
				html: sanitizedErrorsHtml,
				width: 'auto',
				confirmButtonText: __( 'OK', 'cart-relay' ),
				icon: 'warning',
			} );

			return;
		}

		Swal.fire( {
			icon: 'error',
			title: __( 'Oops...', 'cart-relay' ),
			text,
			footer: `<a href>${__( 'Why do I have this issue?', 'cart-relay' )}</a>`,
		} );
	}

	static escapeHtml( value: string ): string {
		return value
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	static objectLength( object: object ): number {
		return Object.keys( object ).length;
	}

	static getUrlParameters( url?: string ): UrlParameters {
		const queryStringSource = url ? url.split( '?' )[1] : window.location.search.slice( 1 );
		const obj: Record<string, string | boolean | Array<string | boolean>> = {};

		if ( ! queryStringSource ) {
			return obj;
		}

		const queryString = queryStringSource.split( '#' )[0];
		const params = queryString.split( '&' );

		params.forEach( ( param ) => {
			const pair = param.split( '=' );
			const paramName = pair[0].toLowerCase();
			const rawValue = typeof pair[1] === 'undefined' ? true : pair[1];
			const paramValue = typeof rawValue === 'string' ? rawValue.toLowerCase() : rawValue;

			if ( paramName.match( /\[(\d+)?]$/ ) ) {
				const key = paramName.replace( /\[(\d+)?]/, '' );
				const existing = obj[key];
				const values = Array.isArray( existing ) ? existing : [];
				const indexMatch = /\[(\d+)]$/.exec( paramName );

				if ( indexMatch ) {
					values[Number( indexMatch[1] )] = paramValue;
				} else {
					values.push( paramValue );
				}

				obj[key] = values;
				return;
			}

			const existing = obj[paramName];

			if ( typeof existing === 'undefined' ) {
				obj[paramName] = paramValue;
			} else if ( Array.isArray( existing ) ) {
				existing.push( paramValue );
			} else {
				obj[paramName] = [ existing, paramValue ];
			}
		} );

		return obj;
	}

	static getUrlParameterString( url: UrlParameters ): string {
		const params = Object.entries( url ).map( ( [ key, value ] ) => {
			return `${key}=${Array.isArray( value ) ? value.join( ',' ) : String( value )}`;
		} );

		return `?${params.join( '&' )}`;
	}

}

export { CartRelayHelpers };
