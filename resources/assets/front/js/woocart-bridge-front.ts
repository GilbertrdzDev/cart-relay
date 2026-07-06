import { CartExport } from '@front/cart/CartExport';
import { CartImport } from '@front/cart/CartImport';

const cartExport = new CartExport();
const cartImport = new CartImport();

cartExport.init();
cartImport.init();
