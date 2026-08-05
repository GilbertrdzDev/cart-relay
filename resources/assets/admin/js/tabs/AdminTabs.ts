interface ActivateOptions {
  focus?: boolean;
  updateUrl?: boolean;
}

interface ActivateTabDetail {
  tabId?: string;
}

export class AdminTabs {
  private readonly tabs: HTMLButtonElement[];
  private readonly panels: HTMLElement[];
  private readonly form: HTMLFormElement | null;
  private nativeValidationPending = false;

  public constructor( private readonly root: HTMLElement ) {
    this.tabs = Array.from( root.querySelectorAll<HTMLButtonElement>( '[data-cr-tab]' ) );
    this.panels = Array.from( root.querySelectorAll<HTMLElement>( '[data-cr-tab-panel]' ) );
    this.form = root.closest<HTMLFormElement>( 'form' );
  }

  public init(): void {
    if ( this.tabs.length === 0 ) {
      return;
    }

    this.tabs.forEach( ( tab ) => {
      tab.addEventListener( 'click', () => {
        this.activate( tab.dataset.crTab ?? '', { updateUrl: true } );
      } );
      tab.addEventListener( 'keydown', ( event ) => {
        this.handleKeydown( event, tab );
      } );
    } );

    this.form?.addEventListener( 'cr:activate-tab', ( event ) => {
      if ( ! ( event instanceof CustomEvent ) ) {
        return;
      }

      const detail = event.detail as ActivateTabDetail;
      this.activate( detail.tabId ?? '', { updateUrl: true } );
    } );
    this.form?.addEventListener( 'invalid', ( event ) => {
      this.handleInvalid( event );
    }, true );

    const selected = this.tabs.find( ( tab ) => tab.getAttribute( 'aria-selected' ) === 'true' ) ?? this.tabs[0];
    this.activate( selected.dataset.crTab ?? '', {} );
  }

  private activate( tabId: string, options: ActivateOptions ): void {
    const activeTab = this.tabs.find( ( tab ) => tab.dataset.crTab === tabId );

    if ( ! activeTab ) {
      return;
    }

    this.tabs.forEach( ( tab ) => {
      const isActive = tab === activeTab;
      tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
      tab.tabIndex = isActive ? 0 : -1;
    } );

    this.panels.forEach( ( panel ) => {
      panel.hidden = panel.dataset.crTabPanel !== tabId;
    } );

    this.root.dataset.activeTab = tabId;

    if ( options.updateUrl ) {
      const url = new URL( window.location.href );
      url.searchParams.set( 'tab', tabId );
      window.history.replaceState( window.history.state, '', url );
    }

    if ( options.focus ) {
      activeTab.focus();
      activeTab.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
    }
  }

  private handleKeydown( event: KeyboardEvent, currentTab: HTMLButtonElement ): void {
    const currentIndex = this.tabs.indexOf( currentTab );
    let nextIndex: number | null = null;

    switch ( event.key ) {
      case 'ArrowLeft':
        nextIndex = ( currentIndex - 1 + this.tabs.length ) % this.tabs.length;
        break;
      case 'ArrowRight':
        nextIndex = ( currentIndex + 1 ) % this.tabs.length;
        break;
      case 'Home':
        nextIndex = 0;
        break;
      case 'End':
        nextIndex = this.tabs.length - 1;
        break;
      default:
        return;
    }

    event.preventDefault();
    const nextTab = this.tabs[nextIndex];
    this.activate( nextTab.dataset.crTab ?? '', { focus: true, updateUrl: true } );
  }

  private handleInvalid( event: Event ): void {
    if ( this.nativeValidationPending ) {
      return;
    }

    this.nativeValidationPending = true;
    window.setTimeout( () => {
      this.nativeValidationPending = false;
    } );

    if ( ! ( event.target instanceof HTMLElement ) ) {
      return;
    }

    const panel = event.target.closest<HTMLElement>( '[data-cr-tab-panel]' );

    if ( panel?.dataset.crTabPanel && panel.hidden ) {
      this.activate( panel.dataset.crTabPanel, { updateUrl: true } );
    }
  }
}
