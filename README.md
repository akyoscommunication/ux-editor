# ux-editor-bundle
This bundle provides a simple way to integrate a builder with twig components in Symfony.
Using LiveComponent.

## Requirements

The bundle supports **Symfony UX 2.34 and UX 3.x on the same version**, so it can
be installed both on legacy projects and on new Symfony 8.1 projects without
forcing a downgrade of `symfony/ux-icons`, `symfony/ux-live-component` or
`symfony/stimulus-bundle`.

| Dependency                     | Supported versions      |
|--------------------------------|-------------------------|
| PHP                            | `>= 8.3`                |
| symfony/framework-bundle       | `^6.4` \| `^7.0` \| `^8.0` |
| symfony/form                   | `^6.4` \| `^7.0` \| `^8.0` |
| symfony/stimulus-bundle        | `^2.9` \| `^3.0`        |
| symfony/ux-live-component      | `^2.34` \| `^3.0`       |
| symfony/ux-twig-component      | `^2.34` \| `^3.0`       |
| symfony/ux-icons               | `^2.34` \| `^3.0`       |
| doctrine/orm                   | `^3.2`                  |

Verified stacks:

- **Symfony 8.1** + UX 3.x (`stimulus-bundle ^3.1`, `ux-* ^3.1`), AssetMapper, Doctrine ORM `^3.6`, PHP `>= 8.4`.
- **Symfony 6.4 / 7.x** + UX 2.34 (constraint preserved; no 2.x-only API removed).

## Installation

```bash
composer require akyoscommunication/ux-editor
```

If you use **AssetMapper** (default on Symfony 8.1), install the shipped Stimulus
controllers and assets:

```bash
php bin/console importmap:install
php bin/console cache:clear
```

### Local testing via a path repository

To test an unreleased version of the bundle from a sibling checkout, add a `path`
repository to the consumer project's `composer.json`:

```json
{
  "repositories": [
    { "type": "path", "url": "../ux-editor", "options": { "symlink": true } }
  ]
}
```

then:

```bash
composer require akyoscommunication/ux-editor:@dev
```

// ROADMAP
- [x] Create a simple builder
- [x] Create a simple component
- [x] Create a simple component with props

// TODO
- [x] Add compilerPass to retrieve all components
- [ ] Retrieve Component name from class 
- [ ] Add a way configure categories in YAML identified by a key
- [ ] Style with cols and rows
- [ ] File type with other file than image
- [ ] Continue with layers
- [ ] Render icon in the builder, iframe or render dynamically page
- [ ] Wait for UX Live component onUpdated working
- [ ] Filter fields by category
- [ ] add children to component

// IDEAS
- [ ] Try to retrieve cva for choice type
- [ ] Add a way to disable / able sub components fields ( writable: ['img' => ['src']], )
