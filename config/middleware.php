<?php
/**
 * Middleware Functions for Access Control
 * Provides page-level and API-level permission checking
 */

// Prevent multiple inclusions
if ( ! defined ( 'MIDDLEWARE_INCLUDED' )) {
    define ( 'MIDDLEWARE_INCLUDED', true );

    // Include helper functions if not already loaded
    if ( ! defined ( 'HELPER_INCLUDED' )) {
        require_once __DIR__ . '/helper.php';
        }

    /**
     * Check if user has permission to access a page
     * Redirects with toaster message if access is denied
     * 
     * @param string $page_prefix The page prefix (e.g., 'master-lead')
     * @param string $action The action required ('is_view', 'is_add', 'is_edit', 'is_delete')
     * @param bool $redirect Whether to redirect on failure (default: true)
     * @return bool True if has permission, false otherwise
     */
    function check_page_access ($page_prefix, $action = 'is_view', $redirect = true)
        {
        global $role_id;

        // Admin always has access
        if ((int) $role_id === 1) {
            return true;
            }

        // Check permission
        $hasPermission = get_permission ( $page_prefix, $action );

        if ( ! $hasPermission && $redirect) {
            // Set session message for toaster
            $_SESSION[ 'access_denied' ]         = true;
            $_SESSION[ 'access_denied_message' ] = 'You do not have permission to access this page';

            // Redirect to previous page or index
            $redirect_url = $_SERVER[ 'HTTP_REFERER' ] ?? 'index.php';
            header ( "Location: $redirect_url" );
            exit ();
            }

        return $hasPermission;
        }

    /**
     * Require specific permission for a page
     * Shows access denied and redirects if user doesn't have permission
     * 
     * @param string $page_prefix The page prefix (e.g., 'master-lead')
     * @param string $action The action required ('is_view', 'is_add', 'is_edit', 'is_delete')
     */
    function require_permission ($page_prefix, $action = 'is_view')
        {
        if ( ! check_page_access ( $page_prefix, $action, true )) {
            exit ();
            }
        }

    /**
     * Check permission for API calls
     * Returns JSON response if access is denied
     * 
     * @param string $page_prefix The page prefix (e.g., 'master-lead')
     * @param string $action The action required ('is_add', 'is_edit', 'is_delete')
     */
    function require_api_permission ($page_prefix, $action)
        {
        global $role_id;

        // Admin always has access
        if ((int) $role_id === 1) {
            return true;
            }

        // Check permission
        if ( ! get_permission ( $page_prefix, $action )) {
            http_response_code ( 403 );
            echo json_encode ( [
                'status' => 'error',
                'message' => 'Access Denied: You do not have permission to perform this action'
            ] );
            exit ();
            }

        return true;
        }

    /**
     * Check if user can view a specific page
     * 
     * @param string $page_prefix The page prefix
     * @return bool
     */
    function can_view ($page_prefix)
        {
        return check_page_access ( $page_prefix, 'is_view', false );
        }

    /**
     * Check if user can add/create
     * 
     * @param string $page_prefix The page prefix
     * @return bool
     */
    function can_add ($page_prefix)
        {
        return check_page_access ( $page_prefix, 'is_add', false );
        }

    /**
     * Check if user can edit/update
     * 
     * @param string $page_prefix The page prefix
     * @return bool
     */
    function can_edit ($page_prefix)
        {
        return check_page_access ( $page_prefix, 'is_edit', false );
        }

    /**
     * Check if user can delete
     * 
     * @param string $page_prefix The page prefix
     * @return bool
     */
    function can_delete ($page_prefix)
        {
        return check_page_access ( $page_prefix, 'is_delete', false );
        }

    /**
     * Check if user has any access (view, add, edit, or delete) for a page – used for menu visibility.
     * Show menu item if any of these is enabled for that permission.
     *
     * @param string $page_prefix The page prefix
     * @return bool
     */
    function can_access_any ($page_prefix)
        {
        return can_view ( $page_prefix ) || can_add ( $page_prefix ) || can_edit ( $page_prefix ) || can_delete ( $page_prefix );
        }

    /**
     * Show access denied toaster message if set in session
     * Call this in header.php after jQuery is loaded
     */
    function show_access_denied_toaster ()
        {
        if (isset ($_SESSION[ 'access_denied' ]) && $_SESSION[ 'access_denied' ]) {
            $message = $_SESSION[ 'access_denied_message' ] ?? 'Access Denied';
            echo "<script>
                $(document).ready(function() {
                    showtoastt('" . addslashes ( $message ) . "', 'error');
                });
            </script>";

            // Clear the session variables
            unset ( $_SESSION[ 'access_denied' ] );
            unset ( $_SESSION[ 'access_denied_message' ] );
            }
        }
    }
