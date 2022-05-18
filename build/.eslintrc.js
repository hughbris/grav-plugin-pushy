module.exports = {
    root: true,
    parser: '@typescript-eslint/parser',
    parserOptions: {
      "ecmaVersion": 6,
      "sourceType": "module",
      project: './build/tsconfig.json',
    },
    plugins: [
      '@typescript-eslint',
    ],
    extends: [
      'eslint:recommended',
      'plugin:@typescript-eslint/recommended',
      "plugin:@typescript-eslint/recommended-requiring-type-checking",
    ],
  };