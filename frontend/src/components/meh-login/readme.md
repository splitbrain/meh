# meh-login

The `meh-login` component provides a simple login button for administrators to access moderation features. When clicked, it displays a password form that allows administrators to authenticate.

## Basic Integration Example

Here's how to add the login component to your website:

```html
<!-- Basic usage -->
<meh-login
  backend="https://comments.example.com"
  site="myblog">
</meh-login>
```

## Usage with meh-form

Typically, you'll want to place the login button in the [meh-form](../meh-form/readme.md)'s action area:

```html
<meh-form
  backend="https://comments.example.com"
  post="/blog/2023/my-awesome-post"
  site="myblog">
  
  <!-- The login button will appear next to the submit button -->
  <meh-login
    backend="https://comments.example.com"
    site="myblog">
  </meh-login>
</meh-form>
```

<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                            | Type                                                                                                      | Default |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- | ------- |
| `backend`            | `backend`             | The base URL for where the meh system is hosted If not provided, defaults to same origin                                               | `string`                                                                                                  | `''`    |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly | `string \| { login?: string; logout?: string; password?: string; submit?: string; loginError?: string; }` | `''`    |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                   | `string`                                                                                                  | `'en'`  |
| `site`               | `site`                | The site identifier to use If not provided, defaults to 'meh'                                                                          | `string`                                                                                                  | `'meh'` |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
