# Fulfillments Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.0.0-beta.1 - 2024-03-26
### Added
- Craft 5/Commerce 5 support.
- Please ensure you have updated to the latest version of Fulfillments on Craft 4/Commerce 4 before upgrading to this version.

## 4.1.0 - 2024-03-26
### Changed
- Shipping carriers are now saved in the project config. This means that if you are adding customer carriers, you may need to reconfigure them.
- To get your tracking URL in emails etc., you should now use `fulfillment.trackingUrl` instead of `fulfillment.shippingCarrier.trackingUrl`.

## 4.0.1 - 2023-06-01
### Fixed
- Fixed issue where an element query was executed before Craft was fully initialized.

## 4.0.0 - 2022-12-19
### Changed
- No longer recalculates orders when updating status.

## 2.0.1 - 2022-05-10
### Fixed
- Fixed bug with plugin permissions.

## 2.0.0 - 2022-05-07
### Fixed
- Updated for compatibility with Craft 4 and Commerce 4.
### Added
- Added Sendle carrier.

## 1.1.2 - 2021-08-23
### Fixed
- Fixed issue with fulfillments tab being highlighted by default.
### Changed
- Updated fulfillments tab to use the `cp.commerce.order.content` hook.
- Now requires Craft Commerce >= 3.2.13

## 1.1.1 - 2020-05-30
### Fixed
- Fixed psr-4 autoload warning.

## 1.1 - 2020-04-03
### Added
- New `FulfillableQtyEvent` event (thanks [@moldedjelly](https://github.com/moldedjelly))
- `dateCreated` and `dateUpdated` added to fulfillment model

## 1.0.3 - 2020-03-25
### Fixed
- Fixed issue caused by the `getDescription` method being removed from the LineItem model.

## 1.0.2 - 2020-01-16
### Fixed
- Potential bug if a purchasable was deleted.
- Version constraint preventing Craft 3.4 and Commerce 3.0 update.

## 1.0.1 - 2020-01-04
### Changed
- Redirect to plugin settings after plugin installation.

## 1.0.0 - 2019-12-30
### Added
- Initial release
