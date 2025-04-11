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

## Example Flow

1. User visits `/admin/settings`
2. Not logged in → Redirect to `/?login=required&return=/admin/settings`
3. Index page loads with login modal
4. Successful login → Redirect back to `/admin/settings`

This maintains the exact same user experience as the current implementation, just with a more robust routing structure underneath.