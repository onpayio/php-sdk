# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
