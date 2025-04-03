# Component Setup

Meh integrates into your site using JavaScript web components. This allows you to easily add comments to your website without complex server-side integrations. It works even for static pages. 

Meh provides the following components:

* [meh-form](../frontend/src/components/meh-form/readme.md) - Comment submission form
* [meh-comments](../frontend/src/components/meh-comments/readme.md) - Comment display
* [meh-count](../frontend/src/components/meh-count/readme.md) - Comment count display
* [meh-login](../frontend/src/components/meh-login/readme.md) - Admin login mechanism
* [meh-mastodon](../frontend/src/components/meh-mastodon/readme.md) - Link to Mastodon post


## Loading the Components

To use Meh components on your website, you need to include the JavaScript bundle. Add this to your HTML:

```html
<script type="module" src="https://comments.example.com/meh/meh.esm.js"></script>
```

Here `https://comments.example.com` would be the base URL of your Meh Server installation. It's also the value to use in the `backend` property of the components.

## Basic Usage

Once the scripts are loaded, you can use the components as HTML tags:

```html

<!-- Add a comment form -->
<meh-form>
    <!-- Add a Mastodon link -->
    <meh-mastodon></meh-mastodon>
    <!-- Add an admin login button -->
    <meh-login></meh-login>
</meh-form>
<!-- Display comments -->
<meh-comments></meh-comments>
```

Of course, you can surround the components with any other HTML you like. The components will be inserted into the page at the location of the tags.

## Common Properties

All Meh components share some common properties described below. Check the individual component documentation for more specific properties.

### `backend`

The URL where your Meh backend is hosted. This should be the base URL of your Meh installation.
 
```html
<meh-comments backend="https://comments.example.com"></meh-comments>
```

The components should be able to detect this themselves from the script URL, but you can specify it explicitly if needed.

### `site`

The site identifier for multi-site setups. This corresponds to the site name you configured in your Meh installation.

```html
<meh-comments site="myblog"></meh-comments>
```

If not specified, it defaults to "meh".

### `post`

This specifies which post the comments belong to. It should be a unique identifier for the page, typically the URL path.

```html
<meh-comments post="/blog/2023/my-awesome-post"></meh-comments>
```

If not specified, it defaults to the current page's path. This default should work for most use cases.

### `language`

The language code for translations. Meh supports multiple languages through translation files.

```html
<meh-comments language="de"></meh-comments>
```

If not specified, it defaults to "en" (English).

### `customTranslations`

See the [Translations](translations.md) documentation for more information.

### `externalStyles`

See the [Styling Components](styling.md) documentation for more information.


## Complete Example

Here's a complete example of integrating all components into a blog post:

```html
<!DOCTYPE html>
<html>
<head>
  <title>My Blog Post</title>
  <!-- Load Meh components -->
  <script type="module" src="https://comments.example.com/meh/meh.esm.js"></script>
</head>
<body>
  <article>
    <h1>My Awesome Blog Post</h1>
    <p>This is the content of my blog post...</p>
    
    <h2>Comments</h2>
    
    <!-- Comment form with login button -->
    <meh-form
      backend="https://comments.example.com"
      post="/blog/2023/my-awesome-post"
      site="myblog">

      <!-- Add link to Mastodon post inside the form-->
      <meh-mastodon
          backend="https://comments.example.com"
          post="/blog/2023/my-awesome-post"
          site="myblog">
      </meh-mastodon>
      
      <!-- Add login button inside the form -->
      <meh-login
        backend="https://comments.example.com"
        site="myblog">
      </meh-login>
    </meh-form>
    
    <!-- Comment count -->
    <h2>
      <meh-count backend="https://comments.example.com"
                 post="/blog/2023/my-awesome-post"
                 site="myblog">
      </meh-count>
    </h2>
    
    <!-- Comments list -->
    <meh-comments
      backend="https://comments.example.com"
      post="/blog/2023/my-awesome-post"
      site="myblog">
    </meh-comments>
  </article>
</body>
</html>
```

For more advanced customization options, see the [Styling Components](styling.md) and [Translations](translations.md) documentation.
