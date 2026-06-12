# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-06-12

### Added

* Initial public release.
* Login session handling for Hisfocus 2 PON OLT web interface.
* ONU list retrieval from configured PON interfaces.
* Search ONU by name, ONU ID, and MAC address.
* Get ONU detail by ONU ID.
* Rename ONU.
* Reboot ONU by ONU ID and ONU name.
* Reboot ONU by keyword search.
* Manual GET request method.
* Manual POST request method.
* Configurable timeout.
* Configurable OLT name.
* Configurable PON interface list.
* HTTP/HTTPS scheme selection.
* JSON-friendly ONU data structure.

### Tested On

* OLT Hisfocus 2 PON
* Software Version: v7.68
* Revision: Release20220825

### Notes

* Rename and reboot operations may return HTTP 302 on successful execution.
* Compatibility with other firmware versions has not been fully tested.
* Contributions and testing reports are welcome.
