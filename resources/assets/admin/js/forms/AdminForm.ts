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
  success: [ 'wcb:border-green-200', 'wcb:bg-green-50', 'wcb:text-green-800' ],
  error: [ 'wcb:border-red-200', 'wcb:bg-red-50', 'wcb:text-red-800' ],
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
    this.form.querySelectorAll<HTMLElement>( '[data-wcb-field-error]' ).forEach( ( error ) => {
      error.textContent = '';
      error.classList.add( 'wcb:hidden' );
    } );

    this.form.querySelectorAll<HTMLElement>( '[data-wcb-field]' ).forEach( ( field ) => {
      field.setAttribute( 'aria-invalid', 'false' );
    } );

    const status = this.getStatus();
    status?.classList.add( 'wcb:hidden' );
  }

  private showErrors( errors: Record<string, string> ): void {
    const invalidFields: HTMLElement[] = [];

    Object.entries( errors ).forEach( ( [name, message] ) => {
      const error = this.form.querySelector<HTMLElement>( `[data-wcb-field-error="${CSS.escape( name )}"]` );
      const field = this.form.querySelector<HTMLElement>( `[data-wcb-field="${CSS.escape( name )}"]` );

      if ( error ) {
        error.textContent = message;
        error.classList.remove( 'wcb:hidden' );
      }

      if ( field ) {
        field.setAttribute( 'aria-invalid', 'true' );
        invalidFields.push( field );
      }
    } );

    invalidFields[0]?.focus();
  }

  private showStatus( message: string, state: keyof typeof STATUS_CLASSES ): void {
    const status = this.getStatus();

    if ( ! status ) {
      return;
    }

    status.classList.remove(
      'wcb:hidden',
      ...STATUS_CLASSES.success,
      ...STATUS_CLASSES.error,
    );
    status.classList.add( ...STATUS_CLASSES[state] );
    status.textContent = message;
    status.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
  }

  private setLoading( loading: boolean ): void {
    const submit = this.form.querySelector<HTMLButtonElement>( '[data-wcb-submit]' );
    const label = this.form.querySelector<HTMLElement>( '[data-wcb-submit-label]' );
    const loadingLabel = this.form.querySelector<HTMLElement>( '[data-wcb-submit-loading]' );

    this.form.setAttribute( 'aria-busy', loading ? 'true' : 'false' );

    if ( submit ) {
      submit.disabled = loading;
    }

    label?.classList.toggle( 'wcb:hidden', loading );
    loadingLabel?.classList.toggle( 'wcb:hidden', ! loading );
  }

  private getStatus(): HTMLElement | null {
    return this.form.querySelector<HTMLElement>( '[data-wcb-form-status]' );
  }
}
