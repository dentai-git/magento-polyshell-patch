# MarkShust_PolyshellPatch for Magento 2.3.x

Mitigates the PolyShell vulnerability — an unrestricted file upload in the Magento REST API that allows attackers to upload executable files via cart item custom option file uploads.

This module was originally forked from [markshust/magento2-module-polyshell-patch](https://github.com/markshust/magento-polyshell-patch) by [Mark Shust](https://github.com/markshust).

---

> **Compatibility note:** This fork has been updated to support **PHP 7.3** and **Magento 2.3.x** (`magento/framework` 102.x). The original module required PHP 8.1+ and Magento 2.4.x only.

---

## What this module does

Two plugins enforce an image-only extension allowlist (`jpg`, `jpeg`, `gif`, `png`):

1. **ImageContentValidatorExtension** — rejects filenames with non-image extensions before the file is written to disk.
2. **ImageProcessorRestrictExtensions** — calls `setAllowedExtensions()` on the `Uploader` so the framework's own extension check blocks dangerous files as a second layer.

## Installation

```bash
composer require markshust/magento2-module-polyshell-patch
bin/magento module:enable MarkShust_PolyshellPatch
bin/magento setup:upgrade
bin/magento cache:flush
```

## Web server hardening (required for production)

The module blocks uploads at the application layer, but defense-in-depth requires blocking execution/access at the web server level too. Apply the appropriate config below.

### Nginx

Add this **before** any `location ~ \.php$` block to prevent it from taking priority:

```nginx
location ^~ /media/custom_options/ {
    deny all;
    return 403;
}
```

Verify the order matters — nginx processes `^~` prefix matches before regex matches, so this ensures `.php` files in this directory are never passed to FastCGI.

Reload after applying:

```bash
nginx -t && nginx -s reload
```

### Apache

Verify that `pub/media/custom_options/.htaccess` exists and contains:

```apache
<IfVersion < 2.4>
    order deny,allow
    deny from all
</IfVersion>
<IfVersion >= 2.4>
    Require all denied
</IfVersion>
```

Also confirm that `AllowOverride All` is set for your document root so `.htaccess` files are honored.

## Scan for existing compromise

Check whether any files have already been uploaded to the custom_options directory:

```bash
find pub/media/custom_options/ -type f ! -name '.htaccess'
```

If any files are found (especially `.php`, `.phtml`, or `.phar`), investigate immediately — they may be webshells.

## When to remove this module

### Magento 2.3.x

Adobe **will not patch Magento 2.3.x** — it has been end-of-life since September 2023 and APSB25-94 only covers 2.4.4 and above. This module provides a fix for 2.3.x stores until a better option is available.

The recommended long-term approach is to upgrade to a supported Magento version (2.4.6 or later with the corresponding security patch applied). Until then, keep this module enabled.

A more comprehensive 2.3.x-compatible module may be available - check for forks of [aregowe/magento2-module-polyshell-protection](https://github.com/aregowe/magento2-module-polyshell-protection) that target Magento 2.3.x, as that module extends it significantly with:

- Polyglot file scanning (detects valid images with embedded PHP)
- No-extension and double-extension attack detection
- Multi-pass URL decoding and obfuscation normalization
- Known attack filename/pattern matching
- Request path blocking at the FrontController and `pub/get.php` level
- Controller-level upload blocking for customer attribute and file upload endpoints
- A kill switch blocking all custom option file uploads via the Webapi File Processor

### Magento 2.4.x

Adobe has released official patches for all supported 2.4.x branches:

| Your version | Upgrade to | Patch released |
|---|---|---|
| 2.4.4-p15 and earlier | 2.4.4-p16 
| 2.4.5-p14 and earlier | 2.4.5-p15
| 2.4.6-p12 and earlier | 2.4.6-p13
| 2.4.7-p7 and earlier | 2.4.7-p8
| 2.4.8-p2 and earlier | 2.4.8-p3

Once you have applied the official Adobe patch for your 2.4.x version, you can remove this module:

If you have installed it manually:

```bash
bin/magento module:disable MarkShust_PolyshellPatch
bin/magento setup:upgrade
rm -rf app/code/MarkShust/PolyshellPatch
bin/magento cache:flush
```

If you have installed it by composer:

```bash
bin/magento module:disable MarkShust_PolyshellPatch
bin/magento setup:upgrade
composer remove markshust/magento2-module-polyshell-patch
bin/magento cache:flush
```

## Why this module is intentionally minimal

Adobe's [official fix](https://github.com/magento/magento2/commit/796c4ce195cee0814ac92e5a19fc2ecfa79dae69) spans 18 files (+997 lines) across `Magento_Catalog`, `Magento_Quote`, and the framework. It introduces a new `ImageContentProcessor`, a `CartItemValidatorChain` at the Repository layer, an `ImageContentUploaderInterface`, and API-scoped DI configuration.

We intentionally did not replicate that approach because:

- **It modifies core module internals.** The official patch alters constructors, adds dependencies to `CustomOptionProcessor` and `Repository`, and introduces new interfaces — changes that are tightly coupled to specific Magento versions and could conflict with the official patch when it ships.
- **A minimal allowlist is sufficient to block the exploit.** The vulnerability is that any file extension is accepted. Our two plugins enforce a strict image-only allowlist (`jpg`, `jpeg`, `gif`, `png`) at both the validator and uploader level. This is actually stricter than the official fix, which uses a denylist approach (`NotProtectedExtension`) that rejects known-dangerous extensions.
- **Lower risk of side effects.** A small, self-contained module with two plugins is easy to audit, test, and remove cleanly — which is exactly what you want from a temporary hotfix.

## References

- [Sansec: Magento PolyShell](https://sansec.io/research/magento-polyshell)
- [Adobe official fix (commit)](https://github.com/magento/magento2/commit/796c4ce195cee0814ac92e5a19fc2ecfa79dae69)
- [Adobe Security Bulletin APSB25-94](https://helpx.adobe.com/security/products/magento/apsb25-94.html)
- Patched in Magento 2.4.4-p16, 2.4.5-p15, 2.4.6-p13, 2.4.7-p8, 2.4.8-p3 (October 2025)
- **Magento 2.3.x: no official patch — EOL version, this module is the permanent fix**

## Credits

### M.academy

This module is sponsored by <a href="https://m.academy" target="_blank">M.academy</a>, the simplest way to learn Magento.
