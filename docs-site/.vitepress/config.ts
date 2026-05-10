import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'xrechnung-kit',
  description:
    'EN 16931 / XRechnung 3.0 compliant e-invoice generator and validator for PHP. Framework-agnostic core with first-class Laravel, Symfony, CakePHP, and Laminas adapters.',
  lang: 'en',
  cleanUrls: true,
  lastUpdated: true,
  ignoreDeadLinks: 'localhostLinks',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#0a1a3a' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'xrechnung-kit' }],
    ['meta', { property: 'og:site_name', content: 'xrechnung-kit' }],
    ['meta', { property: 'og:url', content: 'https://xrechnung-kit.vineethnk.in/' }],
    [
      'meta',
      {
        property: 'og:description',
        content:
          'KoSIT-strict valid XRechnung 3.0 / EN 16931 generation, in-memory UBL XSD validation, optional KoSIT Schematron, and idiomatic adapters for the four major PHP frameworks.',
      },
    ],
    [
      'link',
      {
        rel: 'preconnect',
        href: 'https://fonts.googleapis.com',
      },
    ],
    [
      'link',
      {
        rel: 'preconnect',
        href: 'https://fonts.gstatic.com',
        crossorigin: '',
      },
    ],
    [
      'link',
      {
        rel: 'stylesheet',
        href: 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Serif:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap',
      },
    ],
  ],

  themeConfig: {
    logo: { src: '/logo.svg', alt: 'xrechnung-kit wordmark' },
    siteTitle: 'xrechnung-kit',

    nav: [
      { text: 'Introduction', link: '/' },
      { text: 'Getting Started', link: '/getting-started' },
      {
        text: 'Guides',
        items: [
          { text: 'Mapping data', link: '/mapping-data' },
          { text: 'KoSIT validation', link: '/kosit-validation' },
          { text: 'Walkthrough', link: '/walkthrough/' },
          { text: 'Migrating from easybill', link: '/migrating-from-easybill' },
        ],
      },
      {
        text: 'Frameworks',
        items: [
          { text: 'Laravel', link: '/frameworks/laravel' },
          { text: 'Symfony', link: '/frameworks/symfony' },
          { text: 'CakePHP', link: '/frameworks/cakephp' },
          { text: 'Laminas', link: '/frameworks/laminas' },
          { text: 'TYPO3', link: '/frameworks/typo3' },
          { text: 'Shopware 6', link: '/frameworks/shopware' },
          { text: 'WordPress', link: '/frameworks/wordpress' },
          { text: 'Contenido CMS', link: '/frameworks/contenido' },
        ],
      },
      {
        text: 'Extending',
        link: '/extending',
      },
      {
        text: 'Reference',
        items: [
          { text: 'API overview', link: '/reference/api' },
          { text: 'Generated API reference', link: '/api/', target: '_blank' },
          { text: 'Glossary (DE)', link: '/glossary-de' },
          { text: 'Document type codes', link: '/reference/document-types' },
          { text: 'Versioning policies', link: '/policies' },
        ],
      },
      {
        text: 'v2.0.0',
        items: [
          { text: 'Changelog', link: 'https://github.com/vineethkrishnan/xrechnung-kit/blob/main/CHANGELOG.md' },
          { text: 'Upgrading 0.x to 1.0', link: '/upgrading/0.x-to-1.0' },
          { text: 'On Packagist', link: 'https://packagist.org/packages/vineethkrishnan/xrechnung-kit-core' },
        ],
      },
    ],

    sidebar: {
      '/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Overview', link: '/' },
            { text: 'Getting started', link: '/getting-started' },
            { text: 'Walkthrough', link: '/walkthrough/' },
          ],
        },
        {
          text: 'Core concepts',
          items: [
            { text: 'Mapping data contract', link: '/mapping-data' },
            { text: 'KoSIT Schematron validation', link: '/kosit-validation' },
          ],
        },
        {
          text: 'Framework integration',
          items: [
            { text: 'Laravel', link: '/frameworks/laravel' },
            { text: 'Symfony', link: '/frameworks/symfony' },
            { text: 'CakePHP', link: '/frameworks/cakephp' },
            { text: 'Laminas', link: '/frameworks/laminas' },
            { text: 'TYPO3', link: '/frameworks/typo3' },
            { text: 'Shopware 6', link: '/frameworks/shopware' },
            { text: 'WordPress', link: '/frameworks/wordpress' },
            { text: 'Contenido CMS', link: '/frameworks/contenido' },
          ],
        },
        {
          text: 'Extending',
          items: [
            { text: 'Extending xrechnung-kit', link: '/extending' },
          ],
        },
        {
          text: 'Reference',
          items: [
            { text: 'API overview', link: '/reference/api' },
            { text: 'Generated API reference', link: '/api/', target: '_blank' },
            { text: 'Document type codes', link: '/reference/document-types' },
            { text: 'Glossary (DE)', link: '/glossary-de' },
            { text: 'Versioning policies', link: '/policies' },
          ],
        },
        {
          text: 'Migration',
          items: [
            { text: 'From easybill/xrechnung-php', link: '/migrating-from-easybill' },
            { text: 'Upgrading 0.x to 1.0', link: '/upgrading/0.x-to-1.0' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/vineethkrishnan/xrechnung-kit' },
    ],

    editLink: {
      pattern:
        'https://github.com/vineethkrishnan/xrechnung-kit/edit/main/docs-site/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
      options: {
        detailedView: true,
      },
    },

    outline: { level: [2, 3], label: 'On this page' },

    docFooter: {
      prev: 'Previous',
      next: 'Next',
    },

    footer: {
      message:
        'Released under the MIT License. xrechnung-kit is an independent open source library and is neither affiliated with nor endorsed by KoSIT or any German government agency.',
      copyright: 'Copyright (c) Vineeth N K and contributors',
    },
  },
})
