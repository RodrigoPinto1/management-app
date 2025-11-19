import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }
    function applyFallbackVars(theme: 'dark' | 'light') {
        try {
            if (theme === 'dark') {
                document.documentElement.style.setProperty('--background', '#0a0a0a');
                document.documentElement.style.setProperty('--foreground', '#fafafa');
                document.documentElement.style.setProperty('--sidebar-background', '#121212');
                document.documentElement.style.setProperty('--color-white', '#0a0a0a');
                document.documentElement.style.setProperty('--color-card', '#0a0a0a');
                document.documentElement.style.setProperty('--color-popover', '#0a0a0a');
                document.documentElement.style.setProperty('--color-card-foreground', '#fafafa');
                document.documentElement.style.setProperty('--color-popover-foreground', '#fafafa');
            } else {
                document.documentElement.style.setProperty('--background', '#ffffff');
                document.documentElement.style.setProperty('--foreground', '#0a0a0a');
                document.documentElement.style.setProperty('--sidebar-background', '#fafafa');
                document.documentElement.style.setProperty('--color-white', '#ffffff');
                document.documentElement.style.setProperty('--color-card', '#ffffff');
                document.documentElement.style.setProperty('--color-popover', '#ffffff');
                document.documentElement.style.setProperty('--color-card-foreground', '#0a0a0a');
                document.documentElement.style.setProperty('--color-popover-foreground', '#0a0a0a');
            }
        } catch (e) {
            // ignore in non-browser environments
        }
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle(
            'dark',
            systemTheme === 'dark',
        );
        applyFallbackVars(systemTheme === 'dark' ? 'dark' : 'light');
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
        applyFallbackVars(value === 'dark' ? 'dark' : 'light');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');

export function useAppearance() {
    onMounted(() => {
        const savedAppearance = localStorage.getItem(
            'appearance',
        ) as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
