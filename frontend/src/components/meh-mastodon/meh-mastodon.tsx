import {Component, Prop, h, State} from '@stencil/core';
import {TranslationManager, getAuthToken} from '../../utils/utils';

@Component({
  tag: 'meh-mastodon',
  shadow: true,
  styleUrls: [
    '../../global/defaults.css',
    'meh-mastodon.css',
  ],
})
export class MehMastodon {
  /**
   * The post path to fetch Mastodon link for
   * If not provided, defaults to the current page path
   */
  @Prop() post: string;

  /**
   * The base URL for where the meh system is hosted
   * If not provided, defaults to same origin
   */
  @Prop() backend: string = '';

  /**
   * The site identifier to use
   * If not provided, defaults to 'meh'
   */
  @Prop() site: string = 'meh';

  /**
   * The language code for translations
   * If not provided, defaults to 'en'
   */
  @Prop() language: string = 'en';

  /**
   * Custom translations object that overrides default and loaded translations
   * This allows users to provide their own translations directly
   */
  @Prop() customTranslations: string | Partial<typeof this.defaultTranslations> = '';

  @State() mastodonUrl: string = '';
  @State() loading: boolean = true;
  @State() error: string = '';

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    discussOnMastodon: 'Discuss on Mastodon',
    loadingMastodon: 'Loading Mastodon link...',
    errorLoading: 'Error loading Mastodon link',
  };

  // Translation manager instance
  private translator: TranslationManager<typeof this.defaultTranslations>;

  // Shorthand method for translations
  private _(key: keyof typeof this.defaultTranslations): string {
    return this.translator.get(key);
  }

  async componentWillLoad() {
    // If post prop is not set, use the current page's path
    if (!this.post) {
      this.post = window.location.pathname;
    }

    // remove trailing slash from backend URL
    if (this.backend.endsWith('/')) {
      this.backend = this.backend.slice(0, -1);
    }

    // Initialize the TranslationManager with default translations
    this.translator = new TranslationManager(this.defaultTranslations);

    // Load language-specific translations
    if (this.language && this.language !== 'en') {
      await this.translator.loadTranslations(`${this.backend}/meh/i18n/meh-mastodon.${this.language}.json`);
    }

    // Process any custom translations provided as prop
    if (this.customTranslations) {
      this.translator.setTranslations(this.customTranslations);
    }

    // Fetch Mastodon URL
    await this.fetchMastodonUrl();
  }

  private async fetchMastodonUrl() {
    this.loading = true;
    this.error = '';

    try {
      // Prepare headers
      const headers: HeadersInit = {
        'Content-Type': 'application/json'
      };

      // Add authorization header if token exists
      const token = getAuthToken();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(`${this.backend}/api/${this.site}/mastodon-url?post=${encodeURIComponent(this.post)}`, {
        headers
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error?.message || `Server error: ${response.status}`);
      }

      const data = await response.json();
      this.mastodonUrl = data.response || '';
    } catch (error) {
      console.error('Error fetching Mastodon URL:', error);
      this.error = error.message || 'Unknown error occurred';
    } finally {
      this.loading = false;
    }
  }

  render() {
    if (this.loading) {
      return <span class="loading">{this._('loadingMastodon')}</span>;
    } else if (this.error) {
      return <span class="error" title={this.error}>{this._('errorLoading')}</span>;
    } else if (this.mastodonUrl) {
      return (
        <a 
          href={this.mastodonUrl} 
          target="_blank" 
          rel="noopener noreferrer" 
          class="mastodon-link"
        >
          {this._('discussOnMastodon')}
        </a>
      );
    } else {
      // If no Mastodon URL is found, render nothing
      return null;
    }
  }
}
