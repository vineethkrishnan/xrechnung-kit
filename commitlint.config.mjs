/** @type {import('@commitlint/types').UserConfig} */
export default {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [
      2,
      'always',
      [
        'feat',
        'fix',
        'refactor',
        'chore',
        'docs',
        'style',
        'perf',
        'test',
        'build',
        'ci',
        'revert',
      ],
    ],
    'subject-case': [2, 'always', ['lower-case']],
    'subject-full-stop': [2, 'never', '.'],
    'header-max-length': [2, 'always', 100],
    'body-leading-blank': [2, 'always'],
    'footer-leading-blank': [2, 'always'],
    // Conventional defaults wrap body / footer at 100 chars. We do not
    // hard-wrap by policy (paragraphs as single long lines, soft-wrap in
    // the renderer), and Dependabot release notes routinely exceed 100.
    'body-max-line-length': [0],
    'footer-max-line-length': [0],
  },
};
