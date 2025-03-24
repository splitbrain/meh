# meh-count

The `meh-count` component displays the number of comments for a specific post on your website. It's a lightweight way to show comment counts without loading the full comments list.

## Basic Integration Example

Here's how to add the comment count to your website:

```html
<!-- Basic usage -->
<meh-count
  backend="https://comments.example.com"
  post="/blog/2023/my-awesome-post"
  site="myblog">
</meh-count>
```

This will display text like "5 comments" or "No comments" depending on the count. Using the `numonly` property will display only the number, e.g. "5".

## Usage in Blog Post Lists

A common use case is to show comment counts in a list of blog posts:

```html
<ul class="post-list">
  <li>
    <a href="/blog/post1">My First Post</a>
    <meh-count backend="https://comments.example.com" post="/blog/post1"></meh-count>
  </li>
  <li>
    <a href="/blog/post2">My Second Post</a>
    <meh-count backend="https://comments.example.com" post="/blog/post2"></meh-count>
  </li>
</ul>
```

<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                            | Type                                                                                                                                  | Default     |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `backend`            | `backend`             | The base URL for where the meh system is hosted If not provided, defaults to same origin                                               | `string`                                                                                                                              | `''`        |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly | `string \| { noComments?: string; oneComment?: string; multipleComments?: string; loadingComments?: string; errorLoading?: string; }` | `''`        |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                   | `string`                                                                                                                              | `'en'`      |
| `numonly`            | `numonly`             | When set to true, only the number will be displayed without any text                                                                   | `boolean`                                                                                                                             | `false`     |
| `post`               | `post`                | The post path to fetch comment count for If not provided, defaults to the current page path                                            | `string`                                                                                                                              | `undefined` |
| `site`               | `site`                | The site identifier to use If not provided, defaults to 'meh'                                                                          | `string`                                                                                                                              | `'meh'`     |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
