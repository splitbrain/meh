import {Component, Prop, h, State, Element} from '@stencil/core';
import { TranslationManager, TOKEN_STORAGE_KEY, makeApiRequest, detectBackendUrl } from '../../utils/utils';

@Component({
  tag: 'meh-form',
  styleUrls: [
    '../../global/defaults.css',
    '../../global/form.css',
    'meh-form.css',
  ],
  shadow: true,
})
export class MehForm {
  @Element() el!: HTMLElement;

  /**
   * The post path to associate the comment with
   * If not provided, defaults to the current page path
   */
  @Prop() post: string;

  /**
   * The base URL for where the meh system is hosted
   * If not provided, attempts to detect from script tag
   */
  @Prop({ mutable: true }) backend: string = '';

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

  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() isLoading: boolean = true;
  @State() errorMessage: string = '';
  @State() author: string = '';
  @State() email: string = '';
  @State() website: string = '';
  @State() commentStatus: 'pending' | 'approved' | null = null;
  @State() replyingTo: { comment: any; id: number } | null = null;
  // Reference to the form element
  private formElement?: HTMLFormElement;

  // LocalStorage key
  private readonly STORAGE_KEY = 'meh-form-user-data';

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    nameLabel: 'Your Name',
    namePlaceholder: 'Jane Doe',
    emailLabel: 'Your Email Address',
    emailPlaceholder: 'jane@example.com',
    websiteLabel: 'Your Website',
    websitePlaceholder: 'https://example.com/~jane',
    commentLabel: 'Your Comment',
    replyLabel: 'Your Reply to',
    commentPlaceholder: 'Your text here. You may use Markdown for formatting.',
    submitButton: 'Submit Comment',
    submittingButton: 'Submitting...',
    successMessagePending: 'Thank you for your comment! It has been submitted for review.',
    successMessageApproved: 'Thank you for your comment! It has been published.',
    toosoon: 'You posted really fast. Did you even read the article?',
    toolate: 'Sorry, you took too long to post your comment. Please reload and try again.',
    pending: 'Your previous comment is still pending approval. You will need to wait for it to be approved before posting another.',
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

    // Initialize the TranslationManager with default translations
    this.translator = new TranslationManager(this.defaultTranslations);

    // Load language-specific translations
    if (this.language && this.language !== 'en') {
      await this.translator.loadTranslations(`${this.backend}/meh/i18n/meh-form.${this.language}.json`);
    }

    // Process any custom translations provided as prop
    if (this.customTranslations) {
      this.translator.setTranslations(this.customTranslations);
    }

    // Load saved user data from localStorage
    this.loadUserDataFromStorage();

    // Add event listener for reply events
    window.addEventListener('meh-reply', this.handleReplyEvent);

    // Refresh token before allowing form submission
    await this.refreshToken();
  }

  disconnectedCallback() {
    // Remove event listener when component is destroyed
    window.removeEventListener('meh-reply', this.handleReplyEvent);
  }

  /**
   * Handle the meh-reply custom event
   */
  private handleReplyEvent = (event: CustomEvent) => {
    console.log('Reply event received:', event);
    if (event.detail && event.detail.comment) {
      this.replyingTo = {
        comment: event.detail.comment,
        id: event.detail.comment.id
      };

      // Scroll the form into view
      this.el.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // Focus the comment textarea after a short delay to ensure scrolling is complete
      setTimeout(() => {
        const textarea = this.formElement?.elements['text'] as HTMLTextAreaElement;
        if (textarea) {
          textarea.focus();
        }
      }, 300);
    }
  };

  private loadUserDataFromStorage() {
    try {
      const savedData = localStorage.getItem(this.STORAGE_KEY);
      if (savedData) {
        const userData = JSON.parse(savedData);
        this.author = userData.author || '';
        this.email = userData.email || '';
        this.website = userData.website || '';
      }
    } catch (error) {
      console.error('Failed to load user data from localStorage:', error);
    }
  }

  /**
   * Refresh the authentication token by calling the token/refresh endpoint
   */
  private async refreshToken(): Promise<void> {
    try {
      const response = await makeApiRequest<{ token: string }>(
        this.backend,
        this.site,
        'token/refresh',
        {
          method: 'POST',
          body: {}
        }
      );

      if (response && response.token) {
        // Store the new token
        try {
          localStorage.setItem(TOKEN_STORAGE_KEY, response.token);
        } catch (error) {
          console.error('Failed to save token to localStorage:', error);
        }
      }
    } catch (error) {
      console.error('Error refreshing token:', error);
    } finally {
      // Set loading to false regardless of the result
      this.isLoading = false;
    }
  }

  private saveUserDataToStorage(author: string, email: string, website: string) {
    try {
      const userData = {author, email, website};
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(userData));
    } catch (error) {
      console.error('Failed to save user data to localStorage:', error);
    }
  }

  private handleSubmit = async (e: Event) => {
    e.preventDefault();

    // Get form data and convert to object
    const formData = new FormData(this.formElement);
    const formValues = Object.fromEntries(formData.entries());

    // Set submitting state
    this.status = 'submitting';
    this.errorMessage = '';

    try {
      // Prepare the request body
      const requestBody: any = {
        post: this.post,
        ...formValues
      };

      // Add parent ID if replying to a comment
      if (this.replyingTo) {
        requestBody.parent = this.replyingTo.id;
      }

      // Submit the comment
      const comment = await makeApiRequest(
        this.backend,
        this.site,
        'comment',
        {
          method: 'POST',
          body: requestBody
        }
      );

      // Store the comment status
      this.commentStatus = comment.status;

      // Save user data to localStorage
      this.saveUserDataToStorage(
        formValues.author as string,
        formValues.email as string,
        formValues.website as string
      );

      // Reset form on success
      this.formElement.elements['text'].value = '';
      this.status = 'success';

      // Clear the replying state
      this.replyingTo = null;

      // Reload user data to update the form fields
      this.loadUserDataFromStorage();

      // Dispatch refresh event to update comments list if the comment was approved
      // or if the user is an admin (has token)
      if (this.commentStatus === 'approved') {
        window.dispatchEvent(new CustomEvent('meh-refresh'));
      }
    } catch (error) {
      console.error('Comment submission error:', error);
      this.errorMessage = error.message || 'Unknown error occurred';
      this.status = 'error';
    }
  };

  render() {
    const elements = [];

    // Add external stylesheet if provided
    if (this.externalStyles) {
      elements.push(<link rel="stylesheet" href={this.externalStyles} />);
    }

    elements.push(
      <div class="meh-form-container">
        <form ref={(el) => this.formElement = el as HTMLFormElement} onSubmit={this.handleSubmit}>
          <div class="userdata">
            <label class="required">
              <span>{this._('nameLabel')}</span>
              <input
                name="author"
                type="text"
                placeholder={this._('namePlaceholder')}
                required
                value={this.author}
              />
            </label>

            <label>
              <span>{this._('emailLabel')}</span>
              <input
                name="email"
                type="email"
                placeholder={this._('emailPlaceholder')}
                value={this.email}
              />
            </label>

            <label>
              <span>{this._('websiteLabel')}</span>
              <input
                name="website"
                type="url"
                placeholder={this._('websitePlaceholder')}
                value={this.website}
              />
            </label>
          </div>

          <div>
            <label class="required">
              <span>
                {!this.replyingTo && this._('commentLabel')}
                {this.replyingTo && [this._('replyLabel'), ' ',  this.replyingTo.comment.author]}
              </span>
              <textarea
                name="text"
                required
                rows={5}
                placeholder={this._('commentPlaceholder')}
              ></textarea>
            </label>
          </div>

          {this.status === 'success' && (
            <div class="success">
              {this.commentStatus === 'approved'
                ? this._('successMessageApproved')
                : this._('successMessagePending')}
            </div>
          )}

          {this.status === 'error' && (
            <div class="error">
              {this._(this.errorMessage)}
            </div>
          )}

          <div class="actions">
            <button type="submit" disabled={this.status === 'submitting' || this.isLoading}>
              {this.status === 'submitting' ? this._('submittingButton') : this._('submitButton')}
            </button>

            <slot></slot>
          </div>
        </form>
      </div>
    );

    return elements;
  }
}
