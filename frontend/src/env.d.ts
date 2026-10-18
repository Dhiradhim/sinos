/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly SINOS_API_BASE: string;
  readonly SINOS_API_SECRET: string;
  readonly SINOS_SSO_GATEWAY: string;
  readonly SINOS_SSO_APP_ID: string;
  readonly SESSION_SECRET: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

declare module 'preline';
