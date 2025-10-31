# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - $(date +"%Y-%m-%d")

### Added
- Initial release
- Azure AD/Entra SSO authentication
- Automatic user creation
- Group synchronization
- Role mapping
- Token refresh for long sessions
- Custom claims handling
- CSRF protection with state validation
- Support for multiple Laravel instances
- Comprehensive documentation

### Features
- Role-based middleware (`entra.role`)
- Group-based middleware (`entra.group`)
- Automatic token refresh middleware
- Configurable via environment variables
- Compatible with Laravel 12
- Support for Spatie Laravel-Permission
- Custom claims mapping
- JSON storage for additional claims

### Documentation
- Installation guide
- Azure AD setup guide
- Custom claims configuration
- Usage examples
- Troubleshooting tips
