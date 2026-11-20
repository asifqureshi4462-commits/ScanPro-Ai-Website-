# ScanPro AI

**ScanPro AI** is a web-based PDF toolkit designed to make common document tasks simple and accessible. The project includes PDF utility pages and supporting account/backend components.


> **Project status:** Under development. Features and integrations may need live testing before production use.

## Features

- 19 PDF tool pages included in the project
- Browser-based document workflows
- User account pages and backend endpoints
- CSRF/security helper components and server configuration protections
- Interface intended for desktop and mobile browsers

**Note:** The presence of a tool page does not guarantee the feature has been fully tested in a live environment.

## Tech Stack

- PHP — backend and server-side pages/APIs
- JavaScript — browser-side functionality
- HTML and CSS — interface and styling
- MySQL — database, where configured
- External libraries/CDN dependencies for PDF processing

## Project Structure

The project is organized around an `htdocs` web root, including frontend pages and PDF tools, backend APIs/configuration, shared helpers and authentication components, CSS/JavaScript, static assets, and database-related files. Folder names may evolve as development continues.


## Setup & Deployment

1. Upload website files to your PHP hosting web root (commonly `htdocs` or `public_html`).
2. Create and configure a MySQL database if application features require it.
3. Configure database credentials in protected server-side configuration. **Never commit passwords, API keys, tokens, or secrets.**
4. Configure authentication/Firebase settings and a mail provider if those features are used.
5. Confirm your host supports the required PHP version and extensions.
6. Visit the deployed site and verify routes, assets, and server logs.


## Security

- Never commit database passwords, API keys, tokens, or private configuration.
- Rotate any credential that has been included in a shared ZIP or repository.
- Keep sensitive configuration outside the public web root where possible, or restrict direct access.
- Use HTTPS in production.


## Testing

A static audit reported:
- 64 PHP files: no syntax errors reported.
- 26 JavaScript files: no syntax errors detected.


These are syntax checks only—not live functional testing. Before production, test:
- Registration, login, logout, and session behavior
- Google/Firebase sign-in (if configured)
- Password-reset email delivery
- Contact form delivery
- Each PDF tool with sample documents
- Mobile layout, file-picker, and touch interactions
- CDN/library failure handling
- Downloaded output files and error states


## Known Limitations / Work in Progress

- Password-reset email requires mail configuration and end-to-end verification.
- PDF tools and account flows need live testing on the target host.
- PDF libraries may load from external CDN sources and fail if unavailable.
- The project is under active development and features may change.


## Bug Reports

When reporting a bug, include:
- Page or tool name
- Steps to reproduce
- Expected behavior
- Actual behavior
- Browser/device and operating system, when relevant


## License

No license has been specified. Until a license is added, all rights are reserved by the project owner by default.


---

**ScanPro AI — simpler PDF workflows.**
