import type { FlashData, SharedProps } from './index';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
        flashDataType: FlashData;
    }
}

declare global {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME?: string;
    }
}
