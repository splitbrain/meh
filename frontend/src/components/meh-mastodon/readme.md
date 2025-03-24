# meh-mastodon

The `meh-mastodon` component displays a link to the latest Mastodon post that references the current page. If no Mastodon post is found linking to the page, the component displays nothing.

## Basic Integration Example

Here's how to add the Mastodon link to your website:

```html
<!-- Basic usage -->
<meh-mastodon
  backend="https://comments.example.com"
  post="/blog/2023/my-awesome-post"
  site="myblog">
</meh-mastodon>
```

This will display a "Discuss on Mastodon" link if a Mastodon post referencing your page is found. If no Mastodon post is found, the component will not render anything.

## Usage in Blog Posts

A common use case is to show the Mastodon discussion link at the end of blog posts:

```html
<article>
  <h1>My Awesome Blog Post</h1>
  <div class="content">
    <!-- Blog content here -->
  </div>
  
  <footer>
    <div class="social-links">
      <meh-mastodon backend="https://comments.example.com"></meh-mastodon>
    </div>
  </footer>
</article>
```

<!-- Auto Generated Below -->
