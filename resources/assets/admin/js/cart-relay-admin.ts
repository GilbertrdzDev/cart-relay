import { AdminForm } from '@admin/forms/AdminForm';
import { AdminTabs } from '@admin/tabs/AdminTabs';

const initializeAdmin = (): void => {
  document.querySelectorAll<HTMLElement>( '[data-cr-tabs]' ).forEach( ( tabs ) => {
    new AdminTabs( tabs ).init();
  } );

  document.querySelectorAll<HTMLFormElement>( '[data-cr-admin-form]' ).forEach( ( form ) => {
    new AdminForm( form ).init();
  } );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initializeAdmin, { once: true } );
} else {
	initializeAdmin();
}
