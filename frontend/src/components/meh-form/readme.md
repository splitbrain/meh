# meh-form

A customizable comment form component with internationalization support.

<!-- Auto Generated Below -->


## Properties

| Property            | Attribute            | Description                                                                                    | Type               | Default         |
| ------------------- | -------------------- | ---------------------------------------------------------------------------------------------- | ------------------ | --------------- |
| `api`               | `api`                | The base URL for the API If not provided, defaults to "/api/"                                  | `string`           | `'/api/'`       |
| `post`              | `post`               | The post path to associate the comment with If not provided, defaults to the current page path | `string`           | `undefined`     |
| `language`          | `language`           | The language code for translations (e.g., 'en', 'de', 'fr')                                   | `string`           | `'en'`          |
| `i18nPath`          | `i18n-path`          | Path to translation files                                                                     | `string`           | `'./i18n/'`       |
| `customTranslations` | `custom-translations` | Custom translations object or JSON string that overrides default and loaded translations      | `string \| object` | `''`            |

## Translation System

The component supports three ways to provide translations:

1. **Default English translations** - Used when no other translations are provided
2. **Language-specific translations** - Loaded from JSON files based on the `language` attribute
3. **Custom translations** - Provided directly via the `customTranslations` property

### Translation Object Structure

The translation object can include any of these properties (all are optional):

```typescript
{
  formTitle: string;       // Default: 'Leave a Comment'
  nameLabel: string;       // Default: 'Your Name'
  namePlaceholder: string; // Default: 'Jane Doe'
  emailLabel: string;      // Default: 'Your Email Address'
  emailPlaceholder: string; // Default: 'jane@example.com'
  websiteLabel: string;    // Default: 'Your Website'
  websitePlaceholder: string; // Default: 'https://example.com/~jane'
  commentLabel: string;    // Default: 'Your Comment'
  commentPlaceholder: string; // Default: 'Lorem Ipsum…'
  submitButton: string;    // Default: 'Submit Comment'
  submittingButton: string; // Default: 'Submitting...'
  successMessage: string;  // Default: 'Thank you for your comment! It has been submitted for review.'
  errorPrefix: string;     // Default: 'Error: '
}
```

## Usage Examples

### Basic Usage
```html
<meh-form post="/blog/example-post"></meh-form>
```

### With Language Setting
```html
<meh-form post="/blog/example-post" language="de"></meh-form>
```

### With Custom Translations (JSON string)
```html
<meh-form 
  post="/blog/example-post" 
  custom-translations='{"formTitle":"Share Your Thoughts","submitButton":"Post Comment"}'>
</meh-form>
```

### With Custom Translations (JavaScript object)
```javascript
// In your JavaScript
const myTranslations = {
  formTitle: "Share Your Thoughts",
  submitButton: "Post Comment",
  successMessage: "Your comment has been received!"
};

// Then set it on your element
document.querySelector('meh-form').customTranslations = myTranslations;
```

----------------------------------------------

*Built with [StencilJS](https://stenciljs.com/)*
