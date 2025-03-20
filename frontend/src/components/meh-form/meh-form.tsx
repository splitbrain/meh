import {Component, Prop, h, State, Element} from '@stencil/core';
import { TranslationManager } from '../../utils/utils';

@Component({
  tag: 'meh-form',
  styleUrls: [
    '../../global/defaults.css',
    'meh-form.css',
  ],
  shadow: true,
  assetsDirs: ['i18n']
})
export class MehForm {
  @Element() el!: HTMLElement;

  /**
   * The post path to associate the comment with
   * If not provided, defaults to the current page path
   */
  @Prop() post: string;

  /**
   * The base URL for the API
   * If not provided, defaults to "/api/"
   */
  @Prop() api: string = '/api/';

  /**
   * The language code for translations
   * If not provided, defaults to 'en'
   */
  @Prop() language: string = 'en';

  /**
   * Path to translation files
   * If not provided, defaults to the component's i18n directory
   */
  @Prop() i18nPath: string = './i18n/';

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
    successMessage: 'Thank you for your comment! It has been submitted for review.',
    errorPrefix: 'Error: '
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

    // Initialize the TranslationManager with default translations
    this.translator = new TranslationManager(this.defaultTranslations);

    // Load language-specific translations
    if (this.language && this.language !== 'en') {
      await this.translator.loadTranslations(`${this.i18nPath}${this.language}.json`);
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
      // Ensure API URL ends with a slash if not empty
      const apiBase = this.api.endsWith('/') ? this.api : `${this.api}/`;
      const response = await fetch(`${apiBase}comment`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          post: this.post,
          ...formValues
        }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error?.message || 'Failed to submit comment');
      }

      // Save user data to localStorage
      this.saveUserDataToStorage(
        formValues.author as string,
        formValues.email as string,
        formValues.website as string
      );

      // Reset form on success
      this.formElement.reset();
      this.status = 'success';

      // Reload user data to update the form fields
      this.loadUserDataFromStorage();
    } catch (error) {
      this.errorMessage = error.message || 'An error occurred while submitting your comment';
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
              {this._('successMessage')}
            </div>
          )}

          {this.status === 'error' && (
            <div class="error">
              {this._('errorPrefix')}{this.errorMessage}
            </div>
          )}

          <div>
            <button type="submit" disabled={this.status === 'submitting'}>
              {this.status === 'submitting' ? this._('submittingButton') : this._('submitButton')}
            </button>
          </div>
        </form>
      </div>
    );
  }
}
