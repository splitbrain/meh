import {Component, Element, h, Prop, State} from '@stencil/core';
import {detectBackendUrl, formatRelativeTime, isAdmin, makeApiRequest, TranslationManager} from '../../utils/utils';

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
   * If not provided, attempts to detect from script tag
   */
  @Prop({mutable: true}) backend: string = '';

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

  /**
   * When set, hides the reply link on comments
   */
  @Prop() noreply: boolean = false;

  /**
   * Comment sort order: 'oldest' (default), 'newest', or 'threaded'
   * Can switched by the end user. User preference is saved in localStorage.
   */
  @Prop({mutable: true}) sort: 'oldest' | 'newest' | 'threaded' = 'oldest';

  @State() comments: any[] = [];
  @State() loading: boolean = true;
  @State() error: string = '';

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    noComments: 'No comments yet. Be the first to comment!',
    loadingComments: 'Loading comments...',
    errorLoading: 'Error loading comments:',
    approve: 'Approve',
    reject: 'Reject',
    delete: 'Delete',
    edit: 'Edit',
    spam: 'Spam',
    reply: 'Reply',
    confirmDelete: 'Are you sure you want to delete this comment?',
    inReplyTo: 'in reply to',
    sortOldest: 'Oldest',
    sortNewest: 'Newest',
    sortThreaded: 'Threaded',
    sortBy: 'Sort by:',
  };

  // Translation manager instance
  private translator: TranslationManager<typeof this.defaultTranslations>;

  // Shorthand method for translations
  private _(key: keyof typeof this.defaultTranslations | string): string {
    return this.translator.get(key);
  }

  async componentWillLoad() {
    // If post prop is not set, use the current page's path
    if (!this.post) {
      this.post = window.location.pathname;
    }

    // Process the backend URL (clean or detect)
    this.backend = detectBackendUrl(this.backend);

    // Check localStorage for sort preference
    try {
      const savedSort = localStorage.getItem('meh-sort');
      if (savedSort && ['oldest', 'newest', 'threaded'].includes(savedSort)) {
        this.sort = savedSort as 'oldest' | 'newest' | 'threaded';
      }
    } catch (e) {
      // Ignore localStorage errors (e.g., in private browsing mode)
      console.warn('Could not access localStorage for sort preference:', e);
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
        'comments',
        {
          body: {post: this.post}
        }
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
    return <div class="error">{this._('errorLoading')} {this._(this.error)}</div>;
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
   * Handle reply click
   */
  private handleReplyClick = (comment: any, e: Event) => {
    e.preventDefault();

    // Dispatch a custom event with the comment data
    window.dispatchEvent(new CustomEvent('meh-reply', {
      detail: {comment}
    }));
  };

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

    const actions = [];

    if (comment.status !== 'approved') {
      actions.push(
        <a href="#" class="admin-action approve" title={this._('approve')} onClick={handleApprove}>
          {this._('approve')}
        </a>
      );
    }
    if (comment.status !== 'pending') {
      actions.push(
        <a href="#" class="admin-action reject" title={this._('reject')} onClick={handleReject}>
          {this._('reject')}
        </a>
      );
    }
    actions.push(
      <a href="#" class="admin-action delete" title={this._('delete')} onClick={handleDelete}>
        {this._('delete')}
      </a>
    );
    if (comment.status !== 'spam') {
      actions.push(
        <a href="#" class="admin-action spam" title={this._('spam')} onClick={handleSpam}>
          {this._('spam')}
        </a>
      );
    }

    return actions;
  }

  /**
   * Scroll to a specific comment by ID
   */
  private scrollToComment = (commentId: number, e?: Event) => {
    if (e) {
      e.preventDefault();
    }

    // Find the comment element by its ID
    const commentElement = this.el.shadowRoot.querySelector(`div[data-comment-id="${commentId}"]`);

    if (commentElement) {
      commentElement.classList.add('highlighted');

      // Scroll the comment into view
      commentElement.scrollIntoView({behavior: 'smooth', block: 'center'});

      // Remove the highlight after a delay
      setTimeout(() => {
        commentElement.classList.remove('highlighted');
      }, 100);
    }
  };

  /**
   * Find a parent comment by ID
   */
  private findParentComment(parentId: number) {
    return this.comments.find(comment => comment.id === parentId);
  }

  private localAvatarUrl(ident: string) {
    return `${this.backend}/avatar/${ident}`;
  }

  /**
   * Render a single comment
   */
  private renderComment(comment: any) {
    return (
      <div class={`comment status-${comment.status}`} data-comment-id={comment.id}>
        <img 
          src={comment.avatar_url} 
          alt="Avatar" 
          class="avatar" 
          onError={(e) => {
            const imgElement = e.target as HTMLImageElement;
            imgElement.src = this.localAvatarUrl(comment.ident);
          }}
        />
        <div class="comment-user">
          <strong class="author">
            {comment.website ? (
              <a href={comment.website} target="_blank" rel="noopener noreferrer">
                {comment.author}
              </a>
            ) : (
              comment.author
            )}
          </strong>
          {comment.parent && (
            <span class="parent-link">
              {this._('inReplyTo')}{' '}
              <a href="#" onClick={(e) => this.scrollToComment(comment.parent, e)}>
                {(() => {
                  const parentComment = this.findParentComment(comment.parent);
                  return parentComment ? parentComment.author : `#${comment.parent}`;
                })()}
              </a>
            </span>
          )}
        </div>
        <time
          class="date"
          dateTime={new Date(comment.created_at).toISOString()}
          title={new Date(comment.created_at).toISOString()}
        >
          {this.formatRelativeTime(comment.created_at)}
        </time>
        <div class="comment-content" innerHTML={comment.html}></div>
        <div class="comment-actions">
          {this.renderAdminActions(comment)}
          {!this.noreply && (
            <a href="#" class="reply-action" onClick={(e) => this.handleReplyClick(comment, e)}>
              {this._('reply')}
            </a>
          )}
        </div>
      </div>
    );
  }

  /**
   * Render the list of comments
   */
  private renderCommentsList() {
    // Create a copy of comments to avoid mutating the original array
    const sortedComments = [...this.comments];

    // Handle different sort modes
    if (this.sort === 'threaded') {
      return this.renderThreadedComments();
    } else {
      // Sort comments based on the sort prop
      if (this.sort === 'newest') {
        sortedComments.reverse();
      }

      return (
        <ul class="comments-list">
          {sortedComments.map(comment => (
            <li key={comment.id}>
              {this.renderComment(comment)}
            </li>
          ))}
        </ul>
      );
    }
  }

  /**
   * Handle sort change
   */
  private handleSortChange = (newSort: 'oldest' | 'newest' | 'threaded', e: Event) => {
    e.preventDefault();
    this.sort = newSort;

    // Save the sort preference to localStorage
    try {
      localStorage.setItem('meh-sort', newSort);
    } catch (e) {
      console.warn('Could not save sort preference to localStorage:', e);
    }
  };

  /**
   * Render sort options
   */
  private renderSortOptions() {
    return (
      <div class="sort-options">
        <span class="sort-label">{this._('sortBy')}</span>
        <a
          href="#"
          class={this.sort === 'oldest' ? 'active' : ''}
          onClick={(e) => this.handleSortChange('oldest', e)}
        >
          {this._('sortOldest')}
        </a>
        <a
          href="#"
          class={this.sort === 'newest' ? 'active' : ''}
          onClick={(e) => this.handleSortChange('newest', e)}
        >
          {this._('sortNewest')}
        </a>
        <a
          href="#"
          class={this.sort === 'threaded' ? 'active' : ''}
          onClick={(e) => this.handleSortChange('threaded', e)}
        >
          {this._('sortThreaded')}
        </a>
      </div>
    );
  }

  /**
   * Render comments in a threaded/nested structure
   */
  private renderThreadedComments() {
    // First, identify top-level comments (those without a parent)
    const topLevelComments = this.comments.filter(comment => !comment.parent);

    // Create a map of child comments for quick lookup
    const childrenMap = new Map<number, any[]>();

    // Group child comments by their parent ID
    this.comments.forEach(comment => {
      if (comment.parent) {
        if (!childrenMap.has(comment.parent)) {
          childrenMap.set(comment.parent, []);
        }
        childrenMap.get(comment.parent).push(comment);
      }
    });

    // Recursive function to render a comment and its children
    const renderCommentThread = (comment: any) => {
      const children = childrenMap.get(comment.id) || [];

      return (
        <li key={comment.id}>
          {this.renderComment(comment)}
          {children.length > 0 && (
            <ul class="comment-replies">
              {children.map(child => renderCommentThread(child))}
            </ul>
          )}
        </li>
      );
    };

    return (
      <ul class="comments-list threaded">
        {topLevelComments.map(comment => renderCommentThread(comment))}
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
      // Add sort options above the comments
      elements.push(this.renderSortOptions());
      elements.push(this.renderCommentsList());
    }

    return elements;
  }
}
