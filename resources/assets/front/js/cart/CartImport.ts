import Swal from 'sweetalert2';
import { CartRelayHelpers } from '@helpers/utils/CartRelayHelpers';
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
	price: number;
	subtotal: number;
	image: string;
	permalink: string;
}

type ImportChunkItem = Pick<ImportItem, 'row' | 'product_id' | 'variation_id' | 'sku' | 'quantity'>;

interface PreviewCurrency {
	code: string;
	symbol: string;
	decimal_separator: string;
	thousand_separator: string;
	decimals: number;
}

interface PreviewResponse {
	items: ImportItem[];
	errors: RowError[];
	import_mode: string;
	currency?: PreviewCurrency;
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
	private isListening = false;

	constructor( {
		formSelector = '[data-cart-relay-import-form]',
		chunkSize = 25,
	}: CartImportOptions = {} ) {
		this.formSelector = formSelector;
		this.chunkSize = chunkSize;
		this.handleDocumentClick = this.handleDocumentClick.bind( this );
		this.handleDocumentChange = this.handleDocumentChange.bind( this );
		this.handleDocumentDragOver = this.handleDocumentDragOver.bind( this );
		this.handleDocumentDragLeave = this.handleDocumentDragLeave.bind( this );
		this.handleDocumentDrop = this.handleDocumentDrop.bind( this );
	}

	init(): void {
		if ( this.isListening ) {
			return;
		}

		this.isListening = true;
		document.addEventListener( 'click', this.handleDocumentClick );
		document.addEventListener( 'change', this.handleDocumentChange );
		document.addEventListener( 'dragover', this.handleDocumentDragOver );
		document.addEventListener( 'dragleave', this.handleDocumentDragLeave );
		document.addEventListener( 'drop', this.handleDocumentDrop );
	}

	private handleDocumentClick( event: MouseEvent ): void {
		const target = this.getEventTargetElement( event );

		if ( ! target ) {
			return;
		}

		const removeButton = target.closest<HTMLElement>( '[data-cr-import-remove]' );

		if ( removeButton ) {
			const form = this.getImportForm( removeButton );

			if ( ! form ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			this.removeFile( form, this.getFileInput( form ) );
			return;
		}

		const previewButton = target.closest<HTMLElement>( '[data-cr-import-preview]' );

		if ( previewButton ) {
			const form = this.getImportForm( previewButton );

			if ( ! form ) {
				return;
			}

			event.preventDefault();
			void this.previewImport( form );
			return;
		}

		const dropzone = target.closest<HTMLElement>( '[data-cr-import-dropzone]' );

		if ( ! dropzone ) {
			return;
		}

		const form = this.getImportForm( dropzone );

		if ( ! form ) {
			return;
		}

		event.preventDefault();
		this.getFileInput( form )?.click();
	}

	private handleDocumentChange( event: Event ): void {
		const target = event.target;

		if ( ! ( target instanceof HTMLInputElement ) || ! target.matches( '[data-cr-import-file]' ) ) {
			return;
		}

		const form = this.getImportForm( target );

		if ( ! form ) {
			return;
		}

		this.handleFileSelect( form, target.files?.[0] );
	}

	private handleDocumentDragOver( event: DragEvent ): void {
		const dropzone = this.getDropzoneFromEvent( event );

		if ( ! dropzone ) {
			return;
		}

		this.handleDragOver( event, dropzone );
	}

	private handleDocumentDragLeave( event: DragEvent ): void {
		const dropzone = this.getDropzoneFromEvent( event );

		if ( ! dropzone ) {
			return;
		}

		dropzone.classList.remove( 'is-dragging' );
	}

	private handleDocumentDrop( event: DragEvent ): void {
		const dropzone = this.getDropzoneFromEvent( event );

		if ( ! dropzone ) {
			return;
		}

		const form = this.getImportForm( dropzone );

		if ( ! form ) {
			return;
		}

		this.handleDrop( event, form, this.getFileInput( form ), dropzone );
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

	private getDropzoneFromEvent( event: Event ): HTMLElement | null {
		return this.getEventTargetElement( event )?.closest<HTMLElement>( '[data-cr-import-dropzone]' ) || null;
	}

	private getEventTargetElement( event: Event ): Element | null {
		return event.target instanceof Element ? event.target : null;
	}

	private getFileInput( form: HTMLElement ): HTMLInputElement | null {
		return form.querySelector<HTMLInputElement>( '[data-cr-import-file]' );
	}

	private getImportForm( element: Element ): HTMLElement | null {
		return element.closest<HTMLElement>( this.formSelector );
	}

	private handleFileSelect( form: HTMLElement, file?: File ): void {
		if ( ! file ) {
			return;
		}

		if ( ! this.isCsvFile( file ) ) {
			this.renderSummary( form, {
				added: 0,
				errors: [ { row: 0, message: __( 'Select a valid .csv file.', 'cart-relay' ) } ],
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
				errors: [ { row: 0, message: __( 'Select a CSV file before continuing.', 'cart-relay' ) } ],
			} );
			return;
		}

		const formData = new FormData();
		formData.append( 'action', this.getDatasetValue( form, 'previewAction' ) );
		formData.append( 'nonce', this.getDatasetValue( form, 'previewNonce' ) );
		formData.append( 'csv_file', file );

		CartRelayHelpers.swalShowLoading( __( 'Reading CSV...', 'cart-relay' ) );

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
				/* translators: %d: number of products ready to import. */
				_n( 'Import %d product', 'Import %d products', response.items.length, 'cart-relay' ),
				response.items.length
			),
			cancelButtonText: __( 'Cancel', 'cart-relay' ),
			buttonsStyling: false,
			customClass: {
				popup: 'cr-import-preview-modal',
				htmlContainer: 'cr-import-preview-modal__html',
				actions: 'cr-import-preview-modal__actions',
				cancelButton: 'cr-import-preview-modal__cancel',
				confirmButton: 'cr-import-preview-modal__confirm',
				closeButton: 'cr-import-preview-modal__close',
			},
		} );

		if ( result.isConfirmed && canImport ) {
			await this.importChunks( form, response.items );
		}
	}

	private async importChunks( form: HTMLElement, items: ImportItem[] ): Promise<void> {
		const payloadItems = this.getImportPayloadItems( items );
		const chunks = this.chunkItems( payloadItems );
		const errors: RowError[] = [];
		const updatedItems: UpdatedCartItem[] = [];
		let added = 0;

		CartRelayHelpers.swalShowLoading( __( 'Importing products...', 'cart-relay' ) );
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
		chunk: ImportChunkItem[],
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
		} catch {
			console.error( 'Invalid AJAX response', responseText );
			return null;
		}
	}

	private handleAjaxError<T>( status: number, payload: AjaxResponse<T> | null, responseText: string ): void {
		const data = payload?.data;
		const errors = this.getResponseErrors( data );

		CartRelayHelpers.ajaxErrorHandler(
			{
				status,
				responseText,
				responseJSON: {
					errors: errors.length > 0 ? errors : [ __( 'The request could not be processed.', 'cart-relay' ) ],
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
		const totalAmount = response.items.reduce( ( total, item ) => total + this.getNumber( item.subtotal ), 0 );

		return `
			<div class="cr-import-preview">
				<div class="cr-import-preview__header">
					<h2>${__( 'Import preview', 'cart-relay' )}</h2>
					<p>${__( 'Review products before adding them to WooCommerce.', 'cart-relay' )}</p>
				</div>
				<div class="cr-import-preview__summary">
					<div class="cr-import-preview__badges">
						<span class="cr-import-preview__badge cr-import-preview__badge--ok">
							<span aria-hidden="true"></span>
							<strong>${response.items.length}</strong> ${_n( 'valid product', 'valid products', response.items.length, 'cart-relay' )}
						</span>
						<span class="cr-import-preview__badge cr-import-preview__badge--error">
							<span aria-hidden="true"></span>
							<strong>${response.errors.length}</strong> ${_n( 'with issue', 'with issues', response.errors.length, 'cart-relay' )}
						</span>
						<span class="cr-import-preview__badge cr-import-preview__badge--total">
							<strong>${totalItems}</strong> ${_n( 'total product', 'total products', totalItems, 'cart-relay' )}
						</span>
					</div>
					<div class="cr-import-preview__amount">
						<span>${__( 'Amount to import', 'cart-relay' )}</span>
						<strong>${this.formatCurrency( totalAmount, response.currency )}</strong>
					</div>
				</div>
				<div class="cr-import-preview-table-wrapper">
					<table class="cr-import-preview-table">
						<thead>
							<tr>
								<th>${__( 'Product', 'cart-relay' )}</th>
								<th>${__( 'Product / variation', 'cart-relay' )}</th>
								<th>${__( 'Qty.', 'cart-relay' )}</th>
								<th>${__( 'Price', 'cart-relay' )}</th>
								<th>${__( 'Subtotal', 'cart-relay' )}</th>
								<th>${__( 'Status', 'cart-relay' )}</th>
							</tr>
						</thead>
						<tbody>
							${response.items.map( ( item ) => this.renderPreviewRow( item, response.currency ) ).join( '' )}
							${response.errors.map( ( error ) => this.renderPreviewErrorRow( error ) ).join( '' )}
						</tbody>
					</table>
				</div>
				<div class="cr-import-preview__footer-note">
					${this.renderPreviewFooterNote( response.errors.length )}
				</div>
			</div>
		`;
	}

	private renderPreviewRow( item: ImportItem, currency?: PreviewCurrency ): string {
		return `
			<tr>
				<td>
					${this.renderPreviewProduct( item )}
				</td>
				<td>${this.renderProductVariationLink( item )}</td>
				<td class="cr-import-preview-table__number">${item.quantity}</td>
				<td class="cr-import-preview-table__number">${this.formatCurrency( item.price, currency )}</td>
				<td class="cr-import-preview-table__number"><strong>${this.formatCurrency( item.subtotal, currency )}</strong></td>
				<td class="cr-import-preview-table__status"><span class="cr-import-status cr-import-status--ok">${__( 'Ready', 'cart-relay' )}</span></td>
			</tr>
		`;
	}

	private renderPreviewErrorRow( error: RowError ): string {
		const rowLabel = error.row > 0 ? sprintf(
			/* translators: %d: CSV row number. */
			__( 'Row %d', 'cart-relay' ),
			error.row
		) : '-';

		return `
			<tr class="cr-import-preview-table__row--error">
				<td>
					<div class="cr-import-preview-product">
						<span class="cr-import-preview-product__fallback">!</span>
						<div class="cr-import-preview-product__meta">
							<strong>${rowLabel}</strong>
							<span class="cr-import-preview-product__error">${CartRelayHelpers.escapeHtml( error.message )}</span>
						</div>
					</div>
				</td>
				<td>-</td>
				<td class="cr-import-preview-table__number">-</td>
				<td class="cr-import-preview-table__number">-</td>
				<td class="cr-import-preview-table__number">-</td>
				<td class="cr-import-preview-table__status"><span class="cr-import-status cr-import-status--error">${__( 'With issue', 'cart-relay' )}</span></td>
			</tr>
		`;
	}

	private renderPreviewThumb( item: ImportItem ): string {
		if ( item.image ) {
			return `<img class="cr-import-preview-product__image" src="${CartRelayHelpers.escapeHtml( item.image )}" alt="">`;
		}

		return `<span class="cr-import-preview-product__fallback">${CartRelayHelpers.escapeHtml( this.getInitial( item.name ) )}</span>`;
	}

	private renderPreviewProduct( item: ImportItem ): string {
		const content = `
			${this.renderPreviewThumb( item )}
			<div class="cr-import-preview-product__meta">
				<strong>${CartRelayHelpers.escapeHtml( item.name )}</strong>
				<span>${__( 'SKU', 'cart-relay' )} ${CartRelayHelpers.escapeHtml( item.sku || '-' )}</span>
			</div>
		`;

		if ( ! item.permalink ) {
			return `<div class="cr-import-preview-product">${content}</div>`;
		}

		return `
			<a
				class="cr-import-preview-product cr-import-preview-product--link"
				href="${CartRelayHelpers.escapeHtml( item.permalink )}"
				target="_blank"
				rel="noopener noreferrer"
			>
				${content}
			</a>
		`;
	}

	private renderPreviewFooterNote( errorCount: number ): string {
		if ( errorCount === 0 ) {
			return __( 'All valid products will be included in the import.', 'cart-relay' );
		}

		return sprintf(
			/* translators: %d: number of products that will be skipped. */
			_n(
				'%d product with an issue will be skipped during import.',
				'%d products with issues will be skipped during import.',
				errorCount,
				'cart-relay'
			),
			errorCount
		);
	}

	private updateProgress( current: number, total: number, added: number, errorCount: number ): void {
		const percent = total > 0 ? Math.round( ( current / total ) * 100 ) : 0;
		const title = sprintf(
			/* translators: 1: number of processed products, 2: total number of products. */
			__( 'Importing products... %1$d / %2$d', 'cart-relay' ),
			current,
			total
		);
		const addedLabel = sprintf(
			/* translators: %d: number of products added to the cart. */
			__( 'Added: %d', 'cart-relay' ),
			added
		);
		const issuesLabel = sprintf(
			/* translators: %d: number of products with import issues. */
			_n( 'With issue: %d', 'With issues: %d', errorCount, 'cart-relay' ),
			errorCount
		);

		Swal.update( {
			title,
			html: `
				<div class="cr-import-progress">
					<div class="cr-import-progress__track">
						<div class="cr-import-progress__bar" style="width: ${percent}%"></div>
					</div>
					<div class="cr-import-progress__meta">
						<span>${addedLabel}</span>
						<span>${issuesLabel}</span>
					</div>
				</div>
			`,
		} );
	}

	private async showImportResult( added: number, errors: RowError[] ): Promise<void> {
		if ( errors.length === 0 ) {
			const message = sprintf(
				/* translators: %d: number of products added to the cart. */
				_n( 'Product added: %d', 'Products added: %d', added, 'cart-relay' ),
				added
			);

			await Swal.fire( {
				toast: true,
				position: 'top-end',
				icon: 'success',
				title: __( 'Cart imported', 'cart-relay' ),
				text: message,
				timer: 5000,
				timerProgressBar: true,
				showConfirmButton: false,
				customClass: {
					popup: 'cr-import-result-toast',
				},
			} );

			return;
		}

		await Swal.fire( {
			icon: 'warning',
			title: __( 'Import completed with issues', 'cart-relay' ),
			html: this.renderImportResultIssues( added, errors ),
			confirmButtonText: __( 'Close', 'cart-relay' ),
			buttonsStyling: false,
			customClass: {
				popup: 'cr-import-result-modal',
				htmlContainer: 'cr-import-result-modal__html',
				confirmButton: 'cr-import-result-modal__confirm',
			},
		} );
	}

	private renderImportResultIssues( added: number, errors: RowError[] ): string {
		return `
			<div class="cr-import-result">
				<div class="cr-import-result__stats">
					<span class="cr-import-result__stat cr-import-result__stat--ok">
						<strong>${added}</strong>
						${_n( 'product added', 'products added', added, 'cart-relay' )}
					</span>
					<span class="cr-import-result__stat cr-import-result__stat--error">
						<strong>${errors.length}</strong>
						${_n( 'issue', 'issues', errors.length, 'cart-relay' )}
					</span>
				</div>
				<div class="cr-import-result__errors">
					${errors.map( ( error ) => `<p>${CartRelayHelpers.escapeHtml( this.formatRowError( error ) )}</p>` ).join( '' )}
				</div>
			</div>
		`;
	}

	private renderSummary( form: HTMLElement, summary: { added: number; errors: RowError[] } ): void {
		const summaryElement = form.querySelector<HTMLElement>( '[data-cr-import-summary]' );

		if ( ! summaryElement ) {
			return;
		}

		const errorsHtml = summary.errors.length > 0
			? `<ul>${summary.errors.map( ( error ) => `<li>${CartRelayHelpers.escapeHtml( this.formatRowError( error ) )}</li>` ).join( '' )}</ul>`
			: `<p class="cr-import-summary__success">${__( 'All products were added successfully.', 'cart-relay' )}</p>`;
		const productsAdded = sprintf(
			/* translators: %d: number of products added to the cart. */
			_n( 'Product added: %d', 'Products added: %d', summary.added, 'cart-relay' ),
			summary.added
		);
		const issues = sprintf(
			/* translators: %d: number of products with import issues. */
			_n( 'With issue: %d', 'With issues: %d', summary.errors.length, 'cart-relay' ),
			summary.errors.length
		);

		summaryElement.hidden = false;
		summaryElement.innerHTML = `
			<h3>${__( 'Import summary', 'cart-relay' )}</h3>
			<p>${productsAdded}</p>
			<p>${issues}</p>
			${errorsHtml}
		`;
	}

	private clearSummary( form: HTMLElement ): void {
		const summaryElement = form.querySelector<HTMLElement>( '[data-cr-import-summary]' );

		if ( summaryElement ) {
			summaryElement.hidden = true;
			summaryElement.innerHTML = '';
		}
	}

	private setFileMeta( form: HTMLElement, file: File ): void {
		const meta = form.querySelector<HTMLElement>( '[data-cr-import-file-meta]' );
		const emptyState = form.querySelector<HTMLElement>( '[data-cr-import-empty-state]' );
		const dropzone = form.querySelector<HTMLElement>( '[data-cr-import-dropzone]' );
		const icon = form.querySelector<SVGElement>( '.cr-import-dropzone__icon' );
		const name = form.querySelector<HTMLElement>( '[data-cr-import-file-name]' );
		const size = form.querySelector<HTMLElement>( '[data-cr-import-file-size]' );

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
		const meta = form.querySelector<HTMLElement>( '[data-cr-import-file-meta]' );
		const emptyState = form.querySelector<HTMLElement>( '[data-cr-import-empty-state]' );
		const dropzone = form.querySelector<HTMLElement>( '[data-cr-import-dropzone]' );
		const icon = form.querySelector<SVGElement>( '.cr-import-dropzone__icon' );

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

	private chunkItems<T>( items: T[] ): T[][] {
		const chunks: T[][] = [];

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

	private getImportPayloadItems( items: ImportItem[] ): ImportChunkItem[] {
		return items.map( ( item ) => ( {
			row: item.row,
			product_id: item.product_id,
			variation_id: item.variation_id,
			sku: item.sku,
			quantity: item.quantity,
		} ) );
	}

	private formatCurrency( value: number, currency?: PreviewCurrency ): string {
		const amount = this.getNumber( value );
		const decimals = currency?.decimals ?? 2;
		const currencyCode = currency?.code || 'USD';
		const currencySymbol = currency?.symbol || '$';
		const decimalSeparator = currency?.decimal_separator || '.';
		const thousandSeparator = currency?.thousand_separator || ',';
		const fixedAmount = amount.toFixed( decimals );
		const [ integerPart, decimalPart = '' ] = fixedAmount.split( '.' );
		const formattedInteger = integerPart.replace( /\B(?=(\d{3})+(?!\d))/g, thousandSeparator );
		const formattedAmount = decimals > 0
			? `${formattedInteger}${decimalSeparator}${decimalPart}`
			: formattedInteger;

		return `${currencySymbol} ${formattedAmount} ${currencyCode}`;
	}

	private getNumber( value: unknown ): number {
		const numberValue = typeof value === 'number' ? value : Number( value );

		return Number.isFinite( numberValue ) ? numberValue : 0;
	}

	private formatProductVariationId( item: ImportItem ): string {
		if ( item.variation_id > 0 ) {
			return `${item.product_id} / ${item.variation_id}`;
		}

		return `${item.product_id} / -`;
	}

	private renderProductVariationLink( item: ImportItem ): string {
		const label = CartRelayHelpers.escapeHtml( this.formatProductVariationId( item ) );

		if ( ! item.permalink ) {
			return label;
		}

		return `
			<a
				class="cr-import-preview-table__product-link"
				href="${CartRelayHelpers.escapeHtml( item.permalink )}"
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
		return error.row > 0 ? sprintf( __( 'Row %1$d: %2$s', 'cart-relay' ), error.row, error.message ) : error.message;
	}

	private getUploadIconPath(): string {
		return '<path d="M12 3a1 1 0 0 1 .7.29l4 4a1 1 0 1 1-1.4 1.42L13 6.41V16a1 1 0 1 1-2 0V6.41L8.7 8.71a1 1 0 1 1-1.4-1.42l4-4A1 1 0 0 1 12 3Zm-7 13a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1Z" fill="currentColor"/>';
	}

	private getReadyIconPath(): string {
		return '<path d="M9.2 16.6a1 1 0 0 1-.7-.3l-3.2-3.2a1 1 0 1 1 1.4-1.42l2.48 2.47 7.77-7.78a1 1 0 0 1 1.42 1.42l-8.48 8.5a1 1 0 0 1-.69.31Z" fill="currentColor"/>';
	}

	private async refreshCartDisplay( updatedItems: UpdatedCartItem[] ): Promise<void> {
		this.syncVisibleCartQuantities( updatedItems );

		if ( await this.triggerWooCommerceCartUpdate() ) {
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

	private triggerWooCommerceCartUpdate(): Promise<boolean> {
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
