# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.29] - 2024-03-04
- Added Klarna method

## [1.0.28] - 2023-12-20
- Added user agent to get requests as well

## [1.0.27] - 2023-12-20
- Added custom user agent to header of API calls, and let window platform inherit this value if not otherwise set (#82)

## [1.0.26] - 2023-09-20
- Add transaction creation from subscription flag in payment creation trough API

## [1.0.25] - 2023-02-22
- Updated readme with info about htmlentity escaping field values.
- Added check for negative values in paymentWindow Cart object.
- Allow whitespace and hyphen characters in PostalCode fields in PaymentInfo object.

## [1.0.24] - 2022-08-17
- Added payment service method to API class
- Added typing of certain parameters that are historically less strict, when creating payments

## [1.0.23] - 2022-07-25
- Added PayPal method support (#72)
- Added sending cart data support (#72)

## [1.0.22] - 2022-05-30
- Introduced payment creation (#60)
- Fixed OAUTH2 codeverifier not being set (#65)
- Added helper functions for currency/method validation (#64, #70)
- Ensure HMAC value being set in payment window (#66)
- Added better handling of unexpected API errors (#67)

## [1.0.21] - 2022-03-31
- Introduce Currency classes (#59, #62, #63)
- Fixed broken PHP doc (#61)
- Fixed potential PHP 8.0 problem  (#58)

## [1.0.20] - 2021-12-07
- Added Swish

## [1.0.19] - 2021-05-05
- Added Vipps

## [1.0.18] - 2021-03-23
- Fixed bug with no links being available when getting transactions
- Added Apple pay and Google pay as methods available for payment window.
- Added testmode flag to transactions and subscriptions.

## [1.0.17] - 2021-03-02
- Added expiration field to payment window.
- Throw an ApiException with a meaningful error message when response json body fails to decode.

## [1.0.16] - 2021-01-28
- When no redirect_uri value is sent to OnPayAPI, add an empty value to the option.

## [1.0.15] - 2021-01-27
- Removed void typehinting on paymentwindow setPlatform method

## [1.0.14] - 2021-01-26
- Added platform field to payment window.

## [1.0.13] - 2021-01-20
- Added static token object for use with static API tokens from OnPay.

## [1.0.12] - 2021-01-12
- Added Anyday Split as available method in payment window.

## [1.0.11] - 2020-12-03
- Introduced onpay_website field for payment window.
- Added exception message for invalid OAUTH2 token.

## [1.0.10] - 2020-10-21
- Handle if oauth2 token format is completely broken.
- Implementing new flag for signaling a subscription should create a transaction.

## [1.0.9] - 2020-10-05
- Fixed bug with the latest version of OAuth2 client library.

## [1.0.8] - 2020-09-30
- Added address fields to CardholderData class.
- Removed amount as required field in PaymentWindow class.
- Added ability to disable delivery with different reasons in PaymentWindow class. Specifically for use with MobilePay Checkout.
- Fixed broken dependencies.

## [1.0.7] - 2020-04-28
- Fixed cardholderData being set to hasCardholderData instead of cardholderData on detailedTransaction (PR #28)

## [1.0.6] - 2020-04-01
- Implemented the PaymentInfo object (PR #22)
- Updated SDK to correspond correctly with API (PR #23)
- Properly fetch requests and responses to/from API for logging purposes (PR #23)
- Check that gateway_id is numeric value when constructing OnPayAPI. (Issue #16) (PR #23)

## [1.0.5] - 2020-02-26
- Set last HTTP response/request methods less strict with param values. Sets empty value if supplied with invalid value.

## [1.0.4] - 2020-02-25
- Removed unused includes in OnPayAPI class (PR #20)
- Handle TokenException thrown by fkooman repo (PR #20)
- Added methods for getting latest http request and responses (PR #20)

## [1.0.3] - 2019-10-18
- Fixed transaction and subscription lists API paths
- Fixed bug with HMAC calculations (PR #19)
- Implement MobilePay checkout (PR #18)

## [1.0.2] - 2019-09-19
- Changed OAUTH2 client implementation (PR #15)

## [1.0.1] - 2019-09-13
- Fixed bug with forcing 3D-Secure (PR #14)

## [1.0.0] - 2019-05-28
- First stable release
