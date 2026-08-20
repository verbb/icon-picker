// Craft/Garnish are provided globally by the Craft control panel.
declare const Craft: {
    t: (category: string, message: string, params?: Record<string, unknown>) => string;
    randomString: (length: number) => string;
    sendActionRequest: (method: string, action: string, options?: { data?: unknown }) => Promise<{ data: any }>;
    IconPicker?: {
        Cache?: { stylesheets: string[]; fonts: string[]; scripts?: string[] };
        mountAll?: (scope?: ParentNode) => void;
        startAutoMountObserver?: () => void;
        __autoMountObserverStarted?: boolean;
        [key: string]: unknown;
    };
    [key: string]: unknown;
};

declare const Garnish: unknown;

interface Window {
    Craft: typeof Craft;
    Garnish: typeof Garnish;
    jQuery?: (sel: string) => any;
}
