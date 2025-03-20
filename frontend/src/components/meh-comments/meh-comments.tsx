import { Component, Prop, h, State, Element } from '@stencil/core';
import { TranslationManager, formatRelativeTime } from '../../utils/utils';

@Component({
  tag: 'meh-comments',
  shadow: true,
})
export class MehComments {
  @Element() el!: HTMLElement;

  /**
   * The post path to fetch comments for
   * If not provided, defaults to the current page path
   */
  @Prop() post: string;

  /**
   * The base URL for where the meh system is hosted
   * If not provided, defaults to same origin
   */
  @Prop() backend: string = '';

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

  @State() comments: any[] = [];
  @State() loading: boolean = true;
  @State() error: string = '';

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    commentsTitle: 'Comments',
    noComments: 'No comments yet. Be the first to comment!',
    loadingComments: 'Loading comments...',
    errorLoading: 'Error loading comments:',
    postedOn: 'Posted on',
    by: 'by',
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
      await this.translator.loadTranslations(`${this.backend}/meh/i18n/meh-comments.${this.language}.json`);
    }

    // Process any custom translations provided as prop
    if (this.customTranslations) {
      this.translator.setTranslations(this.customTranslations);
    }

    // Fetch comments
    await this.fetchComments();
  }

  private async fetchComments() {
    this.loading = true;
    this.error = '';

    try {
      const response = await fetch(`${this.backend}/api/comments?post=${encodeURIComponent(this.post)}`);

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error?.message || `Server error: ${response.status}`);
      }

      const data = await response.json();
      this.comments = data.response || [];
    } catch (error) {
      console.error('Error fetching comments:', error);
      this.error = error.message || 'Unknown error occurred';
    } finally {
      this.loading = false;
    }
  }

  private formatDate(dateString: string): string {
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString(this.language, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (e) {
      return dateString;
    }
  }

  /**
   * Format a date as a relative time string (e.g., "5 days ago")
   * Falls back to absolute date format if there's an error
   */
  private formatRelativeTime(dateString: string): string {
    return formatRelativeTime(dateString, this.language, (date) => this.formatDate(date));
  }

  render() {
    return (
      <div class="meh-comments-container">
        <h3>{this._('commentsTitle')}</h3>

        {this.loading && (
          <div class="loading">{this._('loadingComments')}</div>
        )}

        {!this.loading && this.error && (
          <div class="error">{this._('errorLoading')} {this.error}</div>
        )}

        {!this.loading && !this.error && this.comments.length === 0 && (
          <div class="no-comments">{this._('noComments')}</div>
        )}

        {!this.loading && !this.error && this.comments.length > 0 && (
          <ul class="comments-list">
            {this.comments.map(comment => (
              <li class="comment" key={comment.id}>
                <div class="comment-header">
                  <img src={comment.avatar} alt="Avatar" class="avatar" />
                  <strong class="author">
                    {comment.website ? (
                      <a href={comment.website} target="_blank" rel="noopener noreferrer">
                        {comment.author}
                      </a>
                    ) : (
                      comment.author
                    )}
                  </strong>
                  <span class="date" title={this.formatDate(comment.created_at)}>
                    {this.formatRelativeTime(comment.created_at)}
                  </span>
                </div>
                <div class="comment-content" innerHTML={comment.html}></div>
              </li>
            ))}
          </ul>
        )}
      </div>
    );
  }
}
