import Swal from 'sweetalert2';
import { WoocartBridgeHelpers } from '@helpers/utils/WoocartBridgeHelpers';
import { __, _n, sprintf } from '@helpers/utils/i18n';

declare global {
	interface Window {
		jQuery?: ( target: unknown ) => {
			one: ( eventName: string, handler: () => void ) => void;
			trigger: ( eventName: string, extraParameters?: unknown[] ) => void;
		};
	}
}

interface CartImportOptions {
	formSelector?: string;
	chunkSize?: number;
}

interface RowError {
	row: number;
	message: string;
}

interface ImportItem {
	row: number;
	product_id: number;
	variation_id: number;
	sku: string;
	name: string;
	quantity: number;
	image: string;
	permalink: string;
}

interface PreviewResponse {
	items: ImportItem[];
	errors: RowError[];
	import_mode: string;
}

interface UpdatedCartItem {
	cart_item_key: string;
	product_id: number;
	variation_id: number;
	sku: string;
	quantity: number;
}

interface ChunkResponse {
	chunk_index: number;
	total_chunks: number;
	added: number;
	errors: RowError[];
	updated_items?: UpdatedCartItem[];
}

interface AjaxErrorData {
	errors?: string[] | RowError[];
}

interface AjaxResponse<T> {
	success: boolean;
	data?: T | AjaxErrorData;
}

class CartImport {

	private readonly formSelector: string;
	private readonly chunkSize: number;
	private readonly selectedFiles = new WeakMap<HTMLElement, File>();

	constructor( {
		formSelector = '[data-woocart-bridge-import-form]',
		chunkSize = 25,
	}: CartImportOptions = {} ) {
		this.formSelector = formSelector;
		this.chunkSize = chunkSize;
	}

	init(): void {
		document.querySelectorAll<HTMLElement>( this.formSelector ).forEach( ( form ) => {
			if ( form.dataset.wcbImportReady === 'true' ) {
				return;
			}

			form.dataset.wcbImportReady = 'true';
			this.bindEvents( form );
		} );
	}

	private bindEvents( form: HTMLElement ): void {
		const input = form.querySelector<HTMLInputElement>( '[data-wcb-import-file]' );
		const dropzone = form.querySelector<HTMLElement>( '[data-wcb-import-dropzone]' );
		const removeButton = form.querySelector<HTMLElement>( '[data-wcb-import-remove]' );
		const previewButton = form.querySelector<HTMLButtonElement>( '[data-wcb-import-preview]' );

		dropzone?.addEventListener( 'click', () => input?.click() );
		dropzone?.addEventListener( 'dragover', ( event ) => this.handleDragOver( event, dropzone ) );
		dropzone?.addEventListener( 'dragleave', () => dropzone.classList.remove( 'is-dragging' ) );
		dropzone?.addEventListener( 'drop', ( event ) => this.handleDrop( event, form, input, dropzone ) );
		input?.addEventListener( 'change', () => this.handleFileSelect( form, input.files?.[0] ) );
		removeButton?.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			event.stopPropagation();
			this.removeFile( form, input );
		} );
		previewButton?.addEventListener( 'click', () => void this.previewImport( form ) );
	}

	private handleDragOver( event: DragEvent, dropzone: HTMLElement ): void {
		event.preventDefault();
		dropzone.classList.add( 'is-dragging' );
	}

	private handleDrop(
		event: DragEvent,
		form: HTMLElement,
		input: HTMLInputElement | null,
		dropzone: HTMLElement
	): void {
		event.preventDefault();
		dropzone.classList.remove( 'is-dragging' );

		const file = event.dataTransfer?.files?.[0];

		if ( ! file ) {
			return;
		}

		if ( input && typeof DataTransfer !== 'undefined' ) {
			const transfer = new DataTransfer();
			transfer.items.add( file );
			input.files = transfer.files;
		}

		this.handleFileSelect( form, file );
	}

	private handleFileSelect( form: HTMLElement, file?: File ): void {
		if ( ! file ) {
			return;
		}

		if ( ! this.isCsvFile( file ) ) {
			this.renderSummary( form, {
				added: 0,
				errors: [ { row: 0, message: __( 'Select a valid .csv file.' ) } ],
			} );
			return;
		}

		this.selectedFiles.set( form, file );
		this.setFileMeta( form, file );
		this.clearSummary( form );
	}

	private removeFile( form: HTMLElement, input: HTMLInputElement | null ): void {
		this.selectedFiles.delete( form );

		if ( input ) {
			input.value = '';
		}

		this.setDropzoneEmpty( form );
		this.clearSummary( form );
	}

	private async previewImport( form: HTMLElement ): Promise<void> {
		const file = this.selectedFiles.get( form );

		if ( ! file ) {
			this.renderSummary( form, {
				added: 0,
				errors: [ { row: 0, message: __( 'Select a CSV file before continuing.' ) } ],
			} );
			return;
		}

		const formData = new FormData();
		formData.append( 'action', this.getDatasetValue( form, 'previewAction' ) );
		formData.append( 'nonce', this.getDatasetValue( form, 'previewNonce' ) );
		formData.append( 'csv_file', file );

		WoocartBridgeHelpers.swalShowLoading( __( 'Reading CSV...' ) );

		const payload = await this.postFormData<PreviewResponse>( form, formData );

		if ( ! payload ) {
			return;
		}

		void this.openPreviewModal( form, payload );
	}

	private async openPreviewModal( form: HTMLElement, response: PreviewResponse ): Promise<void> {
		const canImport = response.items.length > 0;
		const result = await Swal.fire( {
			title: '',
			html: this.renderPreviewModal( response ),
			width: 'min(960px, 96vw)',
			padding: 0,
			showCloseButton: true,
			showCancelButton: true,
			showConfirmButton: canImport,
			confirmButtonText: sprintf(
				_n( 'Import %d product', 'Import %d products', response.items.length ),
				response.items.length
			),
			cancelButtonText: __( 'Cancel' ),
			buttonsStyling: false,
			customClass: {
				popup: 'wcb-import-preview-modal',
				htmlContainer: 'wcb-import-preview-modal__html',
				actions: 'wcb-import-preview-modal__actions',
				cancelButton: 'wcb-import-preview-modal__cancel',
				confirmButton: 'wcb-import-preview-modal__confirm',
				closeButton: 'wcb-import-preview-modal__close',
			},
		} );

		if ( result.isConfirmed && canImport ) {
			await this.importChunks( form, response.items );
		}
	}

	private async importChunks( form: HTMLElement, items: ImportItem[] ): Promise<void> {
		const chunks = this.chunkItems( items );
		const errors: RowError[] = [];
		const updatedItems: UpdatedCartItem[] = [];
		let added = 0;

		WoocartBridgeHelpers.swalShowLoading( __( 'Importing products...' ) );
		this.updateProgress( 0, items.length, added, errors.length );

		for ( let index = 0; index < chunks.length; index++ ) {
			const chunkResponse = await this.importChunk( form, chunks[index], index, chunks.length );

			if ( ! chunkResponse ) {
				return;
			}

			added += chunkResponse.added;
			errors.push( ...chunkResponse.errors );
			updatedItems.push( ...( chunkResponse.updated_items || [] ) );
			this.updateProgress(
				Math.min( ( index + 1 ) * this.chunkSize, items.length ),
				items.length,
				added,
				errors.length
			);
		}

		this.clearSummary( form );
		await this.refreshCartDisplay( updatedItems );
		await this.showImportResult( added, errors );
	}

	private async importChunk(
		form: HTMLElement,
		chunk: ImportItem[],
		index: number,
		totalChunks: number
	): Promise<ChunkResponse | null> {
		const formData = new FormData();
		formData.append( 'action', this.getDatasetValue( form, 'chunkAction' ) );
		formData.append( 'nonce', this.getDatasetValue( form, 'chunkNonce' ) );
		formData.append( 'items', JSON.stringify( chunk ) );
		formData.append( 'chunk_index', String( index ) );
		formData.append( 'total_chunks', String( totalChunks ) );
		formData.append( 'import_mode', this.getImportMode( form ) );

		return this.postFormData<ChunkResponse>( form, formData );
	}

	private async postFormData<T>( form: HTMLElement, formData: FormData ): Promise<T | null> {
		const response = await fetch( this.getDatasetValue( form, 'ajaxUrl' ), {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} );
		const responseText = await response.text();
		const payload = this.parseAjaxResponse<T>( responseText );

		if ( ! response.ok || ! payload?.success ) {
			this.handleAjaxError( response.status, payload, responseText );
			return null;
		}

		return payload.data as T;
	}

	private parseAjaxResponse<T>( responseText: string ): AjaxResponse<T> | null {
		try {
			return JSON.parse( responseText ) as AjaxResponse<T>;
		} catch ( error ) {
			console.error( 'Invalid AJAX response', error, responseText );
			return null;
		}
	}

	private handleAjaxError<T>( status: number, payload: AjaxResponse<T> | null, responseText: string ): void {
		const data = payload?.data;
		const errors = this.getResponseErrors( data );

		WoocartBridgeHelpers.ajaxErrorHandler(
			{
				status,
				responseText,
				responseJSON: {
					errors: errors.length > 0 ? errors : [ __( 'The request could not be processed.' ) ],
				},
			},
			'error'
		);
	}

	private getResponseErrors( data: unknown ): string[] {
		if ( ! data || typeof data !== 'object' || ! ( 'errors' in data ) ) {
			return [];
		}

		const errors = ( data as AjaxErrorData ).errors;

		if ( ! Array.isArray( errors ) ) {
			return [];
		}

		return errors.map( ( error ) => {
			if ( typeof error === 'string' ) {
				return error;
			}

			return this.formatRowError( error );
		} );
	}

	private renderPreviewModal( response: PreviewResponse ): string {
		const totalItems = response.items.length + response.errors.length;

		return `
			<div class="wcb-import-preview">
				<div class="wcb-import-preview__header">
					<h2>${__( 'Import preview' )}</h2>
					<p>${__( 'Review products before adding them to WooCommerce.' )}</p>
				</div>
				<div class="wcb-import-preview__summary">
					<div class="wcb-import-preview__badges">
						<span class="wcb-import-preview__badge wcb-import-preview__badge--ok">
							<span aria-hidden="true"></span>
							<strong>${response.items.length}</strong> ${__( 'valid' )}
						</span>
						<span class="wcb-import-preview__badge wcb-import-preview__badge--error">
							<span aria-hidden="true"></span>
							<strong>${response.errors.length}</strong> ${_n( 'with issue', 'with issues', response.errors.length )}
						</span>
						<span class="wcb-import-preview__badge wcb-import-preview__badge--total">
							<strong>${totalItems}</strong> ${__( 'total' )}
						</span>
					</div>
				</div>
				<div class="wcb-import-preview-table-wrapper">
					<table class="wcb-import-preview-table">
						<thead>
							<tr>
								<th>${__( 'Product' )}</th>
								<th>${__( 'Product / variation' )}</th>
								<th>${__( 'Qty.' )}</th>
								<th>${__( 'Status' )}</th>
							</tr>
						</thead>
						<tbody>
							${response.items.map( ( item ) => this.renderPreviewRow( item ) ).join( '' )}
							${response.errors.map( ( error ) => this.renderPreviewErrorRow( error ) ).join( '' )}
						</tbody>
					</table>
				</div>
				<div class="wcb-import-preview__footer-note">
					${this.renderPreviewFooterNote( response.errors.length )}
				</div>
			</div>
		`;
	}

	private renderPreviewRow( item: ImportItem ): string {
		return `
			<tr>
				<td>
					${this.renderPreviewProduct( item )}
				</td>
				<td>${this.renderProductVariationLink( item )}</td>
				<td class="wcb-import-preview-table__number">${item.quantity}</td>
				<td class="wcb-import-preview-table__status"><span class="wcb-import-status wcb-import-status--ok">${__( 'Ready' )}</span></td>
			</tr>
		`;
	}

	private renderPreviewErrorRow( error: RowError ): string {
		return `
			<tr class="wcb-import-preview-table__row--error">
				<td>
					<div class="wcb-import-preview-product">
						<span class="wcb-import-preview-product__fallback">!</span>
						<div class="wcb-import-preview-product__meta">
							<strong>${error.row > 0 ? sprintf( __( 'Row %d' ), error.row ) : '-'}</strong>
							<span class="wcb-import-preview-product__error">${WoocartBridgeHelpers.escapeHtml( error.message )}</span>
						</div>
					</div>
				</td>
				<td>-</td>
				<td class="wcb-import-preview-table__number">-</td>
				<td class="wcb-import-preview-table__status"><span class="wcb-import-status wcb-import-status--error">${__( 'With issue' )}</span></td>
			</tr>
		`;
	}

	private renderPreviewThumb( item: ImportItem ): string {
		if ( item.image ) {
			return `<img class="wcb-import-preview-product__image" src="${WoocartBridgeHelpers.escapeHtml( item.image )}" alt="">`;
		}

		return `<span class="wcb-import-preview-product__fallback">${WoocartBridgeHelpers.escapeHtml( this.getInitial( item.name ) )}</span>`;
	}

	private renderPreviewProduct( item: ImportItem ): string {
		const content = `
			${this.renderPreviewThumb( item )}
			<div class="wcb-import-preview-product__meta">
				<strong>${WoocartBridgeHelpers.escapeHtml( item.name )}</strong>
				<span>SKU ${WoocartBridgeHelpers.escapeHtml( item.sku || '-' )}</span>
			</div>
		`;

		if ( ! item.permalink ) {
			return `<div class="wcb-import-preview-product">${content}</div>`;
		}

		return `
			<a
				class="wcb-import-preview-product wcb-import-preview-product--link"
				href="${WoocartBridgeHelpers.escapeHtml( item.permalink )}"
				target="_blank"
				rel="noopener noreferrer"
			>
				${content}
			</a>
		`;
	}

	private renderPreviewFooterNote( errorCount: number ): string {
		if ( errorCount === 0 ) {
			return __( 'All valid products will be included in the import.' );
		}

		return sprintf(
			_n(
				'%d product with an issue will be skipped during import.',
				'%d products with issues will be skipped during import.',
				errorCount
			),
			errorCount
		);
	}

	private updateProgress( current: number, total: number, added: number, errorCount: number ): void {
		const percent = total > 0 ? Math.round( ( current / total ) * 100 ) : 0;

		Swal.update( {
			title: sprintf( __( 'Importing products... %1$d / %2$d' ), current, total ),
			html: `
				<div class="wcb-import-progress">
					<div class="wcb-import-progress__track">
						<div class="wcb-import-progress__bar" style="width: ${percent}%"></div>
					</div>
					<div class="wcb-import-progress__meta">
						<span>${sprintf( __( 'Added: %d' ), added )}</span>
						<span>${sprintf( __( 'With issues: %d' ), errorCount )}</span>
					</div>
				</div>
			`,
		} );
	}

	private async showImportResult( added: number, errors: RowError[] ): Promise<void> {
		if ( errors.length === 0 ) {
			await Swal.fire( {
				toast: true,
				position: 'top-end',
				icon: 'success',
				title: __( 'Cart imported' ),
				text: sprintf( __( 'Products added: %d' ), added ),
				timer: 5000,
				timerProgressBar: true,
				showConfirmButton: false,
				customClass: {
					popup: 'wcb-import-result-toast',
				},
			} );

			return;
		}

		await Swal.fire( {
			icon: 'warning',
			title: __( 'Import completed with issues' ),
			html: this.renderImportResultIssues( added, errors ),
			confirmButtonText: __( 'Close' ),
			buttonsStyling: false,
			customClass: {
				popup: 'wcb-import-result-modal',
				htmlContainer: 'wcb-import-result-modal__html',
				confirmButton: 'wcb-import-result-modal__confirm',
			},
		} );
	}

	private renderImportResultIssues( added: number, errors: RowError[] ): string {
		return `
			<div class="wcb-import-result">
				<div class="wcb-import-result__stats">
					<span class="wcb-import-result__stat wcb-import-result__stat--ok">
						<strong>${added}</strong>
						${_n( 'product added', 'products added', added )}
					</span>
					<span class="wcb-import-result__stat wcb-import-result__stat--error">
						<strong>${errors.length}</strong>
						${_n( 'issue', 'issues', errors.length )}
					</span>
				</div>
				<div class="wcb-import-result__errors">
					${errors.map( ( error ) => `<p>${WoocartBridgeHelpers.escapeHtml( this.formatRowError( error ) )}</p>` ).join( '' )}
				</div>
			</div>
		`;
	}

	private renderSummary( form: HTMLElement, summary: { added: number; errors: RowError[] } ): void {
		const summaryElement = form.querySelector<HTMLElement>( '[data-wcb-import-summary]' );

		if ( ! summaryElement ) {
			return;
		}

		const errorsHtml = summary.errors.length > 0
			? `<ul>${summary.errors.map( ( error ) => `<li>${WoocartBridgeHelpers.escapeHtml( this.formatRowError( error ) )}</li>` ).join( '' )}</ul>`
			: `<p class="wcb-import-summary__success">${__( 'All products were added successfully.' )}</p>`;

		summaryElement.hidden = false;
		summaryElement.innerHTML = `
			<h3>${__( 'Import summary' )}</h3>
			<p>${sprintf( __( 'Products added: %d' ), summary.added )}</p>
			<p>${sprintf( __( 'With issues: %d' ), summary.errors.length )}</p>
			${errorsHtml}
		`;
	}

	private clearSummary( form: HTMLElement ): void {
		const summaryElement = form.querySelector<HTMLElement>( '[data-wcb-import-summary]' );

		if ( summaryElement ) {
			summaryElement.hidden = true;
			summaryElement.innerHTML = '';
		}
	}

	private setFileMeta( form: HTMLElement, file: File ): void {
		const meta = form.querySelector<HTMLElement>( '[data-wcb-import-file-meta]' );
		const emptyState = form.querySelector<HTMLElement>( '[data-wcb-import-empty-state]' );
		const dropzone = form.querySelector<HTMLElement>( '[data-wcb-import-dropzone]' );
		const icon = form.querySelector<SVGElement>( '.wcb-import-dropzone__icon' );
		const name = form.querySelector<HTMLElement>( '[data-wcb-import-file-name]' );
		const size = form.querySelector<HTMLElement>( '[data-wcb-import-file-size]' );

		if ( meta ) {
			meta.hidden = false;
		}

		if ( emptyState ) {
			emptyState.hidden = true;
		}

		if ( dropzone ) {
			dropzone.classList.add( 'has-file' );
		}

		if ( icon ) {
			icon.innerHTML = this.getReadyIconPath();
		}

		if ( name ) {
			name.textContent = file.name;
		}

		if ( size ) {
			size.textContent = this.formatFileSize( file.size );
		}
	}

	private setDropzoneEmpty( form: HTMLElement ): void {
		const meta = form.querySelector<HTMLElement>( '[data-wcb-import-file-meta]' );
		const emptyState = form.querySelector<HTMLElement>( '[data-wcb-import-empty-state]' );
		const dropzone = form.querySelector<HTMLElement>( '[data-wcb-import-dropzone]' );
		const icon = form.querySelector<SVGElement>( '.wcb-import-dropzone__icon' );

		if ( meta ) {
			meta.hidden = true;
		}

		if ( emptyState ) {
			emptyState.hidden = false;
		}

		if ( dropzone ) {
			dropzone.classList.remove( 'has-file' );
		}

		if ( icon ) {
			icon.innerHTML = this.getUploadIconPath();
		}
	}

	private chunkItems( items: ImportItem[] ): ImportItem[][] {
		const chunks: ImportItem[][] = [];

		for ( let index = 0; index < items.length; index += this.chunkSize ) {
			chunks.push( items.slice( index, index + this.chunkSize ) );
		}

		return chunks;
	}

	private getDatasetValue( form: HTMLElement, key: string ): string {
		return form.dataset[key] || '';
	}

	private getImportMode( form: HTMLElement ): string {
		return this.getDatasetValue( form, 'importMode' ) === 'replace' ? 'replace' : 'merge';
	}

	private isCsvFile( file: File ): boolean {
		return file.name.toLowerCase().endsWith( '.csv' );
	}

	private formatFileSize( size: number ): string {
		if ( size < 1024 ) {
			return `${size} B`;
		}

		if ( size < 1024 * 1024 ) {
			return `${( size / 1024 ).toFixed( 1 )} KB`;
		}

		return `${( size / 1024 / 1024 ).toFixed( 1 )} MB`;
	}

	private formatProductVariationId( item: ImportItem ): string {
		if ( item.variation_id > 0 ) {
			return `${item.product_id} / ${item.variation_id}`;
		}

		return `${item.product_id} / -`;
	}

	private renderProductVariationLink( item: ImportItem ): string {
		const label = WoocartBridgeHelpers.escapeHtml( this.formatProductVariationId( item ) );

		if ( ! item.permalink ) {
			return label;
		}

		return `
			<a
				class="wcb-import-preview-table__product-link"
				href="${WoocartBridgeHelpers.escapeHtml( item.permalink )}"
				target="_blank"
				rel="noopener noreferrer"
			>
				${label}
			</a>
		`;
	}

	private getInitial( value: string ): string {
		return ( value.trim().charAt( 0 ) || '?' ).toUpperCase();
	}

	private formatRowError( error: RowError ): string {
		return error.row > 0 ? sprintf( __( 'Row %1$d: %2$s' ), error.row, error.message ) : error.message;
	}

	private getUploadIconPath(): string {
		return '<path d="M12 3a1 1 0 0 1 .7.29l4 4a1 1 0 1 1-1.4 1.42L13 6.41V16a1 1 0 1 1-2 0V6.41L8.7 8.71a1 1 0 1 1-1.4-1.42l4-4A1 1 0 0 1 12 3Zm-7 13a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1Z" fill="currentColor"/>';
	}

	private getReadyIconPath(): string {
		return '<path d="M9.2 16.6a1 1 0 0 1-.7-.3l-3.2-3.2a1 1 0 1 1 1.4-1.42l2.48 2.47 7.77-7.78a1 1 0 0 1 1.42 1.42l-8.48 8.5a1 1 0 0 1-.69.31Z" fill="currentColor"/>';
	}

	private async refreshCartDisplay( updatedItems: UpdatedCartItem[] ): Promise<void> {
		this.syncVisibleCartQuantities( updatedItems );

		if ( await this.triggerWooCartUpdate() ) {
			return;
		}

		this.refreshCartFragments();
	}

	private syncVisibleCartQuantities( updatedItems: UpdatedCartItem[] ): void {
		const cartForm = document.querySelector<HTMLFormElement>( '.woocommerce-cart-form' );

		if ( ! cartForm || updatedItems.length === 0 ) {
			return;
		}

		const quantitiesByKey = new Map(
			updatedItems.map( ( item ) => [ item.cart_item_key, item.quantity ] )
		);

		cartForm.querySelectorAll<HTMLInputElement>( 'input.qty[name^="cart["][name$="[qty]"]' ).forEach( ( input ) => {
			const itemKey = this.getCartItemKeyFromQuantityInput( input );

			if ( ! itemKey || ! quantitiesByKey.has( itemKey ) ) {
				return;
			}

			input.value = String( quantitiesByKey.get( itemKey ) );
		} );
	}

	private getCartItemKeyFromQuantityInput( input: HTMLInputElement ): string {
		const match = input.name.match( /^cart\[(.+)]\[qty]$/ );

		return match ? match[1] : '';
	}

	private triggerWooCartUpdate(): Promise<boolean> {
		if ( typeof window.jQuery !== 'function' || ! document.querySelector( '.woocommerce-cart-form' ) ) {
			return Promise.resolve( false );
		}

		return new Promise( ( resolve ) => {
			let settled = false;
			const done = () => {
				if ( settled ) {
					return;
				}

				settled = true;
				this.init();
				resolve( true );
			};

			window.jQuery?.( document.body ).one( 'updated_wc_div', done );
			window.setTimeout( done, 4000 );
			window.jQuery?.( document ).trigger( 'wc_update_cart', [ true ] );
		} );
	}

	private refreshCartFragments(): void {
		if ( typeof window.jQuery === 'function' ) {
			window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
		}
	}

}

export { CartImport };
