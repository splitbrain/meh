# meh-form

A customizable comment form component with internationalization support.

<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                            | Type                                                                                                                                                                                                                                                                                                                                       | Default     |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------- |
| `api`                | `api`                 | The base URL for the API If not provided, defaults to "/api/"                                                                          | `string`                                                                                                                                                                                                                                                                                                                                   | `'/api/'`   |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly | `string \| { formTitle?: string; nameLabel?: string; namePlaceholder?: string; emailLabel?: string; emailPlaceholder?: string; websiteLabel?: string; websitePlaceholder?: string; commentLabel?: string; commentPlaceholder?: string; submitButton?: string; submittingButton?: string; successMessage?: string; errorPrefix?: string; }` | `''`        |
| `i18nPath`           | `i-1-8n-path`         | Path to translation files If not provided, defaults to the component's i18n directory                                                  | `string`                                                                                                                                                                                                                                                                                                                                   | `'./meh-form/i18n/'` |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                   | `string`                                                                                                                                                                                                                                                                                                                                   | `'en'`      |
| `post`               | `post`                | The post path to associate the comment with If not provided, defaults to the current page path                                         | `string`                                                                                                                                                                                                                                                                                                                                   | `undefined` |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
