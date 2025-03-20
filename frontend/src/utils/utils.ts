
/**
 * TranslationManager
 *
 * A utility class for managing translations in web components.
 * Provides methods to load translations from URLs, set translations from objects or JSON strings,
 * and retrieve translated strings by key.
 */
export class TranslationManager<T extends Record<string, string>> {
  private translations: Partial<T> = {};
  private defaultTranslations: T;
  private cache: Record<string, Partial<T>> = {};

  /**
   * Create a new TranslationManager
   *
   * @param defaultTranslations - The default translations to use as fallback
   */
  constructor(defaultTranslations: T) {
    this.defaultTranslations = { ...defaultTranslations };
    this.translations = { ...defaultTranslations };
  }

  /**
   * Get a translated string for a given key
   *
   * @param key - The translation key to look up
   * @returns The translated string or the default if not found
   */
  get<K extends keyof T>(key: K): string {
    return (this.translations[key] as string) || this.defaultTranslations[key] || String(key);
  }

  /**
   * Set translations from an object or JSON string
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

      // Cache these custom translations
      this.cache['custom'] = parsedTranslations;

      // Merge with existing translations
      this.mergeTranslations();

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
   * @param languageCode - Optional language code to identify these translations in the cache
   * @returns Promise resolving to true if successful, false if there was an error
   */
  async loadTranslations(url: string, languageCode?: string): Promise<boolean> {
    try {
      const response = await fetch(url);

      if (!response.ok) {
        console.warn(`Translation file at ${url} not found, falling back to defaults`);
        return false;
      }

      const langTranslations = await response.json();

      // Cache for future use if a language code was provided
      if (languageCode) {
        this.cache[languageCode] = langTranslations;
      }

      // Merge translations
      this.mergeTranslations();

      return true;
    } catch (error) {
      console.error(`Error loading translations from ${url}:`, error);
      return false;
    }
  }

  /**
   * Set the active language from the cache
   *
   * @param languageCode - The language code to activate
   * @returns True if the language was found in cache and activated, false otherwise
   */
  setLanguage(languageCode: string): boolean {
    if (!this.cache[languageCode]) {
      return false;
    }

    this.mergeTranslations(languageCode);
    return true;
  }

  /**
   * Reset translations to defaults
   */
  reset(): void {
    this.translations = { ...this.defaultTranslations };
  }

  /**
   * Merge translations from cache based on priority
   *
   * @param primaryLanguage - Optional language code to prioritize in the merge
   */
  private mergeTranslations(primaryLanguage?: string): void {
    // Start with default translations
    const merged = { ...this.defaultTranslations };

    // Apply language-specific translations if available
    if (primaryLanguage && this.cache[primaryLanguage]) {
      Object.assign(merged, this.cache[primaryLanguage]);
    }

    // Apply custom translations if available (highest priority)
    if (this.cache['custom']) {
      Object.assign(merged, this.cache['custom']);
    }

    // Update the translations
    this.translations = merged;
  }
}
