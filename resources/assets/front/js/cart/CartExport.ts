import Swal from 'sweetalert2';
import { CartRelayHelpers } from '@helpers/utils/CartRelayHelpers';
import { __ } from '@helpers/utils/i18n';

interface CartExportOptions {
	selector?: string;
	loadingText?: string;
	successTitle?: string;
	successDelay?: number;
}

class CartExport {

	private readonly selector: string;
	private readonly loadingText: string;
	private readonly successTitle: string;
	private readonly successDelay: number;

	constructor( {
		selector = '[data-cart-relay-export-button]',
		loadingText = __( 'Generating CSV...' ),
		successTitle = __( 'Cart exported' ),
		successDelay = 800,
	}: CartExportOptions = {} ) {
		this.selector = selector;
		this.loadingText = loadingText;
		this.successTitle = successTitle;
		this.successDelay = successDelay;
		this.handleClick = this.handleClick.bind( this );
	}

	init(): void {
		document.addEventListener( 'click', this.handleClick );
	}

	destroy(): void {
		document.removeEventListener( 'click', this.handleClick );
	}

	private handleClick( event: MouseEvent ): void {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const button = target.closest<HTMLAnchorElement>( this.selector );

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

	private getExportUrl( button: HTMLAnchorElement ): string {
		return button.dataset.exportUrl || button.href || '';
	}

	private startDownload( exportUrl: string ): void {
		CartRelayHelpers.swalShowLoading( this.loadingText );

		window.location.href = exportUrl;
		window.setTimeout( () => this.showSuccess(), this.successDelay );
	}

	private showSuccess(): void {
		Swal.fire( {
			icon              : 'success',
			title             : this.successTitle,
			timer             : 1500,
			showConfirmButton : false,
		} );
	}

}

export { CartExport };
