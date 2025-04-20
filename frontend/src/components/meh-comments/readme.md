# meh-comments

The `meh-comments` component displays a list of comments for a specific post on your website. It handles loading, displaying, and moderating comments.

## Basic Integration Example

Here's how to add the comments list to your website:

```html
<!-- Basic usage -->
<meh-comments
  backend="https://comments.example.com"
  post="/blog/2023/my-awesome-post"
  site="myblog">
</meh-comments>
```

## Administration

Typically, the component will only display approved comments. To access moderation features, you can use the [meh-login](../meh-login/readme.md) component somewhere on your site to authenticate as an administrator. Once logged in, the component will display all comments, including pending and spam and provide options to moderate them.

<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                             | Type                                                                                                                                                                                                                                                                                                                  | Default     |
| -------------------- | --------------------- | --------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `backend`            | `backend`             | The base URL for where the meh system is hosted If not provided, attempts to detect from script tag                                     | `string`                                                                                                                                                                                                                                                                                                              | `''`        |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly  | `string \| { noComments?: string; loadingComments?: string; errorLoading?: string; approve?: string; reject?: string; delete?: string; edit?: string; spam?: string; reply?: string; confirmDelete?: string; inReplyTo?: string; sortOldest?: string; sortNewest?: string; sortThreaded?: string; sortBy?: string; }` | `''`        |
| `externalStyles`     | `external-styles`     | URL to an external stylesheet to be injected into the shadow DOM                                                                        | `string`                                                                                                                                                                                                                                                                                                              | `''`        |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                    | `string`                                                                                                                                                                                                                                                                                                              | `'en'`      |
| `noreply`            | `noreply`             | When set, hides the reply link on comments                                                                                              | `boolean`                                                                                                                                                                                                                                                                                                             | `false`     |
| `post`               | `post`                | The post path to fetch comments for If not provided, defaults to the current page path                                                  | `string`                                                                                                                                                                                                                                                                                                              | `undefined` |
| `site`               | `site`                | The site identifier to use If not provided, defaults to 'meh'                                                                           | `string`                                                                                                                                                                                                                                                                                                              | `'meh'`     |
| `sort`               | `sort`                | Comment sort order: 'oldest' (default), 'newest', or 'threaded' Can switched by the end user. User preference is saved in localStorage. | `"newest" \| "oldest" \| "threaded"`                                                                                                                                                                                                                                                                                  | `'oldest'`  |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
