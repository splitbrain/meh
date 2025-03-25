import {Component, h, Prop, State} from '@stencil/core';
import {TranslationManager, makeApiRequest} from '../../utils/utils';

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

  /**
   * URL to an external stylesheet to be injected into the shadow DOM
   */
  @Prop() externalStyles: string = '';

  @State() mastodonUrl: string = '';
  @State() loading: boolean = true;

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    discussOnMastodon: 'Discuss on Mastodon'
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

    try {
      this.mastodonUrl = await makeApiRequest<string>(
        this.backend,
        this.site,
        `mastodon-url?post=${encodeURIComponent(this.post)}`
      );
    } catch (error) {
      console.error('Error fetching Mastodon URL:', error);
    } finally {
      this.loading = false;
    }
  }

  render() {
    if (!this.mastodonUrl) {
      // If no Mastodon URL is found, render nothing
      return null;
    }
    
    const elements = [];
    
    // Add external stylesheet if provided
    if (this.externalStyles) {
      elements.push(<link rel="stylesheet" href={this.externalStyles} />);
    }
    
    // Add the Mastodon link
    elements.push(
      <a
        href={this.mastodonUrl}
        target="_blank"
        rel="noopener noreferrer"
        class="mastodon-link"
      >{this._('discussOnMastodon')}</a>
    );
    
    return elements;
  }
}
