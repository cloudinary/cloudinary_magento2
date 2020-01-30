# Cloudinary Magento 2 Extension
​
The Cloudinary Magento extension links your Magento website to your Cloudinary account, allowing you to serve all your product, category, and content management system (CMS) images directly from Cloudinary.
​
Before you install the extension, make sure you have a Cloudinary account. You can start by [signing up](https://cloudinary.com/signup) for a free plan. When your requirements grow, you can upgrade to a [plan](https://cloudinary.com/pricing) that best fits your needs.
​
For more information on using the Cloudinary Magento 2 extension, take a look at our [documentation](https://cloudinary.com/documentation/magento_integration).
​
## Installation
​
You can download and install the extension from the [Magento Marketplace](https://marketplace.magento.com/cloudinary-cloudinary.html) or install it via composer by running the following commands under your Magento 2 root dir:
​
```
composer require cloudinary/cloudinary-magento2
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```
​
https://www.cloudinary.com/

Copyright © 2020 Cloudinary. All rights reserved.
​
![Cloudinary Logo](https://cloudinary-res.cloudinary.com/image/upload/c_scale,w_300/v1/logo/for_white_bg/cloudinary_logo_for_white_bg.svg)
