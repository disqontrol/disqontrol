# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [0.3.1] - 2017-01-11
### Fixed
- If a path in the config is relative and it doesn't exist, try creating it
instead of falling back to relative to CWD. That silent fallback is confusing.

## [0.3.0] - 2017-01-11
### Added
- The Disqontrol class now accepts a fourth argument in the constructor, a path
to the bootstrap file. This is needed for isolated PHP workers in synchronous
mode.

### Changed
- If paths in the configuration file are relative, they are no longer relative
to the current working directory, but relative to the configuration file

## [0.2.3] - 2017-01-04
### Fixed
- Fix a lot of small issues shown by Scrutinizer, mostly coding standards

### Changed
- Tighten the vendor version of disque-php (was: @dev)

## [0.2.2] - 2017-01-03
### Fixed
- Fix wrong check whether the user has registered all required PHP workers

### Changed
- Improve the initial experience by skipping the PHP worker check when just reading help

## [0.2.1] - 2017-01-02
### Added
- WorkerFactoryInterface can throw an exception if a worker is not supported (docblock change only)


### Fixed
- Bootstrap the worker environment only after we're sure the worker factory exists

## [0.2.0] - 2016-12-30
### Changed
- WorkerFactoryInterface::create() receives a second argument, the worker name

## [0.1.0] - 2016-12-27
Initial public release

### Added
- PHP and command-line workers
- Regular, repeated, planned jobs
- Synchronous mode for debugging
- Adding jobs via PHP or the command line

### Remarks
- Hasn't been used in production yet
- HTTP workers are mentioned in the docs, but not implemented yet
