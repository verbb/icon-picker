declare const Craft: {
    t: (category: string, message: string, params?: Record<string, unknown>) => string;
    randomString: (length: number) => string;
    sendActionRequest: (method: string, action: string, options?: { data?: unknown }) => Promise<{ data: any }>;
    initUiElements?: (element?: unknown) => void;
    CpScreenSlideout?: unknown;
    __iconPickerInitUiWrapped?: boolean;
    __iconPickerSlideoutLoadHooked?: boolean;
    IconPicker?: {
        Cache?: { stylesheets: string[]; fonts: string[]; scripts?: string[] };
        mountAll?: (scope?: ParentNode) => void;
        startAutoMountObserver?: () => void;
        __autoMountObserverStarted?: boolean;
        [key: string]: unknown;
    };
    [key: string]: unknown;
};

declare const Garnish: {
    on?: (target: unknown, event: string, handler: (...args: unknown[]) => void) => void;
    [key: string]: unknown;
};

interface Window {
    Craft: typeof Craft;
    Garnish: typeof Garnish;
    jQuery?: (sel: string) => any;
}
