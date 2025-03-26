# Styling Meh Components

Meh components are designed to be easily customizable through CSS variables. This allows you to match the components'
appearance to your website's design without having to modify the component code.

## Available CSS Variables

All Meh components use a common set of CSS variables for consistent styling:

| Variable                          | Default                 | Description                                                  |
|-----------------------------------|-------------------------|--------------------------------------------------------------|
| `--meh-default-accent`            | `#2196f3`               | Primary accent color used for buttons, links, and highlights |
| `--meh-default-background`        | `#fefefe`               | Background color for form elements and containers            |
| `--meh-default-foreground`        | `#333`                  | Text color for content and labels                            |
| `--meh-default-spacing`           | `1em`                   | Base spacing unit used for margins and padding               |
| `--meh-default-border-radius`     | `3px`                   | Border radius for buttons, inputs, and other elements        |
| `--meh-default-success`           | `#4caf50`               | Color used for success messages                              |
| `--meh-default-error`             | `#f44336`               | Color used for error messages                                |
| `--meh-default-link-color`        | derived from accent     | Color used for links                                         |
| `--meh-default-link-deco-color`   | derived from link-color | Color used for link underlines                               |
| `--meh-default-border-color`      | derived from foreground | Color used for borders                                       |
| `--meh-default-button-background` | derived from accent     | Background color for buttons                                 |

> Note: Web components will inherit the surrounding's font and font-size settings by default. The components are
> designed to dynamically adjust to the surrounding font size. The Meh components will also dynamically adjust to the
> available width.

## How to Customize

You can override these variables using CSS to customize the appearance of all Meh components:

```css
/* In your website's CSS */
:root {
    --meh-default-accent: #ff6b6b;
    --meh-default-background: #f8f9fa;
    --meh-default-foreground: #212529;
    --meh-default-spacing: 1.2rem;
    --meh-default-border-radius: 4px;
    --meh-default-success: #20c997;
    --meh-default-error: #dc3545;
}
```

## More Customization

If customizing via the given CSS variables is not enough, you can also use the `externalStyles` attribute on the Meh
components to inject a stylesheet directly into the component's Shadow DOM. This allows you to apply custom styles
directly to the component's internal elements.

```html

<meh-form externalStyles="path/to/custom.css"></meh-form>
```

