import {Component, Element, h, Prop, State} from '@stencil/core';
import {formatRelativeTime, isAdmin, makeApiRequest, TranslationManager} from '../../utils/utils';

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
    spam: 'Spam',
    confirmDelete: 'Are you sure you want to delete this comment?',
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
      this.comments = await makeApiRequest<any[]>(
        this.backend,
        this.site,
        `comments?post=${encodeURIComponent(this.post)}`
      );
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
   * Update a comment's status
   *
   * @param commentId The ID of the comment to update
   * @param status The new status to set ('approved', 'pending', 'spam', 'deleted')
   * @returns Promise that resolves when the status is updated
   */
  private async updateCommentStatus(commentId: number, status: string): Promise<void> {
    try {
      const updatedComment = await makeApiRequest(
        this.backend,
        this.site,
        `comment/${commentId}/${status}`,
        {method: 'PUT'}
      );

      if (status === 'deleted') {
        // Remove the comment from our state
        this.comments = this.comments.filter(comment => comment.id !== commentId);
      } else {
        // Update the comment in our local state
        this.comments = this.comments.map(comment =>
          comment.id === commentId ? updatedComment : comment
        );
      }
    } catch (error) {
      console.error(`Error updating comment status to ${status}:`, error);
      // Could add error handling UI here
    }
  }

  /**
   * Render admin actions for a comment
   */
  private renderAdminActions(comment: any) {
    if (!isAdmin()) {
      return null;
    }

    // Define handlers for each action
    const handleApprove = (e: Event) => {
      e.preventDefault();
      this.updateCommentStatus(comment.id, 'approved');
    };

    const handleReject = (e: Event) => {
      e.preventDefault();
      this.updateCommentStatus(comment.id, 'pending');
    };

    const handleSpam = (e: Event) => {
      e.preventDefault();
      this.updateCommentStatus(comment.id, 'spam');
    };

    const handleDelete = (e: Event) => {
      e.preventDefault();
      if (confirm(this._('confirmDelete'))) {
        this.updateCommentStatus(comment.id, 'deleted');
      }
    };

    return (
      <div class="admin-actions">
        {comment.status !== 'approved' && (
          <a href="#" class="admin-action approve" title={this._('approve')} onClick={handleApprove}>
            {this._('approve')}
          </a>
        )}
        {comment.status !== 'pending' && (
          <a href="#" class="admin-action reject" title={this._('reject')} onClick={handleReject}>
            {this._('reject')}
          </a>
        )}
        <a href="#" class="admin-action delete" title={this._('delete')} onClick={handleDelete}>
          {this._('delete')}
        </a>
        {comment.status !== 'spam' && (
          <a href="#" class="admin-action spam" title={this._('spam')} onClick={handleSpam}>
            {this._('spam')}
          </a>
        )}
      </div>
    );
  }

  /**
   * Render a single comment
   */
  private renderComment(comment: any) {
    return (
      <li class={`comment status-${comment.status}`} key={comment.id}>
        <img src={comment.avatar_url} alt="Avatar" class="avatar"/>
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
    const elements = [];

    // Add external stylesheet if provided
    if (this.externalStyles) {
      elements.push(<link rel="stylesheet" href={this.externalStyles}/>);
    }

    // Add the appropriate content based on component state
    if (this.loading) {
      elements.push(this.renderLoading());
    } else if (this.error) {
      elements.push(this.renderError());
    } else if (this.comments.length === 0) {
      elements.push(this.renderNoComments());
    } else {
      elements.push(this.renderCommentsList());
    }

    return elements;
  }
}
