# Customizing Translations

Meh components support multiple languages and allow you to customize any text displayed to users. This makes it easy to adapt the components to your site's language or terminology.

## Built-in Translations

Meh comes with translations for several languages out of the box. You can activate these by setting the `language` attribute:

```html
<!-- Use German translations -->
<meh-comments 
  backend="https://comments.example.com"
  language="de">
</meh-comments>
```

Currently supported languages include:
- English (`en`) - default
- German (`de`)

## Custom Translations

You can override specific translation strings by providing a JSON object through the `custom-translations` attribute:

```html
<meh-form
  backend="https://comments.example.com"
  custom-translations='{"formTitle": "Join the discussion", "submitButton": "Post Comment"}'>
</meh-form>
```

You only need to include the strings you want to override - any strings not specified will use the default translations for the selected language.

## Combining Language and Custom Translations

You can use both the `language` and `custom-translations` attributes together. In this case, Meh will:

1. Load the default English translations
2. Override them with the specified language translations
3. Finally override those with your custom translations

```html
<meh-comments
  backend="https://comments.example.com"
  language="de"
  custom-translations='{"noComments": "Sei der Erste, der etwas sagt!"}'>
</meh-comments>
```

## Available Translation Strings

Each component has its own set of translation strings:

### meh-comments

- `noComments`: Shown when there are no comments
- `loadingComments`: Shown while comments are loading
- `errorLoading`: Prefix for error messages
- `postedOn`: Date label
- `by`: Author attribution
- `approve`: Text for approve button
- `reject`: Text for reject button
- `delete`: Text for delete button
- `edit`: Text for edit button
- `spam`: Text for spam button
- `confirmDelete`: Confirmation message for deletion

### meh-form

- `formTitle`: Form heading
- `nameLabel`: Label for name field
- `namePlaceholder`: Placeholder for name field
- `emailLabel`: Label for email field
- `emailPlaceholder`: Placeholder for email field
- `websiteLabel`: Label for website field
- `websitePlaceholder`: Placeholder for website field
- `commentLabel`: Label for comment field
- `commentPlaceholder`: Placeholder for comment field
- `submitButton`: Text for submit button
- `submittingButton`: Text while submitting
- `successMessagePending`: Success message for pending comments
- `successMessageApproved`: Success message for approved comments
- `errorPrefix`: Prefix for error messages

### meh-login

- `login`: Text for login button
- `logout`: Text for logout button
- `password`: Label for password field
- `submit`: Text for submit button
- `loginError`: Prefix for login error messages

## Finding All Translation Strings

You can find the complete list of translation strings in several ways:

1. Check the component's TypeScript definition in the source code
2. Look at the default translations in each component's source file
3. Examine the existing translation files in the `i18n` directories

For example, to see all available German translations for the form component, look at:
`frontend/src/components/meh-form/i18n/meh-form.de.json`

## Creating a Complete Translation

If you want to create a complete translation for a new language, you can:

1. Copy the existing translation files
2. Translate all strings
3. Host these files on your server
4. Load them using the `language` attribute

Or you can provide a complete translation object via the `custom-translations` attribute.
