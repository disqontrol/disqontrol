# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
- Fixed wrong check whether the user has registered all required PHP workers

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
