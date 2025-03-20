import {Component, Prop, h, State} from '@stencil/core';

@Component({
  tag: 'meh-form',
  styleUrls: [
    '../../global/defaults.css',
    'meh-form.css',
  ],
  shadow: true,
})
export class MehForm {
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

  // Only keep status-related state properties
  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() errorMessage: string = '';

  // User data state
  @State() author: string = '';
  @State() email: string = '';
  @State() website: string = '';

  // Reference to the form element only
  private formElement?: HTMLFormElement;

  // LocalStorage key
  private readonly STORAGE_KEY = 'meh-form-user-data';

  componentWillLoad() {
    // If post prop is not set, use the current page's path
    if (!this.post) {
      this.post = window.location.pathname;
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
      [
        <slot name="styles"></slot>
        ,
        <form ref={(el) => this.formElement = el as HTMLFormElement} onSubmit={this.handleSubmit}>


          <div class="userdata">
            <label class="required">
              <span>Your Name</span>
              <input
                name="author"
                type="text"
                placeholder="Jane Doe"
                required
                value={this.author}
              />
            </label>

            <label>
              <span>Your Email Address</span>
              <input
                name="email"
                type="email"
                placeholder="jane@example.com"
                value={this.email}
              />
            </label>

            <label>
              <span>Your Website</span>
              <input
                name="website"
                type="url"
                placeholder="https://example.com/~jane"
                value={this.website}
              />
            </label>
          </div>

          <div>
            <label class="required">
              <span>Your Comment</span>
              <textarea name="text" required rows={5} placeholder="Lorem Ipsum…"></textarea>
            </label>
          </div>

          {this.status === 'success' && (
            <div class="success">
              Thank you for your comment! It has been submitted for review.
            </div>
          )}

          {this.status === 'error' && (
            <div class="error">
              Error: {this.errorMessage}
            </div>
          )}

          <div>
            <button type="submit" disabled={this.status === 'submitting'}>
              {this.status === 'submitting' ? 'Submitting...' : 'Submit Comment'}
            </button>
          </div>
        </form>
      ]
    );
  }
}
