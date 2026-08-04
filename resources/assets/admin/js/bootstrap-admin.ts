import Alpine from 'alpinejs';

type SettingsValues = Record<string, boolean | number | string | null>;

const matchesCondition = (
  values: SettingsValues,
  field: string | undefined,
  encodedValue: string | undefined,
): boolean => {
  if ( ! field ) {
    return true;
  }

  let expected: unknown = true;

  try {
    expected = JSON.parse( encodedValue ?? 'true' );
  } catch {
    expected = true;
  }

  return values[field] === expected;
};

Alpine.data( 'crAdminSettings', () => ( {
  values: {} as SettingsValues,

  init(): void {
    const root = ( this as typeof this & { $root: HTMLElement } ).$root;
    const encodedValues = root.dataset.initialValues ?? '{}';

    try {
      this.values = JSON.parse( encodedValues ) as SettingsValues;
    } catch {
      this.values = {};
    }
  },

  isFieldVisible( element: HTMLElement ): boolean {
    return matchesCondition(
      ( this as { values: SettingsValues } ).values,
      element.dataset.visibleField,
      element.dataset.visibleValue,
    );
  },

  isFieldRequired( element: HTMLElement ): boolean {
    return matchesCondition(
      ( this as { values: SettingsValues } ).values,
      element.dataset.requiredField,
      element.dataset.requiredValue,
    );
  },
} ) );

Alpine.start();
