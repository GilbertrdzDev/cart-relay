import Swal from 'sweetalert2';
import { WoocartBridgeHelpers } from '../../../../helpers/utils/WoocartBridgeHelpers';

class CartExport {

	constructor( {
		selector = '[data-woocart-bridge-export-button]',
		loadingText = 'Generando CSV...',
		successTitle = 'Carrito exportado',
		successDelay = 800,
	} = {} ) {
		this.selector = selector;
		this.loadingText = loadingText;
		this.successTitle = successTitle;
		this.successDelay = successDelay;
		this.handleClick = this.handleClick.bind( this );
	}

	init() {
		document.addEventListener( 'click', this.handleClick );
	}

	destroy() {
		document.removeEventListener( 'click', this.handleClick );
	}

	handleClick( event ) {
		const button = event.target.closest( this.selector );

		if ( ! button ) {
			return;
		}

		const exportUrl = this.getExportUrl( button );

		if ( ! exportUrl ) {
			return;
		}

		event.preventDefault();
		this.startDownload( exportUrl );
	}

	getExportUrl( button ) {
		return button.dataset.exportUrl || button.href || '';
	}

	startDownload( exportUrl ) {
		WoocartBridgeHelpers.swalShowLoading( this.loadingText );

		window.location.href = exportUrl;
		window.setTimeout( () => this.showSuccess(), this.successDelay );
	}

	showSuccess() {
		Swal.fire( {
			icon              : 'success',
			title             : this.successTitle,
			timer             : 1500,
			showConfirmButton : false,
		} );
	}

}

export { CartExport };
