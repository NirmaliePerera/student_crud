<?php
// config.php - Database connection and owner resolution logic
// Database connection parameters for localhost using a server
// Rename as config.php everytime using and use the correct server credentials

$database_cnn = "";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "server_name";
$password = "password_if_any";
$dbname = "db_name";

// Create connection

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!function_exists('resolveOwner')) {
    function resolveOwner(): string
    {
        static $cachedOwner = null;

        if ($cachedOwner !== null) {
            return $cachedOwner;
        }

        $owner = $_SESSION['createdBy'] ?? '';

        if (empty($owner) && !empty($_GET['createdBy'])) {
            $owner = $_GET['createdBy'];
        }

        if (empty($owner)) {
            $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
            $requestPath = trim($requestPath, '/');
            $segments = $requestPath === '' ? [] : explode('/', $requestPath);

            $scriptDir = trim(dirname($_SERVER['PHP_SELF'] ?? ''), '/');
            if ($scriptDir !== '' && $scriptDir !== '.') {
                $baseSegments = explode('/', $scriptDir);
                while (!empty($baseSegments) && !empty($segments) && $baseSegments[0] === $segments[0]) {
                    array_shift($baseSegments);
                    array_shift($segments);
                }
            }

            if (!empty($segments)) {
                $candidate = $segments[0];
                if (strpos($candidate, '.php') === false) {
                    $owner = $candidate;
                }
            }
        }

        if (empty($owner)) {
            $scriptDir = trim(dirname($_SERVER['PHP_SELF'] ?? ''), '/');
            if ($scriptDir !== '' && $scriptDir !== '.') {
                $dirSegments = explode('/', $scriptDir);
                $ownerCandidate = end($dirSegments);
                if (!empty($ownerCandidate)) {
                    $owner = $ownerCandidate;
                }
            }
        }

        if (!empty($owner)) {
            $_SESSION['createdBy'] = $owner;
        }

        $cachedOwner = $owner;
        return $cachedOwner;
    }
}

?>
