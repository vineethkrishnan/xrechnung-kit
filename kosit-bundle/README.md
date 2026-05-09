# vineethkrishnan/xrechnung-kit-kosit-bundle

The KoSIT Schematron validator JAR and pinned scenarios, packaged so xrechnung-kit's CLI (`validate-kosit`) can locate them via composer's autoloader.

## What's in this package

- `src/Bundle.php` — `XrechnungKit\KositBundle\Bundle::validatorJarPath()` and `::scenariosPath()` resolve absolute filesystem paths to the JAR and the scenarios manifest.
- `data/` — intentionally empty in this repository. The JAR (~50 MB) and scenarios repository (~200 MB) are fetched once at install time per the steps below; their licenses are reproduced in the parent project's `LICENSE-third-party.md`.

## Installation

```bash
composer require --dev vineethkrishnan/xrechnung-kit-kosit-bundle
```

## Pinned versions

| Component | Version |
|---|---|
| KoSIT validator JAR | 1.5.0 |
| KoSIT scenarios | 2.0.1 |

## Fetching the binaries

The first time you use the bundle, run:

```bash
cd vendor/vineethkrishnan/xrechnung-kit-kosit-bundle/data
curl -L -o validator.jar \
  https://github.com/itplr-kosit/validator/releases/download/v1.5.0/validator-1.5.0-standalone.jar
curl -L -o scenarios.zip \
  https://github.com/itplr-kosit/validator-configuration-xrechnung/releases/download/release-2.0.1/validator-configuration-xrechnung_3.0.1_2024-06-20.zip
unzip scenarios.zip -d scenarios
```

A future release will automate this via a composer post-install script. The JAR and scenarios are not committed to this repository to keep the install footprint small for consumers who do not need KoSIT validation.

## License

The validator JAR is published by KoSIT (Koordinierungsstelle fuer IT-Standards) under the EUPL 1.2 license. The scenarios are published under CC-BY 3.0 DE. Both licenses are reproduced in the parent project's `LICENSE-third-party.md`.
