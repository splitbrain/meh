# meh-form

The `meh-form` component provides the comment submission form for your website. It allows visitors to leave comments on your content with support for name, email, website, and comment text fields.

## Basic Integration Example

Here's how to add the comment form to your website:

```html
<!-- Basic usage -->
<meh-form
  backend="https://comments.example.com"
  post="/blog/2023/my-awesome-post"
  site="myblog">
</meh-form>
```

Anything the component wraps around will be displayed as a child of the form. This is useful for adding the [admin login button](../meh-login/readme.md) or other actions.

<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                            | Type                                                                                                                                                                                                                                                                                                                                                                                           | Default     |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `backend`            | `backend`             | The base URL for where the meh system is hosted If not provided, attempts to detect from script tag                                    | `string`                                                                                                                                                                                                                                                                                                                                                                                       | `''`        |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly | `string \| { nameLabel?: string; namePlaceholder?: string; emailLabel?: string; emailPlaceholder?: string; websiteLabel?: string; websitePlaceholder?: string; commentLabel?: string; commentPlaceholder?: string; submitButton?: string; submittingButton?: string; successMessagePending?: string; successMessageApproved?: string; toosoon?: string; toolate?: string; pending?: string; }` | `''`        |
| `externalStyles`     | `external-styles`     | URL to an external stylesheet to be injected into the shadow DOM                                                                       | `string`                                                                                                                                                                                                                                                                                                                                                                                       | `''`        |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                   | `string`                                                                                                                                                                                                                                                                                                                                                                                       | `'en'`      |
| `post`               | `post`                | The post path to associate the comment with If not provided, defaults to the current page path                                         | `string`                                                                                                                                                                                                                                                                                                                                                                                       | `undefined` |
| `site`               | `site`                | The site identifier to use If not provided, defaults to 'meh'                                                                          | `string`                                                                                                                                                                                                                                                                                                                                                                                       | `'meh'`     |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
