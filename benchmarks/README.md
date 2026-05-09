# Benchmarks

Performance regression tests for the core pipeline. The benchmarks run on every release-please PR; regressions of >20% on any tracked metric block the release.

## Tracked metrics

| Metric | Target | Measurement |
|---|---|---|
| 50-line invoice generation | <50 ms wall on a 2024 laptop | `MappingData -> file on disk` end-to-end |
| KoSIT validation per file | <500 ms wall (Java startup dominates) | `validate-kosit` over a single fixture |
| Peak memory per generation | <16 MB | `memory_get_peak_usage(true)` after generation |

## Running locally

```bash
composer benchmark
```

Behind the scenes this invokes [PHPBench](https://phpbench.readthedocs.io/) with the bundled config (`phpbench.json`), iterates the suites, and writes a Markdown summary to `benchmarks/last-run.md`.

To compare against a baseline:

```bash
composer benchmark -- --baseline=main
```

## Regression policy

A pull request that regresses any tracked metric by more than 20% (across 5 runs, median) requires explicit reviewer sign-off. The release-please workflow blocks the bump until the regression is either fixed or accepted (with an explanation in the release notes).

## Hardware reference

Targets are normalised to the GitHub-hosted `ubuntu-latest` runner at the time of measurement. Local numbers will vary; what matters is the relative delta between commits on the same hardware.

## Status

Stub. The benchmark suites land alongside the source extraction (checklist item A1). The directory exists so CI workflow references stay valid.
