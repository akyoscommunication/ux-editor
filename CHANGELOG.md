# Changelog

All notable changes to `akyoscommunication/ux-editor` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added — Compatibility UX 3.x / Symfony 8.1

- **Dual support Symfony UX 2.34 _and_ UX 3.x on the same branch.** Composer
  constraints widened so the bundle installs on both legacy projects (UX 2.34)
  and new projects (Symfony 8.1 + UX 3.x) without forcing a downgrade on either
  side:
  - `symfony/ux-icons`: `^2.34` → `^2.34|^3.0`
  - `symfony/ux-live-component`: `^2.34` → `^2.34|^3.0`
  - `symfony/ux-twig-component`: now an explicit dependency, `^2.34|^3.0`
  - `symfony/stimulus-bundle`: now an explicit dependency, `^2.9|^3.0`
  - `symfony/form` / `symfony/framework-bundle`: promoted to `require`
    (`^6.4|^7.0|^8.0`) — they were already required at runtime
    (`AbstractController`, Form types) but only listed in `require-dev`.
- `require-dev` aligned with Symfony 8 (`symfony/dependency-injection`,
  `symfony/http-kernel` → `^6.4|^7.0|^8.0`; `symfonycasts/tailwind-bundle`
  → `^0.6|^0.13`).

### Notes

- **No PHP / Twig / JS code change was required.** Every UX API used by the
  bundle (`#[AsLiveComponent]`, `LiveProp`, `LiveAction`, `LiveArg`,
  `LiveListener`, `DefaultActionTrait`, `ComponentToolsTrait`,
  `LiveCollectionTrait`, `ComponentWithFormTrait`, `HydrationExtensionInterface`,
  `AsTwigComponent`, `ExposeInTemplate`, `<twig:ux:icon>`, the `stimulus_*`
  Twig helpers and `@hotwired/stimulus ^3`) keeps an identical signature
  between UX 2.34 and UX 3.x. The bundle's `HydrationExtensionInterface`
  implementations remain valid: their `object` / `array` return types are
  covariant with the interface's `?object` / `mixed`.

### Known limitations

- Not validated against a running UX **2.34** project in this change — the
  2.34 constraint is preserved and no 2.x-only API was removed, so existing
  consumers are expected to keep working, but a smoke test on a 2.34 app is
  recommended before tagging a release.
- Stimulus controllers are shipped pre-built under `assets/dist/`. AssetMapper
  is the supported integration path (Webpack Encore is not actively tested).
