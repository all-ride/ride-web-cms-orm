# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).
## [1.7.0] - 2024-06-26
### Updated
- Updated to be compatible with php 8.3
## [1.6.0] - 2023-06-02
### Updated
- PHP 8.1 Compatibility

## [1.5.0] - 2019-01-23
### Added
- added description and image field to entry node

## [1.4.4] - 2018-11-14
### Updated
- added locale for js call to retrieve the entries

## [1.4.3] - 2018-11-14
### Updated
- fixed creation of entry node without entering a name

## [1.4.2] - 2018-03-20
### Updated
- made variables and methods protected in ContentOverviewWidget to open the widget up for extension

## [1.4.1] - 2017-04-26
### Updated
- order contact submissions by date added descending

## [1.4.0] - 2017-04-13
### Added
- widget property to disable behaviour processor

## [1.3.2] - 2017-04-11
### Updated
- fixed distinct for generic content mapper

## [1.3.1] - 2017-04-07
### Updated
- content mappers will set distinct to true when performing a search

## [1.3.0] - 2017-03-01
### Added
- implemented behaviour processors with publish as first implementation

## [1.2.5] - 2017-02-24
### Added
- add dateAdded to contact teaser

## [1.2.4] - 2017-02-16
### Updated
- fixed unconfigured data entry properties with limited permissions

## [1.2.3] - 2017-02-08
### Updated
- fixed infinite loop when requesting content mapper

## [1.2.2] - 2017-02-07
### Updated
- use the content mapper from the content facade when nog specific mapper is requested

## [1.2.1] - 2017-02-07
### Added
- check for localized entry

## [1.2.0] - 2017-01-24
### Added
- support for ride-web-cms-widgets 2.0
### Updated
- improved localized handling of the content mapper

## [1.1.0] - 2016-11-15
### Added
- implemented content mapper choice for a content entry widget

## [1.0.0]
### Updated
- composer.json for first stable release
