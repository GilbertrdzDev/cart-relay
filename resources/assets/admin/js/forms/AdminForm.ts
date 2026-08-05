interface AjaxData {
  message?: string;
  errors?: Record<string, string>;
  values?: Record<string, unknown>;
}

interface AjaxResponse {
  success: boolean;
  data?: AjaxData;
}

const STATUS_CLASSES = {
  success: [ 'cr:border-green-200', 'cr:bg-green-50', 'cr:text-green-800' ],
  error: [ 'cr:border-red-200', 'cr:bg-red-50', 'cr:text-red-800' ],
} as const;

export class AdminForm {
  public constructor( private readonly form: HTMLFormElement ) {}

  public init(): void {
    this.form.addEventListener( 'submit', ( event ) => {
      void this.handleSubmit( event );
    } );
  }

  private async handleSubmit( event: SubmitEvent ): Promise<void> {
    event.preventDefault();
    this.clearFeedback();
    this.setLoading( true );

    try {
      const response = await fetch( this.form.dataset.ajaxUrl ?? '', {
        method: 'POST',
        credentials: 'same-origin',
        body: new FormData( this.form ),
      } );
      const payload = await this.parseResponse( response );

      if ( ! response.ok || ! payload.success ) {
        this.showErrors( payload.data?.errors ?? {} );
        this.showStatus(
          payload.data?.message ?? 'The settings could not be saved.',
          'error',
        );
        return;
      }

      this.showStatus( payload.data?.message ?? 'Settings saved successfully.', 'success' );
    } catch {
      this.showStatus( 'The request failed. Check your connection and try again.', 'error' );
    } finally {
      this.setLoading( false );
    }
  }

  private async parseResponse( response: Response ): Promise<AjaxResponse> {
    try {
      return await response.json() as AjaxResponse;
    } catch {
      return {
        success: false,
        data: { message: 'The server returned an invalid response.' },
      };
    }
  }

  private clearFeedback(): void {
    this.form.querySelectorAll<HTMLElement>( '[data-cr-field-error]' ).forEach( ( error ) => {
      error.textContent = '';
      error.classList.add( 'cr:hidden' );
    } );

    this.form.querySelectorAll<HTMLElement>( '[data-cr-field]' ).forEach( ( field ) => {
      field.setAttribute( 'aria-invalid', 'false' );
    } );

    const status = this.getStatus();
    status?.classList.add( 'cr:hidden' );
  }

  private showErrors( errors: Record<string, string> ): void {
    const invalidFields: HTMLElement[] = [];

    Object.entries( errors ).forEach( ( [name, message] ) => {
      const error = this.form.querySelector<HTMLElement>( `[data-cr-field-error="${CSS.escape( name )}"]` );
      const field = this.form.querySelector<HTMLElement>( `[data-cr-field="${CSS.escape( name )}"]` );

      if ( error ) {
        error.textContent = message;
        error.classList.remove( 'cr:hidden' );
      }

      if ( field ) {
        field.setAttribute( 'aria-invalid', 'true' );
        invalidFields.push( field );
      }
    } );

    const firstInvalidField = invalidFields[0];
    const panel = firstInvalidField?.closest<HTMLElement>( '[data-cr-tab-panel]' );

    if ( panel?.dataset.crTabPanel ) {
      this.form.dispatchEvent( new CustomEvent( 'cr:activate-tab', {
        detail: { tabId: panel.dataset.crTabPanel },
      } ) );
    }

    firstInvalidField?.focus();
  }

  private showStatus( message: string, state: keyof typeof STATUS_CLASSES ): void {
    const status = this.getStatus();

    if ( ! status ) {
      return;
    }

    status.classList.remove(
      'cr:hidden',
      ...STATUS_CLASSES.success,
      ...STATUS_CLASSES.error,
    );
    status.classList.add( ...STATUS_CLASSES[state] );
    status.textContent = message;
    status.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
  }

  private setLoading( loading: boolean ): void {
    const submit = this.form.querySelector<HTMLButtonElement>( '[data-cr-submit]' );
    const label = this.form.querySelector<HTMLElement>( '[data-cr-submit-label]' );
    const loadingLabel = this.form.querySelector<HTMLElement>( '[data-cr-submit-loading]' );

    this.form.setAttribute( 'aria-busy', loading ? 'true' : 'false' );

    if ( submit ) {
      submit.disabled = loading;
    }

    label?.classList.toggle( 'cr:hidden', loading );
    loadingLabel?.classList.toggle( 'cr:hidden', ! loading );
  }

  private getStatus(): HTMLElement | null {
    return this.form.querySelector<HTMLElement>( '[data-cr-form-status]' );
  }
}
