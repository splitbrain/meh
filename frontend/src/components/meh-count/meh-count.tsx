import {Component, Prop, h, State} from '@stencil/core';
import {TranslationManager, getAuthToken} from '../../utils/utils';

@Component({
  tag: 'meh-count',
  shadow: true,
  styleUrls: [
    '../../global/defaults.css',
    'meh-count.css',
  ],
})
export class MehCount {
  /**
   * The post path to fetch comment count for
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
   * When set to true, only the number will be displayed without any text
   */
  @Prop() numonly: boolean = false;

  /**
   * URL to an external stylesheet to be injected into the shadow DOM
   */
  @Prop() externalStyles: string = '';

  @State() count: number = 0;
  @State() loading: boolean = true;
  @State() error: string = '';

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    noComments: 'No comments',
    oneComment: '1 comment',
    multipleComments: '{count} comments',
    loadingComments: 'Loading...',
    errorLoading: 'Error loading comment count',
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
      await this.translator.loadTranslations(`${this.backend}/meh/i18n/meh-count.${this.language}.json`);
    }

    // Process any custom translations provided as prop
    if (this.customTranslations) {
      this.translator.setTranslations(this.customTranslations);
    }

    // Fetch comment count
    await this.fetchCommentCount();
  }

  private async fetchCommentCount() {
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

      const response = await fetch(`${this.backend}/api/${this.site}/comments-count?post=${encodeURIComponent(this.post)}`, {
        headers
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error?.message || `Server error: ${response.status}`);
      }

      const data = await response.json();
      this.count = data.response || 0;
    } catch (error) {
      console.error('Error fetching comment count:', error);
      this.error = error.message || 'Unknown error occurred';
    } finally {
      this.loading = false;
    }
  }

  /**
   * Format the comment count with the appropriate translation
   */
  private formatCount(): string {
    if (this.numonly) {
      return this.count.toString();
    } else if (this.count === 0) {
      return this._('noComments');
    } else if (this.count === 1) {
      return this._('oneComment');
    } else {
      return this._('multipleComments').replace('{count}', this.count.toString());
    }
  }


  render() {
    const elements = [];
    
    // Add external stylesheet if provided
    if (this.externalStyles) {
      elements.push(<link rel="stylesheet" href={this.externalStyles} />);
    }
    
    // Add the appropriate content based on component state
    if (this.loading) {
      elements.push(<span class="loading">{this._('loadingComments')}</span>);
    } else if (this.error) {
      elements.push(<span class="error" title={this.error}>{this._('errorLoading')}</span>);
    } else {
      elements.push(<span>{this.formatCount()}</span>);
    }
    
    return elements;
  }
}
