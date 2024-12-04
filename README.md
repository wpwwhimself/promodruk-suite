# Magazyn

This is an app commissioned by [Promodruk](www.promodruk.pl) in order to track stock qunatities from multiple outside contractors.

The company sells items with custimised prints and on their shop one can order these items in bulk.
As they don't stock these items, they are being ordered from many distributors, each with their own way of communicating their stock quantities and expected future deliveries. The goal of this app is to tally down all this data and present it in a clear and informative way.

## Capabilities

- 🚚 API integration with multiple different sources
- 📊 concise view of stock quantities
- ...and that's about it. Nothing more is needed

## 🧑‍💻 Dev zone
### 🧪 Run tests
1. Run all tests
```
php artisan test
```

2. Run single test
```
vendor/bin/phpunit --filter criteria
```
where `criteria` can be class name (`'ExampleTest'`) or method name (`testExample`)
