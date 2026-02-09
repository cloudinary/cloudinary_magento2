# Changelog

## Deprecation Notice

### Image Delivery Domain Sharding (CDN Subdomain)

The **Image Delivery Domain Sharding** feature (`cdn_subdomain`) is deprecated and will be removed in the next major version.

#### How to Disable via Magento CLI

To disable the Image Delivery Domain Sharding setting using the Magento CLI, run:

```bash
bin/magento config:set cloudinary/configuration/cloudinary_cdn_subdomain 0
```

To verify the current value:

```bash
bin/magento config:show cloudinary/configuration/cloudinary_cdn_subdomain
```

After changing the configuration, flush the cache:

```bash
bin/magento cache:flush
```

#### Alternative: Disable via Admin Panel

1. Navigate to **Stores > Configuration > Cloudinary > Settings**
2. Expand the **Auto-upload** section
3. Set **Image Delivery Domain Sharding** to **No**
4. Click **Save Config**
5. Flush cache
