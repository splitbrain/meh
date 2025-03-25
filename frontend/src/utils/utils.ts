import {jwtDecode, JwtPayload} from "jwt-decode"

// Define a custom interface that extends JwtPayload to include scopes
interface MehJwtPayload extends JwtPayload {
  scopes?: string[];
}

/**
 * Options for the API request
 */
export interface ApiRequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: any;
  includeToken?: boolean;
}

/**
 * Make an API request to the meh backend
 *
 * @param backend - The base URL for the backend
 * @param site - The site identifier
 * @param endpoint - The API endpoint to call
 * @param options - Request options (method, body, etc.)
 * @returns Promise resolving to the parsed response data
 * @throws Error with message from the API if the request fails
 */
export async function makeApiRequest<T = any>(
  backend: string,
  site: string,
  endpoint: string,
  options: ApiRequestOptions = {}
): Promise<T> {
  const {
    method = 'GET',
    body = undefined,
    includeToken = true
  } = options;

  // Prepare headers
  const headers: HeadersInit = {
    'Content-Type': 'application/json'
  };

  // Add authorization header if token exists and includeToken is true
  if (includeToken) {
    const token = getAuthToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
  }

  // Prepare request options
  const requestOptions: RequestInit = {
    method,
    headers
  };

  // Build the URL
  let url = `${backend}/api/${site}/${endpoint}`;
  
  // For GET requests, convert body to query parameters
  if (method === 'GET' && body !== undefined) {
    const params = new URLSearchParams();
    Object.entries(body).forEach(([key, value]) => {
      params.append(key, String(value));
    });
    url += `?${params.toString()}`;
  } 
  // For non-GET requests, add body as JSON
  else if (method !== 'GET' && body !== undefined) {
    requestOptions.body = JSON.stringify(body);
  }

  // Make the request
  const response = await fetch(url, requestOptions);

  // Parse the response
  const data = await response.json();

  // Handle error responses
  if (!response.ok) {
    throw new Error(data.error?.message || `Server error: ${response.status}`);
  }

  // Return the response data
  return data.response;
}

/**
 * Storage key for the auth token
 */
export const TOKEN_STORAGE_KEY = 'meh-token';

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
 * Check if the current user has admin privileges
 *
 * @returns True if the user has admin scope, false otherwise
 */
export function isAdmin(): boolean {
  try {
    const token = getAuthToken();
    if (!token) return false;

    const decoded = jwtDecode<MehJwtPayload>(token);

    // Check if the token has the admin scope
    return decoded &&
      decoded.scopes &&
      Array.isArray(decoded.scopes) &&
      decoded.scopes.includes('admin');
  } catch (error) {
    console.error('Failed to verify admin status:', error);
    return false;
  }
}

/**
 * Format a date as a relative time string (e.g., "5 days ago", "3 minutes ago")
 *
 * @param dateString - The date string to format
 * @param language - The language code for localization (e.g., 'en', 'de')
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
    const units: { unit: Intl.RelativeTimeFormatUnit, seconds: number }[] = [
      {unit: 'year', seconds: 31536000},
      {unit: 'month', seconds: 2592000},
      {unit: 'day', seconds: 86400},
      {unit: 'hour', seconds: 3600},
      {unit: 'minute', seconds: 60},
      {unit: 'second', seconds: 1}
    ];

    // Find the appropriate unit
    for (const {unit, seconds} of units) {
      const value = Math.floor(diffInSeconds / seconds);
      if (value >= 1) {
        // Use Intl.RelativeTimeFormat for localized relative time
        const rtf = new Intl.RelativeTimeFormat(language, {numeric: 'auto'});
        return rtf.format(-value, unit);
      }
    }

    // If we get here, it's just now
    const rtf = new Intl.RelativeTimeFormat(language, {numeric: 'auto'});
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
    this.translations = {...defaultTranslations};
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
   * Process a template string with a key in curly braces
   * If the key exists in translations, returns the translation
   * Otherwise returns the text after the key
   * 
   * @param template - Template string in format "{key} fallback text"
   * @returns The translated string if key exists, or fallback text
   */
  processTemplate(template: string): string {
    // Match pattern {key} followed by optional text
    const match = template.match(/^\{([^}]+)\}(.*)$/);
    
    if (!match) {
      return template; // Not a template format, return as is
    }
    
    const [, key, fallbackText] = match;
    
    // Check if key exists in translations
    if (key in this.translations) {
      return this.translations[key as keyof T] as string;
    }
    
    // Return fallback text (trimming leading space if present)
    return fallbackText.trim();
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
