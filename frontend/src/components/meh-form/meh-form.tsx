import {Component, Prop, h, State, Element} from '@stencil/core';
import { TranslationManager, getAuthToken } from '../../utils/utils';

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

  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() errorMessage: string = '';
  @State() author: string = '';
  @State() email: string = '';
  @State() website: string = '';
  @State() commentStatus: 'pending' | 'approved' | null = null;
  // Reference to the form element
  private formElement?: HTMLFormElement;

  // LocalStorage key
  private readonly STORAGE_KEY = 'meh-form-user-data';

  // Default English translations that also define the translation structure
  private defaultTranslations = {
    formTitle: 'Leave a Comment',
    nameLabel: 'Your Name',
    namePlaceholder: 'Jane Doe',
    emailLabel: 'Your Email Address',
    emailPlaceholder: 'jane@example.com',
    websiteLabel: 'Your Website',
    websitePlaceholder: 'https://example.com/~jane',
    commentLabel: 'Your Comment',
    commentPlaceholder: 'Lorem Ipsum…',
    submitButton: 'Submit Comment',
    submittingButton: 'Submitting...',
    successMessagePending: 'Thank you for your comment! It has been submitted for review.',
    successMessageApproved: 'Thank you for your comment! It has been published.',
    errorPrefix: 'An error occurred while submitting your comment:',
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
      await this.translator.loadTranslations(`${this.backend}/meh/i18n/meh-form.${this.language}.json`);
    }

    // Process any custom translations provided as prop
    if (this.customTranslations) {
      this.translator.setTranslations(this.customTranslations);
    }

    // Load saved user data from localStorage
    this.loadUserDataFromStorage();
  }

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
      // Prepare headers
      const headers: HeadersInit = {
        'Content-Type': 'application/json'
      };

      // Add authorization header if token exists
      const token = getAuthToken();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(`${this.backend}/api/${this.site}/comment`, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          post: this.post,
          ...formValues
        }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error?.message || `Server error: ${response.status}`);
      }

      // Get the response data which includes the created comment
      const data = await response.json();
      const comment = data.response;

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
    return (
      <div class="meh-form-container">
        <slot name="styles"></slot>

        <h3>{this._('formTitle')}</h3>

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
              <span>{this._('commentLabel')}</span>
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
              {this._('errorPrefix')} {this.errorMessage}
            </div>
          )}

          <div class="actions">
            <button type="submit" disabled={this.status === 'submitting'}>
              {this.status === 'submitting' ? this._('submittingButton') : this._('submitButton')}
            </button>

            <slot></slot>
          </div>
        </form>
      </div>
    );
  }
}
