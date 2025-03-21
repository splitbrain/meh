import {Component, Prop, h, State, Element} from '@stencil/core';
import {TranslationManager, formatRelativeTime, getAuthToken} from '../../utils/utils';

@Component({
  tag: 'meh-comments',
  shadow: true,
  styleUrls: [
    '../../global/defaults.css',
    'meh-comments.css',
  ],
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
    noComments: 'No comments yet. Be the first to comment!',
    loadingComments: 'Loading comments...',
    errorLoading: 'Error loading comments:',
    postedOn: 'Posted on',
    by: 'by',
    approve: 'Approve',
    reject: 'Reject',
    delete: 'Delete',
    edit: 'Edit',
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

    // Add event listener for refresh events
    window.addEventListener('meh-refresh', this.handleRefreshEvent);
  }

  /**
   * Handle the meh-refresh custom event
   */
  private handleRefreshEvent = async () => {
    await this.fetchComments();
  };

  private async fetchComments() {
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

      const response = await fetch(`${this.backend}/api/comments?post=${encodeURIComponent(this.post)}`, {
        headers
      });

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

  /**
   * Format a date as a relative time string (e.g., "5 days ago")
   * Falls back to absolute date format if there's an error
   */
  private formatRelativeTime(dateString: string): string {
    return formatRelativeTime(dateString, this.language);
  }

  /**
   * Render the loading state
   */
  private renderLoading() {
    return <div class="loading">{this._('loadingComments')}</div>;
  }

  /**
   * Render error message
   */
  private renderError() {
    return <div class="error">{this._('errorLoading')} {this.error}</div>;
  }

  /**
   * Render empty state when no comments are available
   */
  private renderNoComments() {
    return <div class="no-comments">{this._('noComments')}</div>;
  }

  /**
   * Check if the current user is an admin
   */
  private isAdmin(): boolean {
    return !!getAuthToken();
  }

  /**
   * Render admin actions for a comment
   */
  private renderAdminActions(comment: any) {
    if (!this.isAdmin()) {
      return null;
    }

    return (
      <div class="admin-actions">
        <a href="#" class="admin-action approve" title={this._('approve')}>
          {this._('approve')}
        </a>
        <a href="#" class="admin-action reject" title={this._('reject')}>
          {this._('reject')}
        </a>
        <a href="#" class="admin-action delete" title={this._('delete')}>
          {this._('delete')}
        </a>
        <a href="#" class="admin-action edit" title={this._('edit')}>
          {this._('edit')}
        </a>
      </div>
    );
  }

  /**
   * Render a single comment
   */
  private renderComment(comment: any) {
    return (
      <li class={`comment status-${comment.status}`} key={comment.id}>
        <img src={comment.avatar} alt="Avatar" class="avatar"/>
        <strong class="author">
          {comment.website ? (
            <a href={comment.website} target="_blank" rel="noopener noreferrer">
              {comment.author}
            </a>
          ) : (
            comment.author
          )}
        </strong>
        <time
          class="date"
          dateTime={new Date(comment.created_at).toISOString()}
          title={new Date(comment.created_at).toISOString()}
        >
          {this.formatRelativeTime(comment.created_at)}
        </time>
        <div class="comment-content" innerHTML={comment.html}></div>
        {this.renderAdminActions(comment)}
      </li>
    );
  }

  /**
   * Render the list of comments
   */
  private renderCommentsList() {
    return (
      <ul class="comments-list">
        {this.comments.map(comment => this.renderComment(comment))}
      </ul>
    );
  }

  /**
   * Clean up event listeners when component is removed
   */
  disconnectedCallback() {
    window.removeEventListener('meh-refresh', this.handleRefreshEvent);
  }

  /**
   * Main render method
   */
  render() {
    if (this.loading) {
      return this.renderLoading();
    } else if (this.error) {
      return this.renderError();
    } else if (this.comments.length === 0) {
      return this.renderNoComments();
    } else {
      return this.renderCommentsList();
    }
  }
}
