const TEXT_DOMAIN = 'woocart-bridge';

interface WpI18n {
	__: ( text: string, domain?: string ) => string;
	_n: ( single: string, plural: string, count: number, domain?: string ) => string;
	sprintf: ( format: string, ...args: Array<number | string> ) => string;
}

declare global {
	interface Window {
		wp?: {
			i18n?: Partial<WpI18n>;
		};
	}
}

const getWpI18n = (): Partial<WpI18n> => window.wp?.i18n ?? {};

const fallbackSprintf = ( format: string, ...args: Array<number | string> ): string => {
	let index = 0;

	return format.replace( /%([0-9]+\$)?[sd]/g, ( match, position: string | undefined ) => {
		const argIndex = position ? Number.parseInt( position, 10 ) - 1 : index++;
		const value = args[argIndex];

		return typeof value === 'undefined' ? match : String( value );
	} );
};

const __ = ( text: string ): string => {
	return getWpI18n().__?.( text, TEXT_DOMAIN ) ?? text;
};

const _n = ( single: string, plural: string, count: number ): string => {
	return getWpI18n()._n?.( single, plural, count, TEXT_DOMAIN ) ?? ( count === 1 ? single : plural );
};

const sprintf = ( format: string, ...args: Array<number | string> ): string => {
	return getWpI18n().sprintf?.( format, ...args ) ?? fallbackSprintf( format, ...args );
};

export { __, _n, sprintf };
