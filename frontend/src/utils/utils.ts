
/**
 * Storage key for the auth token
 */
export const TOKEN_STORAGE_KEY = 'meh_admin_token';

/**
 * Get the authentication token from localStorage if available
 * 
 * @returns The token string or null if not found
 */
export function getAuthToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_STORAGE_KEY);
  } catch (error) {
    console.error('Failed to get token from localStorage:', error);
    return null;
  }
}

/**
 * Format a date as a relative time string (e.g., "5 days ago", "3 minutes ago")
 *
 * @param dateString - The date string to format
 * @param language - The language code for localization (e.g., 'en', 'de')
 * @param fallbackFormatter - Optional function to use if formatting fails
 * @returns A localized relative time string
 */
export function formatRelativeTime(
  dateString: string,
  language: string = 'en',
): string {
  try {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    // Define time units and their values in seconds
    const units: {unit: Intl.RelativeTimeFormatUnit, seconds: number}[] = [
      { unit: 'year', seconds: 31536000 },
      { unit: 'month', seconds: 2592000 },
      { unit: 'day', seconds: 86400 },
      { unit: 'hour', seconds: 3600 },
      { unit: 'minute', seconds: 60 },
      { unit: 'second', seconds: 1 }
    ];

    // Find the appropriate unit
    for (const {unit, seconds} of units) {
      const value = Math.floor(diffInSeconds / seconds);
      if (value >= 1) {
        // Use Intl.RelativeTimeFormat for localized relative time
        const rtf = new Intl.RelativeTimeFormat(language, { numeric: 'auto' });
        return rtf.format(-value, unit);
      }
    }

    // If we get here, it's just now
    const rtf = new Intl.RelativeTimeFormat(language, { numeric: 'auto' });
    return rtf.format(0, 'second');
  } catch (e) {
    console.error('Error formatting relative time:', e);
    // Simple fallback
    return dateString;
  }
}

/**
 * TranslationManager
 *
 * A simplified utility class for managing translations in web components.
 * Provides methods to load translations from URLs, set translations from objects or JSON strings,
 * and retrieve translated strings by key.
 */
export class TranslationManager<T extends Record<string, string>> {
  private translations: T;

  /**
   * Create a new TranslationManager
   *
   * @param defaultTranslations - The default translations to use as initial values
   */
  constructor(defaultTranslations: T) {
    this.translations = { ...defaultTranslations };
  }

  /**
   * Get a translated string for a given key
   *
   * @param key - The translation key to look up
   * @returns The translated string or the key itself if not found
   */
  get<K extends keyof T>(key: K): string {
    return (this.translations[key] as string) || String(key);
  }

  /**
   * Set translations from an object or JSON string
   * Merges with existing translations
   *
   * @param translations - Object or JSON string containing translations
   * @returns True if successful, false if there was an error
   */
  setTranslations(translations: string | Partial<T>): boolean {
    try {
      let parsedTranslations: Partial<T>;

      if (typeof translations === 'string') {
        if (!translations.trim()) {
          return false;
        }
        parsedTranslations = JSON.parse(translations) as Partial<T>;
      } else if (typeof translations === 'object') {
        parsedTranslations = translations;
      } else {
        return false;
      }

      // Merge with existing translations
      this.translations = {
        ...this.translations,
        ...parsedTranslations
      };

      return true;
    } catch (error) {
      console.error('Failed to parse translations:', error);
      return false;
    }
  }

  /**
   * Load translations from a URL
   *
   * @param url - The URL to load translations from
   * @returns Promise resolving to true if successful, false if there was an error
   */
  async loadTranslations(url: string): Promise<boolean> {
    try {
      const response = await fetch(url);

      if (!response.ok) {
        console.warn(`Translation file at ${url} not found, falling back to current translations`);
        return false;
      }

      const loadedTranslations = await response.json();

      // Use setTranslations to merge with existing translations
      return this.setTranslations(loadedTranslations);
    } catch (error) {
      console.error(`Error loading translations from ${url}:`, error);
      return false;
    }
  }
}
