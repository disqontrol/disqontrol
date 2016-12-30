# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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
