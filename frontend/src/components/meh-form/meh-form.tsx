import {Component, Prop, h, State, Watch, Element} from '@stencil/core';

// Define the translation interface within the component file
export interface MehFormTranslations {
  formTitle: string;
  nameLabel: string;
  namePlaceholder: string;
  emailLabel: string;
  emailPlaceholder: string;
  websiteLabel: string;
  websitePlaceholder: string;
  commentLabel: string;
  commentPlaceholder: string;
  submitButton: string;
  submittingButton: string;
  successMessage: string;
  errorPrefix: string;
}

@Component({
  tag: 'meh-form',
  styleUrls: [
    '../../global/defaults.css',
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
   * If not provided, defaults to './assets/i18n/'
   */
  @Prop() i18nPath: string = './assets/i18n/';

  /**
   * Custom translations object that overrides default and loaded translations
   * This allows users to provide their own translations directly
   */
  @Prop() customTranslations: string | object = '';

  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() errorMessage: string = '';
  @State() author: string = '';
  @State() email: string = '';
  @State() website: string = '';
  @State() translations: MehFormTranslations;

  // Reference to the form element only
  private formElement?: HTMLFormElement;

  // LocalStorage key
  private readonly STORAGE_KEY = 'meh-form-user-data';
  
  // Default English translations
  private defaultTranslations: MehFormTranslations = {
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
  
  // Cache for loaded translations
  private translationCache: Record<string, MehFormTranslations> = {};
  
  // Watch for language changes
  @Watch('language')
  async languageChangedHandler() {
    await this.loadTranslations();
  }
  
  // Watch for custom translations changes
  @Watch('customTranslations')
  customTranslationsChangedHandler() {
    this.processCustomTranslations();
    this.mergeTranslations();
  }

  async componentWillLoad() {
    // If post prop is not set, use the current page's path
    if (!this.post) {
      this.post = window.location.pathname;
    }
    
    // Initialize translations with defaults
    this.translations = { ...this.defaultTranslations };
    
    // Process any custom translations provided as prop
    this.processCustomTranslations();
    
    // Load language-specific translations
    await this.loadTranslations();
    
    // Load saved user data from localStorage
    this.loadUserDataFromStorage();
  }
  
  private processCustomTranslations() {
    if (!this.customTranslations) return;
    
    try {
      // If customTranslations is a string, try to parse it as JSON
      if (typeof this.customTranslations === 'string') {
        if (this.customTranslations.trim()) {
          this.translationCache['custom'] = JSON.parse(this.customTranslations as string);
        }
      } 
      // If it's already an object, use it directly
      else if (typeof this.customTranslations === 'object') {
        this.translationCache['custom'] = this.customTranslations as MehFormTranslations;
      }
    } catch (error) {
      console.error('Failed to parse custom translations:', error);
    }
  }
  
  private async loadTranslations() {
    // For English or if we already have custom translations, just merge what we have
    if (this.language === 'en' || !this.language) {
      this.mergeTranslations();
      return;
    }
    
    // If we've already loaded this language, use the cached version
    if (this.translationCache[this.language]) {
      this.mergeTranslations();
      return;
    }
    
    try {
      // Try to fetch the translation file
      const response = await fetch(`${this.i18nPath}${this.language}.json`);
      
      if (!response.ok) {
        console.warn(`Translation file for ${this.language} not found, falling back to defaults`);
        this.mergeTranslations();
        return;
      }
      
      const langTranslations = await response.json();
      
      // Cache for future use
      this.translationCache[this.language] = langTranslations;
      
      // Merge translations
      this.mergeTranslations();
    } catch (error) {
      console.error(`Error loading translations for ${this.language}:`, error);
      this.mergeTranslations();
    }
  }
  
  private mergeTranslations() {
    // Start with default translations
    const merged = { ...this.defaultTranslations };
    
    // Apply language-specific translations if available
    if (this.language && this.translationCache[this.language]) {
      Object.assign(merged, this.translationCache[this.language]);
    }
    
    // Apply custom translations if available (highest priority)
    if (this.translationCache['custom']) {
      Object.assign(merged, this.translationCache['custom']);
    }
    
    // Update the component state
    this.translations = merged;
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
    const t = this.translations;
    
    return (
      <div class="meh-form-container">
        <slot name="styles"></slot>
        
        <h3>{t.formTitle}</h3>
        
        <form ref={(el) => this.formElement = el as HTMLFormElement} onSubmit={this.handleSubmit}>
          <div class="userdata">
            <label class="required">
              <span>{t.nameLabel}</span>
              <input
                name="author"
                type="text"
                placeholder={t.namePlaceholder}
                required
                value={this.author}
              />
            </label>

            <label>
              <span>{t.emailLabel}</span>
              <input
                name="email"
                type="email"
                placeholder={t.emailPlaceholder}
                value={this.email}
              />
            </label>

            <label>
              <span>{t.websiteLabel}</span>
              <input
                name="website"
                type="url"
                placeholder={t.websitePlaceholder}
                value={this.website}
              />
            </label>
          </div>

          <div>
            <label class="required">
              <span>{t.commentLabel}</span>
              <textarea 
                name="text" 
                required 
                rows={5} 
                placeholder={t.commentPlaceholder}
              ></textarea>
            </label>
          </div>

          {this.status === 'success' && (
            <div class="success">
              {t.successMessage}
            </div>
          )}

          {this.status === 'error' && (
            <div class="error">
              {t.errorPrefix}{this.errorMessage}
            </div>
          )}

          <div>
            <button type="submit" disabled={this.status === 'submitting'}>
              {this.status === 'submitting' ? t.submittingButton : t.submitButton}
            </button>
          </div>
        </form>
      </div>
    );
  }
}
