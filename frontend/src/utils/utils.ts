
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
