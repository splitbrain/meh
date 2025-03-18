import { Component, Prop, h, State } from '@stencil/core';

@Component({
  tag: 'meh-form',
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
   * If not provided, defaults to "/"
   */
  @Prop() api: string = '/';

  // Only keep status-related state properties
  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() errorMessage: string = '';

  // Reference to the form element only
  private formElement?: HTMLFormElement;
  
  componentWillLoad() {
    // If post prop is not set, use the current page's path
    if (!this.post) {
      this.post = window.location.pathname;
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
      const apiBase = this.api.endsWith('/') || this.api === '' ? this.api : `${this.api}/`;
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

      // Reset form on success
      this.formElement.reset();
      this.status = 'success';
    } catch (error) {
      this.errorMessage = error.message || 'An error occurred while submitting your comment';
      this.status = 'error';
    }
  };

  render() {
    return (
      <div>
        <h3>Leave a Comment</h3>
        
        {this.status === 'success' && (
          <div>
            <p>Thank you for your comment! It has been submitted for review.</p>
          </div>
        )}

        {this.status === 'error' && (
          <div>
            <p>Error: {this.errorMessage}</p>
          </div>
        )}

        <form ref={(el) => this.formElement = el as HTMLFormElement} onSubmit={this.handleSubmit}>
          <div>
            <label>
              Name *
              <input
                name="author"
                type="text"
                required
              />
            </label>
          </div>

          <div>
            <label>
              Email
              <input
                name="email"
                type="email"
              />
            </label>
          </div>

          <div>
            <label>
              Website
              <input
                name="website"
                type="url"
              />
            </label>
          </div>

          <div>
            <label>
              Comment *
              <textarea
                name="text"
                required
                rows={5}
              ></textarea>
            </label>
          </div>

          <button type="submit" disabled={this.status === 'submitting'}>
            {this.status === 'submitting' ? 'Submitting...' : 'Submit Comment'}
          </button>
        </form>
      </div>
    );
  }
}
