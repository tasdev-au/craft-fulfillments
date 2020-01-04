# Order Fulfillments Plugin for Craft CMS

Fulfillments is a plugin for Craft CMS to let you create "Shopify like" fulfillments for Craft Commerce orders. Fulfillments can be configured to automatically update your order statuses and send notification emails.
<img width="500" src="https://tas.dev/uploads/plugins/fulfillments/screenshot-1.png" style="box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 4px; border: 1px solid rgba(0,0,0,0.12);">

## Documentation
Read the full documentation [here](https://tas.dev/plugins/fulfillments/installation).

## Features

- Create fulfillments for orders.
- Enter tracking information and choose from a number of default shipping carriers.
- Partially fulfill orders - supports multiple fulfillments.
- Extendable to allow additional carriers to be added.

<img width="500" src="https://tas.dev/uploads/plugins/fulfillments/screenshot-2.png" style="box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 4px; border: 1px solid rgba(0,0,0,0.12);">

## Adding Carriers

Plugins and modules can register their own carriers to choose from when fulfilling orders.
```php
use craft\events\RegisterComponentTypesEvent;
use tasdev\orderfulfillments\services\Carriers;
use yii\base\Event;

Event::on(Carriers::class, Carriers::EVENT_REGISTER_CARRIERS, function(RegisterComponentTypesEvent $e) {
    $e->types[] = MyCarrier::class;
});
```

To see what your `MyCarrier` class might look like, [take a look at the default classes](src/carriers/AusPost.php).

## Support

[Create a Github issue](https://github.com/tasdev-au/craft-fulfillments/issues) if you experience a bug with the Fulfillments plugin.

<a href="https://tas.dev" target="_blank">
  <img width="100" src="https://tas.dev/assets/img/logo-text.svg">
</a>
