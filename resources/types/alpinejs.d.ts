declare module 'alpinejs' {
  interface AlpineApi {
    data( name: string, callback: () => Record<string, unknown> ): void;
    start(): void;
  }

  const Alpine: AlpineApi;
  export default Alpine;
}
