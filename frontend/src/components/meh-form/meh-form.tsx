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

  @State() author: string = '';
  @State() email: string = '';
  @State() website: string = '';
  @State() text: string = '';
  @State() status: 'idle' | 'submitting' | 'success' | 'error' = 'idle';
  @State() errorMessage: string = '';

  private handleSubmit = async (e: Event) => {
    e.preventDefault();
    
    // Basic validation
    if (!this.author || !this.text) {
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
          author: this.author,
          email: this.email,
          website: this.website,
          text: this.text,
        }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error?.message || 'Failed to submit comment');
      }

      // Reset form on success
      this.author = '';
      this.email = '';
      this.website = '';
      this.text = '';
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

        <form onSubmit={this.handleSubmit}>
          <div>
            <label htmlFor="author">Name *</label>
            <input
              id="author"
              type="text"
              value={this.author}
              onInput={(e) => this.author = (e.target as HTMLInputElement).value}
              required
            />
          </div>

          <div>
            <label htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              value={this.email}
              onInput={(e) => this.email = (e.target as HTMLInputElement).value}
            />
          </div>

          <div>
            <label htmlFor="website">Website</label>
            <input
              id="website"
              type="url"
              value={this.website}
              onInput={(e) => this.website = (e.target as HTMLInputElement).value}
            />
          </div>

          <div>
            <label htmlFor="text">Comment *</label>
            <textarea
              id="text"
              value={this.text}
              onInput={(e) => this.text = (e.target as HTMLTextAreaElement).value}
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
