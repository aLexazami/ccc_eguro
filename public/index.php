<?php
# ============================================================================================================================================
# APPLICATION FRONT CONTROLLER & ROUTER
# Handles URL routing, authentication checks, role-based access control, and dynamic parameter parsing for all incoming requests.

# Set base path directory constant
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));

# Load core configurations, session, database, and functions
require_once DOMAIN_PATH . '/config/config.php';
require_once CL_SESSION_PATH;
require_once CONNECT_PATH;
require_once GLOBAL_FUNC;

# Retrieve active user session variables
$g_system_role = $session_class->getValue('system_role');
$g_user_role   = $session_class->getValue('user_role');

# Parse requested URL path
$request_uri = $_SERVER['REQUEST_URI'];
$clean_path  = explode('?', $request_uri)[0];

# Normalize path relative to base directory
$script_base = parse_url(BASE_URL, PHP_URL_PATH);
if ($script_base !== '/' && strpos($clean_path, $script_base) === 0) {
	$clean_path = substr($clean_path, strlen($script_base));
}

$route = '/' . trim($clean_path, '/');
$route = strtolower($route);

# ======================================================================
# CORE STATIC ROUTES
switch ($route) {
	case '/':
	case '/login':
	case '/index':
	case '/index.php':
		if (!empty($g_system_role)) {
			header("Location: " . BASE_URL . "home");
			exit();
		}
		require_once SRC_PATH . 'Views/app/login.php';
		exit();

	case '/login-process':
		require_once SRC_PATH . 'Handlers/app/login_process.php';
		exit();

	case '/logout':
		require_once SRC_PATH . 'Handlers/app/logout_process.php';
		exit();

	case '/reset-password':
		require_once SRC_PATH . 'Handlers/app/reset_password.php';
		exit();

	case '/home':
		if (empty($g_system_role)) {
			header("Location: " . BASE_URL);
			exit();
		}
		require_once SRC_PATH . 'Views/app/home.php';
		exit();

	case '/profile':
		if (empty($g_system_role)) {
			header("Location: " . BASE_URL);
			exit();
		}
		require_once SRC_PATH . 'Views/app/profile.php';
		exit();

	case '/ajax/account-process':
		require_once SRC_PATH . 'Handlers/app/account_process.php';
		exit();

	case '/system-login':
		require_once SRC_PATH . 'Handlers/app/system_login_process.php';
		exit();

		# Block access to sensitive system directories
	case '/config':
	case '/src':
	case '/env':
	case '/global':
	case '/error_page':
		http_response_code(403);
		require HTTP_404;
		exit();

		# ======================================================================
		# CUSTOM DYNAMIC ROUTES & ACCESS MAP
	default:
		# Pass-through static asset files (CSS, JS, Images)
		if (file_exists(__DIR__ . $route) && is_file(__DIR__ . $route)) {
			return false;
		}

		# Define application custom routes, target view/handler, and role access permissions
		$custom_routes = [
			# Public routes
			'/digital-profile/{slug}' => [
				'file'  => SRC_PATH . 'Views/app/digital_profile.php',
				'roles' => [] # Public access
			],

			# Admin views
			'/user-information'        => ['file' => SRC_PATH . 'Views/admin/user_information.php', 'roles' => ['ADMIN']],
			'/employee-information'    => ['file' => SRC_PATH . 'Views/admin/employee_information.php', 'roles' => ['ADMIN']],
			'/student-information'     => ['file' => SRC_PATH . 'Views/admin/student_information.php', 'roles' => ['ADMIN']],
			'/employee-system-access'  => ['file' => SRC_PATH . 'Views/admin/employee_system_access.php', 'roles' => ['ADMIN']],
			'/student-system-access'   => ['file' => SRC_PATH . 'Views/admin/student_system_access.php', 'roles' => ['ADMIN']],
			'/activity-log'            => ['file' => SRC_PATH . 'Views/admin/activity_log.php', 'roles' => ['ADMIN']],
			'/user-management'         => ['file' => SRC_PATH . 'Views/admin/user_management.php', 'roles' => ['ADMIN']],
			'/dashboard'               => ['file' => (isset($g_user_role) && $g_user_role === 'ADMIN') ? SRC_PATH . 'Views/admin/dashboard.php' : SRC_PATH . 'Views/staff/dashboard.php', 'roles' => ['ADMIN', 'STAFF']],

			# AJAX / Handlers
			'/ajax/user-information-process'       => ['file' => SRC_PATH . 'Handlers/admin/user_information_process.php', 'roles' => ['ADMIN']],
			'/ajax/employee-information-process'   => ['file' => SRC_PATH . 'Handlers/admin/employee_information_process.php', 'roles' => ['ADMIN']],
			'/ajax/employee-system-access-process' => ['file' => SRC_PATH . 'Handlers/admin/employee_system_access_process.php', 'roles' => ['ADMIN']],

			# Bulk actions
			'/ajax/user-information-bulk'       => ['file' => SRC_PATH . 'Handlers/admin/user_information_bulk.php', 'roles' => ['ADMIN']],
			'/ajax/employee-information-bulk'   => ['file' => SRC_PATH . 'Handlers/admin/employee_information_bulk.php', 'roles' => ['ADMIN']],
			'/ajax/employee-system-access-bulk' => ['file' => SRC_PATH . 'Handlers/admin/employee_system_access_bulk.php', 'roles' => ['ADMIN']],

			# Data tables
			'/table/user-information-table'       => ['file' => SRC_PATH . 'Handlers/table/user_information_table.php', 'roles' => ['ADMIN']],
			'/table/employee-information-table'   => ['file' => SRC_PATH . 'Handlers/table/employee_information_table.php', 'roles' => ['ADMIN']],
			'/table/student-information-table'    => ['file' => SRC_PATH . 'Handlers/table/student_information_table.php', 'roles' => ['ADMIN']],
			'/table/employee-system-access-table' => ['file' => SRC_PATH . 'Handlers/table/employee_system_access_table.php', 'roles' => ['ADMIN']],

			# Downloads
			'/download' => ['file' => SRC_PATH . 'Handlers/download.php', 'roles' => ['ADMIN']],

			# Shared modules
			'/student/records'             => ['file' => SRC_PATH . 'Views/shared/student_records.php', 'roles' => ['ADMIN', 'STAFF']],
			'/api/inventory/save'          => ['file' => SRC_PATH . 'Handlers/shared/save_inventory.php', 'roles' => ['ADMIN', 'STAFF']],
			'/api/user/delete'             => ['file' => SRC_PATH . 'Handlers/admin/delete_user.php', 'roles' => ['ADMIN']],
			'/api/student/update-profile'  => ['file' => SRC_PATH . 'Handlers/student/update_profile.php', 'roles' => ['ADMIN', 'STUDENT']]
		];

		# Dynamic Route Matching Engine
		$matched_route_info = null;
		$route_params = [];

		foreach ($custom_routes as $defined_route => $info) {
			$defined_route_lower = strtolower($defined_route);

			# Build regex pattern to support parameterized routes like {slug}
			$pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $defined_route_lower);
			$pattern = '#^' . $pattern . '$#';

			if (preg_match($pattern, $route, $matches)) {
				$matched_route_info = $info;

				# Extract wildcard dynamic parameters
				preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $defined_route_lower, $param_names);
				foreach ($param_names[1] as $index => $name) {
					if (isset($matches[$index + 1])) {
						$route_params[$name] = $matches[$index + 1];
					}
				}
				break;
			}
		}

		# Dynamic Route Authorization and Execution
		if ($matched_route_info) {
			# Validate Role-Based Access Control (RBAC)
			if (!empty($matched_route_info['roles'])) {
				$user_role_upper = !empty($g_user_role) ? strtoupper($g_user_role) : '';
				$allowed_roles_upper = array_map('strtoupper', $matched_route_info['roles']);

				if (empty($g_system_role) || !in_array($user_role_upper, $allowed_roles_upper)) {
					# Handle forbidden responses for API vs standard pages
					if (strpos($route, '/api/') === 0 || strpos($route, '/ajax/') === 0) {
						http_response_code(403);
						header('Content-Type: application/json');
						echo json_encode(['success' => false, 'error' => 'Forbidden: Insufficient privileges.']);
					} else {
						http_response_code(403);
						require HTTP_404;
					}
					exit();
				}
			}

			# Inject route parameters into global request scope
			$_ROUTE_PARAMS = $route_params;
			foreach ($route_params as $key => $val) {
				$_GET[$key] = $val;
			}

			$page_add_title = !empty($g_user_role) ? "[" . str_replace('_', '', $g_user_role) . "]" : "";

			# Serve target route handler or view
			if (file_exists($matched_route_info['file'])) {
				require_once $matched_route_info['file'];
				exit();
			}
		}

		# Fallback 404 handler
		http_response_code(404);
		require HTTP_404;
		exit();
}
