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
      <svg 
        xmlns="http://www.w3.org/2000/svg" 
        height="24" 
        viewBox="0 -960 960 960" 
        width="24"
        onClick={this.togglePasswordField} 
        title={this._('login')}
        aria-label={this._('login')}
        role="button"
        tabIndex={0}
      >
        <path d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Z"/>
      </svg>
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
      <svg 
        xmlns="http://www.w3.org/2000/svg" 
        height="24" 
        viewBox="0 -960 960 960" 
        width="24"
        onClick={this.handleLogout}
        title={this._('logout')}
        aria-label={this._('logout')}
        role="button"
        tabIndex={0}
      >
        <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/>
      </svg>
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
