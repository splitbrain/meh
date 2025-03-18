import { Component, Prop, h, State } from '@stencil/core';

@Component({
  tag: 'meh-form',
  shadow: true,
})
export class MehForm {
  /**
   * The post path to associate the comment with
   */
  @Prop() post: string;

  // Only keep status-related state properties
  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() errorMessage: string = '';

  // Reference to the form element only
  private formElement?: HTMLFormElement;

  private handleSubmit = async (e: Event) => {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this.formElement);
    
    // Extract values from form data
    const author = formData.get('author') as string;
    const email = formData.get('email') as string;
    const website = formData.get('website') as string;
    const text = formData.get('text') as string;
    
    // Basic validation
    if (!author || !text) {
      this.errorMessage = 'Name and comment text are required';
      this.status = 'error';
      return;
    }

    this.status = 'submitting';
    this.errorMessage = '';

    try {
      const response = await fetch('/comment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          post: this.post,
          author,
          email,
          website,
          text,
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
            <label htmlFor="author">Name *</label>
            <input
              id="author"
              name="author"
              type="text"
              required
            />
          </div>

          <div>
            <label htmlFor="email">Email</label>
            <input
              id="email"
              name="email"
              type="email"
            />
          </div>

          <div>
            <label htmlFor="website">Website</label>
            <input
              id="website"
              name="website"
              type="url"
            />
          </div>

          <div>
            <label htmlFor="text">Comment *</label>
            <textarea
              id="text"
              name="text"
              required
              rows={5}
            ></textarea>
          </div>

          <button type="submit" disabled={this.status === 'submitting'}>
            {this.status === 'submitting' ? 'Submitting...' : 'Submit Comment'}
          </button>
        </form>
      </div>
    );
  }
}
