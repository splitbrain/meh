import {Config} from '@stencil/core';

export const config: Config = {
  namespace: 'meh',
  outputTargets: [
    {
      type: 'dist',
      esmLoaderPath: '../loader',
      copy: [
        { src: 'components/*/i18n/*.json', dest: 'i18n', warn: true }
        ]
    },
    {
      type: 'docs-readme',
    },
  ],
  testing: {
    browserHeadless: "shell",
  },
};
