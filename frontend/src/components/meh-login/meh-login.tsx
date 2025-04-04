import {Component, Prop, h, State} from '@stencil/core';
import {TranslationManager, TOKEN_STORAGE_KEY, isAdmin, makeApiRequest, detectBackendUrl} from '../../utils/utils';

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

  @State() showPasswordField: boolean = false;
  @State() isLoggedIn: boolean = false;
  @State() loading: boolean = false;
  @State() error: string = '';
  @State() password: string = '';


  // Default English translations
  private defaultTranslations = {
    login: 'Login',
    logout: 'Logout',
    password: 'Admin Password',
    submit: 'Submit',
    loginError: 'Login failed:',
    nopass: 'No password provided',
    noadmin: 'There is no admin set up for this site',
    badpass: 'Incorrect password'
  };

  // Translation manager instance
  private translator: TranslationManager<typeof this.defaultTranslations>;

  // Shorthand method for translations
  private _(key: keyof typeof this.defaultTranslations | string): string {
    return this.translator.get(key);
  }

  async componentWillLoad() {
    // Process the backend URL (clean or detect)
    this.backend = detectBackendUrl(this.backend);

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

    // Check if user is already logged in as admin
    this.isLoggedIn = isAdmin();
  }

  private togglePasswordField = () => {
    this.showPasswordField = !this.showPasswordField;
    this.error = '';
  }

  private handlePasswordChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    this.password = input.value;
  }

  private handleLogout = async () => {
    try {
      // Remove the current token
      localStorage.removeItem(TOKEN_STORAGE_KEY);

      // Get a new user token by calling refresh endpoint
      const response = await makeApiRequest<{ token: string }>(
        this.backend,
        this.site,
        'token/refresh',
        {
          method: 'POST',
          body: {},
          includeToken: false
        }
      );

      if (response && response.token) {
        // Store the new user token
        localStorage.setItem(TOKEN_STORAGE_KEY, response.token);
      }
    } catch (error) {
      console.error('Error during logout:', error);
    }

    // Update state
    this.isLoggedIn = false;
    this.password = '';

    // Dispatch refresh event to update comments list after logout
    window.dispatchEvent(new CustomEvent('meh-refresh'));
  }

  private handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!this.password) {
      return;
    }

    this.loading = true;
    this.error = '';

    try {
      const response = await makeApiRequest<{ token: string }>(
        this.backend,
        this.site,
        'token/admin',
        {
          method: 'POST',
          body: { password: this.password },
          includeToken: false
        }
      );

      // Store the token in localStorage
      localStorage.setItem(TOKEN_STORAGE_KEY, response.token);

      // Update state
      this.isLoggedIn = true;
      this.showPasswordField = false;
      this.password = '';

      // Dispatch refresh event to update comments list after login
      window.dispatchEvent(new CustomEvent('meh-refresh'));
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
          required={true}
        />
        <button type="submit" disabled={this.loading}>
          {this._('submit')}
        </button>
        {this.error && <div class="error">{this._('loginError')} {this._(this.error)}</div>}
      </form>
    );
  }


  render() {
    // Check admin status on each render to ensure it's current
    this.isLoggedIn = isAdmin();

    const elements = [];

    // Add external stylesheet if provided
    if (this.externalStyles) {
      elements.push(<link rel="stylesheet" href={this.externalStyles} />);
    }

    // Add the appropriate content based on component state
    if (this.isLoggedIn) {
      elements.push(this.renderLogoutButton());
    } else if (this.showPasswordField) {
      elements.push(this.renderPasswordForm());
    } else {
      elements.push(this.renderLoginButton());
    }

    return elements;
  }
}
