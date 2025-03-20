import {Component, Prop, h, State} from '@stencil/core';
import {TranslationManager} from '../../utils/utils';

@Component({
  tag: 'meh-login',
  shadow: true,
  styleUrls: [
    '../../global/defaults.css',
    '../../global/form.css',
    'meh-login.css',
  ],
})
export class MehLogin {
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

  @State() showPasswordField: boolean = false;
  @State() isLoggedIn: boolean = false;
  @State() loading: boolean = false;
  @State() error: string = '';
  @State() password: string = '';
  @State() token: string = '';

  // Storage key for the auth token
  private readonly TOKEN_STORAGE_KEY = 'meh_admin_token';

  // Default English translations
  private defaultTranslations = {
    login: 'Login',
    logout: 'Logout',
    password: 'Admin Password',
    submit: 'Submit',
    loginError: 'Login failed:',
  };

  // Translation manager instance
  private translator: TranslationManager<typeof this.defaultTranslations>;

  // Shorthand method for translations
  private _(key: keyof typeof this.defaultTranslations): string {
    return this.translator.get(key);
  }

  async componentWillLoad() {
    // remove trailing slash from backend URL
    if (this.backend.endsWith('/')) {
      this.backend = this.backend.slice(0, -1);
    }

    // Initialize the TranslationManager with default translations
    this.translator = new TranslationManager(this.defaultTranslations);

    // Load language-specific translations
    if (this.language && this.language !== 'en') {
      await this.translator.loadTranslations(`${this.backend}/meh/i18n/meh-login.${this.language}.json`);
    }

    // Process any custom translations provided as prop
    if (this.customTranslations) {
      this.translator.setTranslations(this.customTranslations);
    }

    // Load token from localStorage if it exists
    this.loadTokenFromStorage();
  }

  /**
   * Load the authentication token from localStorage
   */
  private loadTokenFromStorage() {
    try {
      const savedToken = localStorage.getItem(this.TOKEN_STORAGE_KEY);
      if (savedToken) {
        this.token = savedToken;
        this.isLoggedIn = true;
      }
    } catch (error) {
      console.error('Failed to load token from localStorage:', error);
    }
  }

  /**
   * Save the authentication token to localStorage
   */
  private saveTokenToStorage(token: string) {
    try {
      localStorage.setItem(this.TOKEN_STORAGE_KEY, token);
    } catch (error) {
      console.error('Failed to save token to localStorage:', error);
    }
  }

  /**
   * Remove the authentication token from localStorage
   */
  private removeTokenFromStorage() {
    try {
      localStorage.removeItem(this.TOKEN_STORAGE_KEY);
    } catch (error) {
      console.error('Failed to remove token from localStorage:', error);
    }
  }

  private togglePasswordField = () => {
    this.showPasswordField = !this.showPasswordField;
    this.error = '';
  }

  private handlePasswordChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    this.password = input.value;
  }

  private handleLogout = () => {
    this.isLoggedIn = false;
    this.password = '';
    this.token = '';
    this.removeTokenFromStorage();
  }

  private handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!this.password) {
      return;
    }

    this.loading = true;
    this.error = '';

    try {
      const response = await fetch(`${this.backend}/api/token/admin`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({password: this.password}),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error?.message || `Server error: ${response.status}`);
      }

      // Successfully logged in
      this.token = data.token;
      this.isLoggedIn = true;
      this.showPasswordField = false;
      this.password = '';

      // Store the token in localStorage
      this.saveTokenToStorage(this.token);
    } catch (error) {
      console.error('Login error:', error);
      this.error = error.message || 'Unknown error occurred';
    } finally {
      this.loading = false;
    }
  }

  private renderLoginButton() {
    return (
      <svg
        viewBox="0 0 24 24"
        onClick={this.togglePasswordField}
        aria-label={this._('login')}
        role="button"
        tabindex={0}
      >
        <title>{this._('login')}</title>
        <path
          d="M11 7L9.6 8.4L12.2 11H2V13H12.2L9.6 15.6L11 17L16 12L11 7M20 19H12V21H20C21.1 21 22 20.1 22 19V5C22 3.9 21.1 3 20 3H12V5H20V19Z"/>
      </svg>
    );
  }

  private renderLogoutButton() {
    return (
      <svg
        viewBox="0 0 24 24"
        onClick={this.handleLogout}
        aria-label={this._('logout')}
        role="button"
        tabindex={0}
      >
        <title>{this._('logout')}</title>
        <path
          d="M17 7L15.59 8.41L18.17 11H8V13H18.17L15.59 15.58L17 17L22 12M4 5H12V3H4C2.9 3 2 3.9 2 5V19C2 20.1 2.9 21 4 21H12V19H4V5Z"/>
      </svg>
    );
  }

  private renderPasswordForm() {
    return (
      <form onSubmit={this.handleSubmit}>
        <input
          id="password"
          type="password"
          value={this.password}
          onInput={this.handlePasswordChange}
          placeholder={this._('password')}
          disabled={this.loading}
        />
        <button type="submit" disabled={this.loading}>
          {this._('submit')}
        </button>
        {this.error && <div class="error">{this._('loginError')} {this.error}</div>}
      </form>
    );
  }


  render() {
    if (this.isLoggedIn) {
      return this.renderLogoutButton();
    } else if (this.showPasswordField) {
      return this.renderPasswordForm();
    } else {
      return this.renderLoginButton();
    }
  }
}
