import { AdminForm } from '@admin/forms/AdminForm';

const initializeAdminForms = (): void => {
  document.querySelectorAll<HTMLFormElement>( '[data-wcb-admin-form]' ).forEach( ( form ) => {
    new AdminForm( form ).init();
  } );
};

if ( document.readyState === 'loading' ) {
  document.addEventListener( 'DOMContentLoaded', initializeAdminForms, { once: true } );
} else {
  initializeAdminForms();
}
