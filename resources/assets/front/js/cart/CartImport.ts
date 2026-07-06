import Swal from 'sweetalert2';
import { WoocartBridgeHelpers } from '@helpers/utils/WoocartBridgeHelpers';

declare global {
	interface Window {
		jQuery?: ( target: unknown ) => {
			trigger: ( eventName: string ) => void;
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
	price: string;
	subtotal: string;
	image: string;
}

interface PreviewResponse {
	items: ImportItem[];
	errors: RowError[];
	import_mode: string;
}

interface ChunkResponse {
	chunk_index: number;
	total_chunks: number;
	added: number;
	errors: RowError[];
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
		const removeButton = form.querySelector<HTMLButtonElement>( '[data-wcb-import-remove]' );
		const previewButton = form.querySelector<HTMLButtonElement>( '[data-wcb-import-preview]' );

		dropzone?.addEventListener( 'click', () => input?.click() );
		dropzone?.addEventListener( 'dragover', ( event ) => this.handleDragOver( event, dropzone ) );
		dropzone?.addEventListener( 'dragleave', () => dropzone.classList.remove( 'is-dragging' ) );
		dropzone?.addEventListener( 'drop', ( event ) => this.handleDrop( event, form, input, dropzone ) );
		input?.addEventListener( 'change', () => this.handleFileSelect( form, input.files?.[0] ) );
		removeButton?.addEventListener( 'click', () => this.removeFile( form, input ) );
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
				errors: [ { row: 0, message: 'Selecciona un archivo .csv válido.' } ],
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

		const meta = form.querySelector<HTMLElement>( '[data-wcb-import-file-meta]' );
		meta?.setAttribute( 'hidden', 'hidden' );
		this.clearSummary( form );
	}

	private async previewImport( form: HTMLElement ): Promise<void> {
		const file = this.selectedFiles.get( form );

		if ( ! file ) {
			this.renderSummary( form, {
				added: 0,
				errors: [ { row: 0, message: 'Selecciona un archivo CSV antes de continuar.' } ],
			} );
			return;
		}

		const formData = new FormData();
		formData.append( 'action', this.getDatasetValue( form, 'previewAction' ) );
		formData.append( 'nonce', this.getDatasetValue( form, 'previewNonce' ) );
		formData.append( 'csv_file', file );

		WoocartBridgeHelpers.swalShowLoading( 'Leyendo CSV...' );

		const payload = await this.postFormData<PreviewResponse>( form, formData );

		if ( ! payload ) {
			return;
		}

		void this.openPreviewModal( form, payload );
	}

	private async openPreviewModal( form: HTMLElement, response: PreviewResponse ): Promise<void> {
		const canImport = response.items.length > 0;
		const result = await Swal.fire( {
			title: 'Vista previa de importación',
			html: this.renderPreviewModal( response ),
			width: 'min(960px, 96vw)',
			showCancelButton: true,
			showConfirmButton: canImport,
			confirmButtonText: 'Importar productos',
			cancelButtonText: 'Cancelar',
			customClass: {
				popup: 'wcb-import-preview-modal',
			},
		} );

		if ( result.isConfirmed && canImport ) {
			await this.importChunks( form, response.items );
		}
	}

	private async importChunks( form: HTMLElement, items: ImportItem[] ): Promise<void> {
		const chunks = this.chunkItems( items );
		const errors: RowError[] = [];
		let added = 0;

		WoocartBridgeHelpers.swalShowLoading( 'Importando productos...' );
		this.updateProgress( 0, items.length, added, errors.length );

		for ( let index = 0; index < chunks.length; index++ ) {
			const chunkResponse = await this.importChunk( form, chunks[index], index, chunks.length );

			if ( ! chunkResponse ) {
				return;
			}

			added += chunkResponse.added;
			errors.push( ...chunkResponse.errors );
			this.updateProgress(
				Math.min( ( index + 1 ) * this.chunkSize, items.length ),
				items.length,
				added,
				errors.length
			);
		}

		await Swal.fire( {
			icon: errors.length > 0 ? 'warning' : 'success',
			title: errors.length > 0 ? 'Importación finalizada con errores' : 'Carrito importado',
			timer: 1700,
			showConfirmButton: false,
		} );

		this.renderSummary( form, { added, errors } );
		this.refreshCartFragments();
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
					errors: errors.length > 0 ? errors : [ 'No se pudo procesar la solicitud.' ],
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
		return `
			<div class="wcb-import-preview">
				<div class="wcb-import-preview__counts">
					<span>Productos válidos: <strong>${response.items.length}</strong></span>
					<span>Con error: <strong>${response.errors.length}</strong></span>
				</div>
				<div class="wcb-import-preview-table-wrapper">
					<table class="wcb-import-preview-table">
						<thead>
							<tr>
								<th>Imagen</th>
								<th>SKU</th>
								<th>Producto</th>
								<th>Cantidad</th>
								<th>Precio</th>
								<th>Subtotal</th>
								<th>Estado</th>
							</tr>
						</thead>
						<tbody>
							${response.items.map( ( item ) => this.renderPreviewRow( item ) ).join( '' )}
						</tbody>
					</table>
				</div>
				${this.renderPreviewErrors( response.errors )}
			</div>
		`;
	}

	private renderPreviewRow( item: ImportItem ): string {
		const image = item.image
			? `<img src="${WoocartBridgeHelpers.escapeHtml( item.image )}" alt="">`
			: '<span class="wcb-import-preview-table__empty">-</span>';

		return `
			<tr>
				<td class="wcb-import-preview-table__image">${image}</td>
				<td>${WoocartBridgeHelpers.escapeHtml( item.sku || '-' )}</td>
				<td>${WoocartBridgeHelpers.escapeHtml( item.name )}</td>
				<td>${item.quantity}</td>
				<td>${WoocartBridgeHelpers.escapeHtml( item.price )}</td>
				<td>${WoocartBridgeHelpers.escapeHtml( item.subtotal )}</td>
				<td><span class="wcb-import-status wcb-import-status--ok">Listo</span></td>
			</tr>
		`;
	}

	private renderPreviewErrors( errors: RowError[] ): string {
		if ( errors.length === 0 ) {
			return '';
		}

		return `
			<div class="wcb-import-preview-errors">
				<h3>Errores detectados</h3>
				<ul>
					${errors.map( ( error ) => `<li>${WoocartBridgeHelpers.escapeHtml( this.formatRowError( error ) )}</li>` ).join( '' )}
				</ul>
			</div>
		`;
	}

	private updateProgress( current: number, total: number, added: number, errorCount: number ): void {
		const percent = total > 0 ? Math.round( ( current / total ) * 100 ) : 0;

		Swal.update( {
			title: `Importando productos... ${current} / ${total}`,
			html: `
				<div class="wcb-import-progress">
					<div class="wcb-import-progress__track">
						<div class="wcb-import-progress__bar" style="width: ${percent}%"></div>
					</div>
					<div class="wcb-import-progress__meta">
						<span>Agregados: ${added}</span>
						<span>Con error: ${errorCount}</span>
					</div>
				</div>
			`,
		} );
	}

	private renderSummary( form: HTMLElement, summary: { added: number; errors: RowError[] } ): void {
		const summaryElement = form.querySelector<HTMLElement>( '[data-wcb-import-summary]' );

		if ( ! summaryElement ) {
			return;
		}

		const errorsHtml = summary.errors.length > 0
			? `<ul>${summary.errors.map( ( error ) => `<li>${WoocartBridgeHelpers.escapeHtml( this.formatRowError( error ) )}</li>` ).join( '' )}</ul>`
			: '<p class="wcb-import-summary__success">Todos los productos se agregaron correctamente.</p>';

		summaryElement.hidden = false;
		summaryElement.innerHTML = `
			<h3>Resumen de importación</h3>
			<p>Productos agregados: <strong>${summary.added}</strong></p>
			<p>Con error: <strong>${summary.errors.length}</strong></p>
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
		const name = form.querySelector<HTMLElement>( '[data-wcb-import-file-name]' );
		const size = form.querySelector<HTMLElement>( '[data-wcb-import-file-size]' );

		if ( meta ) {
			meta.hidden = false;
		}

		if ( name ) {
			name.textContent = file.name;
		}

		if ( size ) {
			size.textContent = this.formatFileSize( file.size );
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

	private formatRowError( error: RowError ): string {
		return error.row > 0 ? `Fila ${error.row}: ${error.message}` : error.message;
	}

	private refreshCartFragments(): void {
		if ( typeof window.jQuery === 'function' ) {
			window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
		}
	}

}

export { CartImport };
