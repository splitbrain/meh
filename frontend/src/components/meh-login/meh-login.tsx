import { Component, Prop, h, State } from '@stencil/core';
import { TranslationManager } from '../../utils/utils';

@Component({
  tag: 'meh-login',
  shadow: true,
  styleUrls: [
    '../../global/defaults.css',
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

  // Default English translations
  private defaultTranslations = {
    login: 'Login',
    logout: 'Logout',
    password: 'Password',
    submit: 'Submit',
    loginError: 'Login failed:',
    enterPassword: 'Enter admin password',
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
    // We'll handle token removal later
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
        body: JSON.stringify({ password: this.password }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error?.message || `Server error: ${response.status}`);
      }

      // Successfully logged in
      this.isLoggedIn = true;
      this.showPasswordField = false;
      this.password = '';
      
      // We'll handle token storage later
    } catch (error) {
      console.error('Login error:', error);
      this.error = error.message || 'Unknown error occurred';
    } finally {
      this.loading = false;
    }
  }

  private renderLoginButton() {
    return (
      <button onClick={this.togglePasswordField}>
        {this._('login')}
      </button>
    );
  }

  private renderPasswordForm() {
    return (
      <form onSubmit={this.handleSubmit}>
        <label htmlFor="password">{this._('enterPassword')}</label>
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

  private renderLogoutButton() {
    return (
      <button onClick={this.handleLogout}>
        {this._('logout')}
      </button>
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
