# meh-login



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
