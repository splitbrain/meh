# Customizing Translations

Meh components are displayed in English by default. Using the `language` prop an existing different translation can be loaded. When no such translation exists, or you want to overwrite some of the labels, you can use the `custom-translations` prop. 

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

## Finding All Translation Strings

You can find the complete list of translation strings in several ways:

1. Look at the component's documentation. The `Properties` table lists all available translation string keys  in the `type` column.
2. Look at the default translations in each component's source file
