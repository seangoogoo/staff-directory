# Authentication Flow in New Router Implementation

## Admin Route Access Flow

1. User attempts to access `/admin/*`
2. FastRoute matches the admin route
3. Auth middleware checks `is_logged_in()`
4. If not logged in:
   - Sets `$_SESSION[LOGIN_MODAL_FLAG] = true`
   - Redirects to `/?login=required&return=/admin/original-path`
   - Login modal automatically appears on index page
5. After successful login:
   - User is redirected back to original admin URL
   - Auth middleware allows access

## Key Components Preserved

- Login modal trigger
- Return URL functionality
- Session-based authentication
- Seamless user experience

## Login Failure

`verify_login()` checks the submitted password with `password_verify()` against
`ADMIN_PASSWORD_HASH`; there is no cleartext form (see `authentication-system.md`).
The login endpoint therefore distinguishes two failures:

- wrong username or password → the generic `login_failed` message in the modal
- `ADMIN_PASSWORD_HASH` empty or absent from `.env` → a dedicated configuration
  message, since no credential can ever match and reporting bad credentials would
  hide the real cause. Fix it by generating a hash
  (`php -r 'echo password_hash("your-password", PASSWORD_DEFAULT), PHP_EOL;'`) or, on
  an installation still holding a cleartext `ADMIN_PASSWORD`, by running
  `php tools/hash_admin_password.php`

## Example Flow

1. User visits `/admin/settings`
2. Not logged in → Redirect to `/?login=required&return=/admin/settings`
3. Index page loads with login modal
4. Successful login → Redirect back to `/admin/settings`

This maintains the exact same user experience as the current implementation, just with a more robust routing structure underneath.