# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This file is maintained automatically by [release-please](https://github.com/googleapis/release-please) from Conventional Commits on `main`. Manual edits will be overwritten on the next release.

## [2.1.1](https://github.com/vineethkrishnan/xrechnung-kit/compare/v2.1.0...v2.1.1) (2026-05-10)


### Bug Fixes

* **ci:** make split-and-publish work on tag-push triggers ([5043faa](https://github.com/vineethkrishnan/xrechnung-kit/commit/5043faa4c9389409f59af801f81c00811c0a3f62))

## [2.1.0](https://github.com/vineethkrishnan/xrechnung-kit/compare/v2.0.0...v2.1.0) (2026-05-10)


### Features

* **adapters:** scaffold typo3, shopware, and wordpress integrations ([4431535](https://github.com/vineethkrishnan/xrechnung-kit/commit/4431535116335fd4d363cd9dbdc5ea07eacdc43a))
* **ci:** auto-publish core to packagist via splitsh mirror ([e991c21](https://github.com/vineethkrishnan/xrechnung-kit/commit/e991c21f993332b3e15e67cdf722318a581ad44a))
* **docs:** auto-generated phpdocumentor reference + node 24 actions ([8316f39](https://github.com/vineethkrishnan/xrechnung-kit/commit/8316f39c8447b22db3e116919ae3cc4662aaee0c))
* **docs:** vitepress site with german-formal styling and silicon walkthrough ([6d9ddc3](https://github.com/vineethkrishnan/xrechnung-kit/commit/6d9ddc36a63fe3220f1f5e83eeb5a2784b112ffa))
* **pdfa:** scaffold xrechnung-kit-pdfa package for hybrid invoices ([e778c87](https://github.com/vineethkrishnan/xrechnung-kit/commit/e778c8775bd0bf675d5caeeee67a5122faa11861))
* scaffold contenido cms plugin and add extending guide ([02911c4](https://github.com/vineethkrishnan/xrechnung-kit/commit/02911c4acfeca82f9c7a5988aac6df0954035786))
* **shopware:** phase 5 - peppol delivery (vendor-neutral webhook) ([1c5d074](https://github.com/vineethkrishnan/xrechnung-kit/commit/1c5d074d6e9b8026331bae7977523bd3e23282f0))
* **shopware:** phase A - plugin config + DAL entity + custom fields ([d60f206](https://github.com/vineethkrishnan/xrechnung-kit/commit/d60f206903e1b6c4bc4ed3d313bd089204806217))
* **shopware:** phase B - OrderToMappingData + state subscriber + persistence ([b30d9f2](https://github.com/vineethkrishnan/xrechnung-kit/commit/b30d9f241f6e0c6fada12e28655aa4ceedaff591))
* **shopware:** phase C - admin tab on order detail with download ([60ae972](https://github.com/vineethkrishnan/xrechnung-kit/commit/60ae9722b6797aa97615e3cc4f58ccc2e1537d30))
* **shopware:** phase D - retry, quarantine, audit, scheduled task, notification channel ([0df415f](https://github.com/vineethkrishnan/xrechnung-kit/commit/0df415f95854b609124ff9d2e3f360bedb1db188))


### Bug Fixes

* **ci:** authenticate mirror push with PAT username, not x-access-token ([2235f49](https://github.com/vineethkrishnan/xrechnung-kit/commit/2235f49916fe96caed526e3596c91434cff2743a))
* **ci:** disable persist-credentials so PAT-embedded mirror push wins ([ca48e23](https://github.com/vineethkrishnan/xrechnung-kit/commit/ca48e2378f43e6b5a92548b76c98efe51603f7e6))
* **ci:** drop nonexistent tests/Unit dir from phpunit.xml.dist ([b55c519](https://github.com/vineethkrishnan/xrechnung-kit/commit/b55c519417bea90e52cfec715d04e3ca98262c8f))
* **ci:** drop php 8.1 from shopware matrix, allow shopware composer plugins ([d35a723](https://github.com/vineethkrishnan/xrechnung-kit/commit/d35a7234de0b4bf8a4b159714775849ec7163990))
* **ci:** drop splitsh state cache, it broke runs after the first ([2128e20](https://github.com/vineethkrishnan/xrechnung-kit/commit/2128e20f6f499ba2fbf2767f6f16bc09fc5cc983))
* **ci:** refresh package-lock.json with cypress 13.17.0 ([4742593](https://github.com/vineethkrishnan/xrechnung-kit/commit/4742593ef78b59b9ba73a3e229a7c83998695e15))
* **shopware:** retro-review fixes for phases A and B ([83d87b6](https://github.com/vineethkrishnan/xrechnung-kit/commit/83d87b6b2252a7025afe162de3da6acdad514a20))


### Documentation

* **core:** add standalone README for the packagist mirror ([129151f](https://github.com/vineethkrishnan/xrechnung-kit/commit/129151f9602d573c181f46748ddd27a4d3bcf6b7))
* cross-link the generated API reference from the high-traffic pages ([d43b684](https://github.com/vineethkrishnan/xrechnung-kit/commit/d43b684536012fd1ac13c84f5d6bed4dd0a3d531))
* drop pre-alpha framing now that v2.0.0 is on packagist ([3e64ad9](https://github.com/vineethkrishnan/xrechnung-kit/commit/3e64ad9afa641d1950a9a37aae47addc38a6545a))
* **readme:** trim documentation line to a single sentence ([d418ba3](https://github.com/vineethkrishnan/xrechnung-kit/commit/d418ba3a85da62ebcf68d7d9d9e79e6283badd26))
* **shopware:** document the future Shopware Store publishing path ([b6f75da](https://github.com/vineethkrishnan/xrechnung-kit/commit/b6f75daae7a1c2ac125bfa39e5f0225fb36a64ce))
* split framework adapters from platform integrations across nav and readmes ([8597930](https://github.com/vineethkrishnan/xrechnung-kit/commit/85979305a428622173ab10fe9198fab8aaa7baef))

## [2.0.0](https://github.com/vineethkrishnan/xrechnung-kit/compare/v1.0.0...v2.0.0) (2026-05-09)


### ⚠ BREAKING CHANGES

* **core:** XRechnungInvoiceTypeCode::REQUEST_FRO_PAYMENT removed (typo); use REQUEST_FOR_PAYMENT instead. Both enums are now case enums rather than const classes; callers passing the constant directly to setTypeCode/setTaxCategory now pass an enum case value instead of a raw int/string. The Generator's str_replace fill helper handles backed-enum -> scalar coercion so generated XML byte-stays identical for callers that pass either the enum case or its raw value.

### Features

* **cli, kosit-bundle:** scaffold the kosit validator bundle and the validate-kosit cli ([5cbb7f2](https://github.com/vineethkrishnan/xrechnung-kit/commit/5cbb7f2d051d3164b03cf69f3a5fa856da5957a4))
* **core:** defense-in-depth sanitisation in builder, plus xxe and injection tests ([cb2eba1](https://github.com/vineethkrishnan/xrechnung-kit/commit/cb2eba1871d9dbdc5e2ea02aa55221b75dfcebe7))
* **core:** extract L3 XRechnungen pipeline with framework-clean abstractions ([504d146](https://github.com/vineethkrishnan/xrechnung-kit/commit/504d146048a936c7fa88bde131b1e77723416c49))
* **core:** foundational typed value objects for mapping data ([2744848](https://github.com/vineethkrishnan/xrechnung-kit/commit/2744848f61b230462a53c4b5ab90eec02d6eb82e))
* **core:** LineItem, TaxBreakdown, DocumentMeta, DocumentTotals value objects ([42835a0](https://github.com/vineethkrishnan/xrechnung-kit/commit/42835a05fba974cb0ab884f3234da6cae4765d92))
* **core:** MappingData root with five named constructors per document class ([d1a1f69](https://github.com/vineethkrishnan/xrechnung-kit/commit/d1a1f692a310a0354e1359e3ccf560a5eeb24f71))
* **core:** Party value object with role-aware named constructors ([cce5003](https://github.com/vineethkrishnan/xrechnung-kit/commit/cce50038ccdfb24a4a4803d9b391605d639bec2a))
* **core:** PaymentMeans value object with seven payment-code variants ([64129fd](https://github.com/vineethkrishnan/xrechnung-kit/commit/64129fd2e264a6bd1d4c4a12c1ab8bb38e9b7f77))
* **core:** XRechnungBuilder bridges MappingData to the lifted entity pipeline ([3979d63](https://github.com/vineethkrishnan/xrechnung-kit/commit/3979d630cbfc548e3a1d229e023dd99fd1871fdd))
* **laminas:** scaffold the laminas / mezzio adapter sub-package ([80cce7c](https://github.com/vineethkrishnan/xrechnung-kit/commit/80cce7cb8bd870881cc44836fd350178c86ddf42))
* **laravel:** scaffold the laravel adapter sub-package ([d14d267](https://github.com/vineethkrishnan/xrechnung-kit/commit/d14d2671d977b58779e30e76331036447e8b7b88))
* **mappers:** scaffold mapper-simple and mapper-bookings sub-packages ([e3d90c1](https://github.com/vineethkrishnan/xrechnung-kit/commit/e3d90c172b28bb2b5551667e8809916ae890c054))
* **symfony:** scaffold the symfony bundle sub-package ([b6208df](https://github.com/vineethkrishnan/xrechnung-kit/commit/b6208df34615483dcdfaba4532bc1af6a97cf50f))


### Bug Fixes

* **core:** coerce backed enum cases via -&gt;value in generator's substitution helpers ([5441092](https://github.com/vineethkrishnan/xrechnung-kit/commit/5441092b1c334d62e923d4a696312f90d3ffaffd))
* **core:** coerce null entity values to empty string in str_replace ([41c5702](https://github.com/vineethkrishnan/xrechnung-kit/commit/41c57025888c81474ecce4fc272ecd73e08fa737))
* **core:** tighten generator output and builder taxscheme mapping ([d24a8c1](https://github.com/vineethkrishnan/xrechnung-kit/commit/d24a8c1606e4e1023cdda73968a0f17b526c17ef))


### Refactors

* **core:** convert invoice-type and tax-category constants to PHP 8.1 enums ([55ee017](https://github.com/vineethkrishnan/xrechnung-kit/commit/55ee017bda4f463e36497ba1b6144cc3c54a12f6))
* **core:** validate-before-write with atomic temp+rename and quarantine sibling ([d82b4bf](https://github.com/vineethkrishnan/xrechnung-kit/commit/d82b4bf30ecaec57301a58916d13d2d619468ebe))


### Documentation

* **examples:** runnable standalone demo of the typed mappingdata pipeline ([9bae6e9](https://github.com/vineethkrishnan/xrechnung-kit/commit/9bae6e97eaf10ad56cb3e91093946a04fabd51c9))
* getting-started, mapping-data, kosit, frameworks, glossary, policies ([78e6849](https://github.com/vineethkrishnan/xrechnung-kit/commit/78e68495f722f7edf70c181d13d716a1fb0204c0))


### Build

* composer manifests and tooling configs ([513a8c8](https://github.com/vineethkrishnan/xrechnung-kit/commit/513a8c80cd50654a81752607baef2e67ba058c35))
* **core:** phpbench setup and 50-line invoice pipeline benchmark ([bc70c84](https://github.com/vineethkrishnan/xrechnung-kit/commit/bc70c84aa522d76c38cf60616aaa2cc6fab28acc))

## 1.0.0 (2026-05-09)


### ⚠ BREAKING CHANGES

* **core:** XRechnungInvoiceTypeCode::REQUEST_FRO_PAYMENT removed (typo); use REQUEST_FOR_PAYMENT instead. Both enums are now case enums rather than const classes; callers passing the constant directly to setTypeCode/setTaxCategory now pass an enum case value instead of a raw int/string. The Generator's str_replace fill helper handles backed-enum -> scalar coercion so generated XML byte-stays identical for callers that pass either the enum case or its raw value.

### Features

* **cli, kosit-bundle:** scaffold the kosit validator bundle and the validate-kosit cli ([d1138cd](https://github.com/vineethkrishnan/xrechnung-kit/commit/d1138cdacd3aae3df8f88bfeb643aca2fd945d6e))
* **core:** defense-in-depth sanitisation in builder, plus xxe and injection tests ([bb3944f](https://github.com/vineethkrishnan/xrechnung-kit/commit/bb3944fddbf09cfd5667de0b19ef6a1e65b5bf07))
* **core:** extract L3 XRechnungen pipeline with framework-clean abstractions ([fa44424](https://github.com/vineethkrishnan/xrechnung-kit/commit/fa444243dcab6f7cbc513b54ebf65d8aefa94172))
* **core:** foundational typed value objects for mapping data ([2b935c3](https://github.com/vineethkrishnan/xrechnung-kit/commit/2b935c3780cfb01dce7f5b5ae8a5dd5cd7f92c48))
* **core:** LineItem, TaxBreakdown, DocumentMeta, DocumentTotals value objects ([e86809e](https://github.com/vineethkrishnan/xrechnung-kit/commit/e86809e9e3c6918ba91e112f78d98e503e6bb0c7))
* **core:** MappingData root with five named constructors per document class ([251e2b9](https://github.com/vineethkrishnan/xrechnung-kit/commit/251e2b99a26ed921f25e33cc1ef56ec297530edd))
* **core:** Party value object with role-aware named constructors ([840b006](https://github.com/vineethkrishnan/xrechnung-kit/commit/840b0068dfee4135596120923b639e43ee038199))
* **core:** PaymentMeans value object with seven payment-code variants ([49e6a80](https://github.com/vineethkrishnan/xrechnung-kit/commit/49e6a80fb39e0de362aab78633c274f504f6efc9))
* **core:** XRechnungBuilder bridges MappingData to the lifted entity pipeline ([1bf6056](https://github.com/vineethkrishnan/xrechnung-kit/commit/1bf605653629803dab644696fb5c017690393fe0))
* **laminas:** scaffold the laminas / mezzio adapter sub-package ([1b1c478](https://github.com/vineethkrishnan/xrechnung-kit/commit/1b1c47847f46966c809f1a0ea68d041b3baac4ed))
* **laravel:** scaffold the laravel adapter sub-package ([7d3ea3f](https://github.com/vineethkrishnan/xrechnung-kit/commit/7d3ea3f89f269c1cbb6de9e5ae3835616a8ea76c))
* **mappers:** scaffold mapper-simple and mapper-bookings sub-packages ([ceb2f92](https://github.com/vineethkrishnan/xrechnung-kit/commit/ceb2f9233160ba0e5414d889bb643b68c1d07a24))
* **symfony:** scaffold the symfony bundle sub-package ([e925006](https://github.com/vineethkrishnan/xrechnung-kit/commit/e9250060a0c240a21cb71eec23a72b2f66c6a159))


### Bug Fixes

* **core:** coerce backed enum cases via -&gt;value in generator's substitution helpers ([3834876](https://github.com/vineethkrishnan/xrechnung-kit/commit/383487699ee6da25558ed087d1ebbc376c29a9db))
* **core:** coerce null entity values to empty string in str_replace ([4843b2b](https://github.com/vineethkrishnan/xrechnung-kit/commit/4843b2b3bd6750c4d7a6667c297121c1ec8cad30))


### Refactors

* **core:** convert invoice-type and tax-category constants to PHP 8.1 enums ([88c0933](https://github.com/vineethkrishnan/xrechnung-kit/commit/88c093307fd36ecae208f2b007ecc509c51167c7))
* **core:** validate-before-write with atomic temp+rename and quarantine sibling ([8a207d1](https://github.com/vineethkrishnan/xrechnung-kit/commit/8a207d17a12d55a55f1d4c841948209f5148d8d1))


### Documentation

* getting-started, mapping-data, kosit, frameworks, glossary, policies ([1291e2c](https://github.com/vineethkrishnan/xrechnung-kit/commit/1291e2c4628b43ff86da5bd0263bb1985c08a531))


### Build

* composer manifests and tooling configs ([ebf3ef8](https://github.com/vineethkrishnan/xrechnung-kit/commit/ebf3ef8c2bc4c94ca74c43da6063d624d602ff3e))
* **core:** phpbench setup and 50-line invoice pipeline benchmark ([4099a77](https://github.com/vineethkrishnan/xrechnung-kit/commit/4099a777f0453cad9699e27e08a97f4b48cf08bb))

## [Unreleased]

### Added

- Initial repository scaffolding (README, license, contributing, security policy, code of conduct).

[Unreleased]: https://github.com/vineethkrishnan/xrechnung-kit/commits/main
