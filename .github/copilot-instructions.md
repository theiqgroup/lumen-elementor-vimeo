# WordPress Plugin Instructions

## Coding
- Follow WordPress Coding Standards
- Prefer WordPress APIs
- Use the plugin version constant consistently.

## Security
- Sanitize all input.
- Escape all output.
- Use nonces for forms and AJAX/REST actions.
- Restrict mutating actions to authorized users.
- Use `$wpdb->prepare()` for database queries.
- Do not expose secrets or environment-specific config.
