/**
 * @fileoverview entry point for your component library
 *
 * This is the entry point for your component library. Use this file to export utilities,
 * constants or data structure that accompany your components.
 *
 * DO NOT use this file to export your components. Instead, use the recommended approaches
 * to consume components of this package as outlined in the `README.md`.
 */

// Define a type for the translations that can be used by consumers
export type MehFormTranslations = {
  formTitle?: string;
  nameLabel?: string;
  namePlaceholder?: string;
  emailLabel?: string;
  emailPlaceholder?: string;
  websiteLabel?: string;
  websitePlaceholder?: string;
  commentLabel?: string;
  commentPlaceholder?: string;
  submitButton?: string;
  submittingButton?: string;
  successMessage?: string;
  errorPrefix?: string;
};

// Export the component types for users to implement
export type * from './components.d.ts';
