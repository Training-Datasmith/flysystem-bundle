# Architecture: flysystem-bundle

## Purpose

A Symfony bundle that integrates the Flysystem filesystem abstraction library. It reads adapter configuration from `config/packages/flysystem.yaml`, registers named filesystem services in the Symfony DI container, and provides lazy factories for deferred adapter initialisation.

## Directory Structure

```
src/
  Flysystem_Bundle.php                      — Bundle entry point; registers compiler passes
  DependencyInjection/
    Flysystem_Extension.php                 — Processes flysystem.yaml config; registers adapter and filesystem services
    Configuration.php                       — TreeBuilder for flysystem.yaml schema validation
    Compiler/
      Gcloud_Factory_Pass.php               — Registers Google Cloud Storage factory class if the package is installed
      Lazy_Factory_Pass.php                 — Wraps adapters in lazy virtual proxies for deferred initialisation
  Adapter/
    Adapter_Definition_Factory.php          — Dispatches adapter creation to the appropriate builder
    Builder/
      Abstract_Adapter_Definition_Builder.php  — Base: constructs a DI service definition for an adapter
      Adapter_Definition_Builder_Interface.php — Contract for all adapter builders
      Aws_Adapter_Definition_Builder.php       — AWS S3 (official SDK)
      Async_Aws_Adapter_Definition_Builder.php — AWS S3 (AsyncAws)
      Ftp_Adapter_Definition_Builder.php       — FTP
      Gcloud_Adapter_Definition_Builder.php    — Google Cloud Storage
      Local_Adapter_Definition_Builder.php     — Local filesystem
      Memory_Adapter_Definition_Builder.php    — In-memory (tests)
      Sftp_Adapter_Definition_Builder.php      — SFTP
      Azure_Adapter_Definition_Builder.php     — Azure Blob Storage
      Bunny_CDN_Adapter_Definition_Builder.php — BunnyCDN
      Grid_FS_Adapter_Definition_Builder.php   — MongoDB GridFS
      Web_DAV_Adapter_Definition_Builder.php   — WebDAV
  Lazy/
    Lazy_Factory.php                        — Creates adapter instances on first use via a virtual proxy
  Exception/
    Missing_Package_Exception.php           — Thrown when a required adapter package is not installed

tests/
  DependencyInjection/                      — Integration tests for the Symfony extension
  Adapter/Builder/                          — Unit tests per adapter builder
  Kernel/                                   — Minimal Symfony kernel fixtures for DI container testing
```

## Key Design Decisions

- **One builder per adapter** — each storage backend has its own `Adapter_Definition_Builder` that knows which Composer package to require and how to map config keys to constructor arguments.
- **Lazy initialisation** — `Lazy_Factory_Pass` wraps adapters in virtual proxies so connection setup is deferred until the filesystem is first used, avoiding unnecessary overhead in requests that don't touch storage.
- **Missing-package detection** — builders check whether their optional adapter package is installed and throw `Missing_Package_Exception` with an actionable `composer require` command if not.
- **Tag-based discovery** — adapter builders are registered with the `flysystem.adapter` tag, enabling custom adapters to be added from third-party bundles without modifying core code.

## Extension Points

- Implement `Adapter_Definition_Builder_Interface` and tag the service with `flysystem.adapter` to register a custom adapter type.
- Add storage configuration under `flysystem.storages` in `flysystem.yaml`.

## Dependency Flow

```
flysystem.yaml config
  └── Flysystem_Extension (processes config)
        └── Adapter_Definition_Factory (selects builder by DSN/type)
              └── Specific Builder (e.g., Local_Adapter_Definition_Builder)
                    └── DI service definition for the adapter
                          └── Lazy_Factory_Pass wraps it in a virtual proxy
                                └── Filesystem service registered as e.g. flysystem.storage.uploads
```
