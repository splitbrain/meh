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

You can also add this inside the [meh-form](../meh-form/readme.md) component to show the Mastodon link right in the comment form.

<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                            | Type                                        | Default     |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------- | ----------- |
| `backend`            | `backend`             | The base URL for where the meh system is hosted If not provided, attempts to detect from script tag                                    | `string`                                    | `''`        |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly | `string \| { discussOnMastodon?: string; }` | `''`        |
| `externalStyles`     | `external-styles`     | URL to an external stylesheet to be injected into the shadow DOM                                                                       | `string`                                    | `''`        |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                   | `string`                                    | `'en'`      |
| `post`               | `post`                | The post path to fetch Mastodon link for If not provided, defaults to the current page path                                            | `string`                                    | `undefined` |
| `site`               | `site`                | The site identifier to use If not provided, defaults to 'meh'                                                                          | `string`                                    | `'meh'`     |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
