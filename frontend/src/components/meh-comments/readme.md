# meh-comments



<!-- Auto Generated Below -->


## Properties

| Property             | Attribute             | Description                                                                                                                            | Type                                                                                                                                                                                                                            | Default     |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `backend`            | `backend`             | The base URL for where the meh system is hosted If not provided, defaults to same origin                                               | `string`                                                                                                                                                                                                                        | `''`        |
| `customTranslations` | `custom-translations` | Custom translations object that overrides default and loaded translations This allows users to provide their own translations directly | `string \| { noComments?: string; loadingComments?: string; errorLoading?: string; postedOn?: string; by?: string; approve?: string; reject?: string; delete?: string; edit?: string; spam?: string; confirmDelete?: string; }` | `''`        |
| `language`           | `language`            | The language code for translations If not provided, defaults to 'en'                                                                   | `string`                                                                                                                                                                                                                        | `'en'`      |
| `post`               | `post`                | The post path to fetch comments for If not provided, defaults to the current page path                                                 | `string`                                                                                                                                                                                                                        | `undefined` |
| `site`               | `site`                | The site identifier to use If not provided, defaults to 'meh'                                                                          | `string`                                                                                                                                                                                                                        | `'meh'`     |


----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
