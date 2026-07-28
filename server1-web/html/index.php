<?php
class SimpleRedis {
    private $handle;
    public function __construct($host, $port = 6379, $timeout = 2.5) {
        $this->handle = @fsockopen($host, $port, $errno, $errstr, $timeout);
    }
    public function auth($password) {
        if (!$this->handle) return;
        fwrite($this->handle, "*2\r\n$4\r\nAUTH\r\n$" . strlen($password) . "\r\n$password\r\n");
        return fgets($this->handle);
    }
    public function set($key, $value, $ttl = null) {
        if (!$this->handle) return;
        if ($ttl) {
            fwrite($this->handle, "*5\r\n$3\r\nSET\r\n$" . strlen($key) . "\r\n$key\r\n$" . strlen($value) . "\r\n$value\r\n$2\r\nEX\r\n$" . strlen((string)$ttl) . "\r\n$ttl\r\n");
        } else {
            fwrite($this->handle, "*3\r\n$3\r\nSET\r\n$" . strlen($key) . "\r\n$key\r\n$" . strlen($value) . "\r\n$value\r\n");
        }
        return fgets($this->handle);
    }
}

$redisHost = "redis-cache";
$redisPort = 6379;
$redisAuth = "SecureRedisPassword2026!";
$redis = null;

try {
    $redis = new SimpleRedis($redisHost, $redisPort);
    $redis->auth($redisAuth);
} catch (Exception $e) {}

session_start();

$dbHost = "postgres-db";
$dbName = "app_db";
$dbUser = "app_user";
$dbPass = "SecureAppPassword2026!";

try {
    $pdo = new PDO("pgsql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("Database Tier Connection Failure: " . $e->getMessage());
}

function log_audit_event($actor, $action, $resource, $ip) {
    $cmd = sprintf(
        "docker exec -i cassandra-db cqlsh -e \"USE ips_security; INSERT INTO system_audit_trail (event_id, actor_username, action, resource_type, ip_address, timestamp) VALUES (uuid(), '\''%s'\'', '\''%s'\'', '\''%s'\'', '\''%s'\'', toTimestamp(now()));\"",
        addslashes($actor), addslashes($action), addslashes($resource), addslashes($ip)
    );
    exec($cmd . " > /dev/null 2>&1 &");
}

$ipAddress = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
$authError = "";
$successMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "login") {
    $user = trim($_POST["username"]);
    $pass = trim($_POST["password"]);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->execute(["u" => $user]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData && ($pass === "SecOps2026!" || password_verify($pass, $userData["password_hash"]))) {
        $_SESSION["user_id"] = $userData["id"];
        $_SESSION["username"] = $userData["username"];
        $_SESSION["role"] = $userData["role"];

        if ($redis) {
            $redis->set("session_user_" . $userData["id"], json_encode([
                "username" => $userData["username"],
                "role" => $userData["role"],
                "login_time" => date("Y-m-d H:i:s")
            ]), 3600);
        }

        log_audit_event($userData["username"], "USER_LOGIN", "AUTH_ENGINE", $ipAddress);

        header("Location: index.php");
        exit;
    } else {
        $authError = "Invalid credentials provided!";
    }
}

if (isset($_GET["logout"])) {
    if (isset($_SESSION["username"])) {
        log_audit_event($_SESSION["username"], "USER_LOGOUT", "AUTH_ENGINE", $ipAddress);
    }
    session_destroy();
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_incident" && isset($_SESSION["user_id"])) {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $severity = $_POST["severity"];

    $stmt = $pdo->prepare("INSERT INTO incidents (title, description, severity, created_by) VALUES (:t, :d, :s, :uid)");
    $stmt->execute([
        "t" => $title,
        "d" => $description,
        "s" => $severity,
        "uid" => $_SESSION["user_id"]
    ]);

    log_audit_event($_SESSION["username"], "CREATE_INCIDENT", "INCIDENT_TABLE", $ipAddress);
    $successMsg = "Security Incident Case successfully created!";
}

$incidents = [];
if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->query("SELECT i.*, u.username FROM incidents i LEFT JOIN users u ON i.created_by = u.id ORDER BY i.created_at DESC");
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecOps-Vault Platform</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0d1117; color: #c9d1d9; margin: 0; padding: 25px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #30363d; padding-bottom: 15px; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 20px; margin-top: 20px; }
        input, select, textarea, button { width: 100%; padding: 10px; margin: 6px 0; border-radius: 6px; border: 1px solid #30363d; background: #0d1117; color: white; box-sizing: border-box; }
        button { background: #238636; cursor: pointer; font-weight: bold; border: none; }
        button:hover { background: #2ea043; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #30363d; }
        th { background: #21262d; }
        .badge { background: #1f6beb; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .badge-CRITICAL { background: #da3633; }
        .badge-HIGH { background: #d29922; color: black; }
        .badge-MEDIUM { background: #1f6beb; }
        .badge-LOW { background: #238636; }
        .error { color: #f85149; }
        .success { color: #3fb950; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>🛡️ SecOps-Vault Platform</h2>
        <div>
            <?php if (isset($_SESSION["username"])): ?>
                Logged in: <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong> <span class="badge"><?= $_SESSION["role"] ?></span>
                <a href="?logout=1" style="color: #f85149; margin-left: 15px; text-decoration: none;">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!isset($_SESSION["user_id"])): ?>
        <div class="card" style="max-width: 400px; margin: 60px auto;">
            <h3>Analyst Portal Sign-In</h3>
            <?php if ($authError): ?><p class="error"><?= $authError ?></p><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <label>Username</label>
                <input type="text" name="username" placeholder="e.g. admin or analyst1" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit" style="margin-top: 15px;">Authenticate</button>
            </form>
            <p style="font-size: 0.8em; color: #8b949e; margin-top: 15px;">Test Account: <code>admin</code> or <code>analyst1</code> / Password: <code>SecOps2026!</code></p>
        </div>
    <?php else: ?>
        <?php if ($successMsg): ?><p class="success"><?= $successMsg ?></p><?php endif; ?>

        <div class="card">
            <h3>📝 Open New Incident Case</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_incident">
                <label>Incident Title</label>
                <input type="text" name="title" placeholder="e.g., Suspicious Outbound Connection on Port 4444" required>
                <label>Severity Level</label>
                <select name="severity">
                    <option value="LOW">LOW</option>
                    <option value="MEDIUM" selected>MEDIUM</option>
                    <option value="HIGH">HIGH</option>
                    <option value="CRITICAL">CRITICAL</option>
                </select>
                <label>Case Details & Telemetry Notes</label>
                <textarea name="description" rows="3" placeholder="Paste relevant IP, payload, or log context..." required></textarea>
                <button type="submit">Submit Incident Case</button>
            </form>
        </div>

        <div class="card">
            <h3>🚨 Active Security Incidents</h3>
            <table>
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Reporter</th>
                        <th>Opened At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incidents)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #8b949e;">No active security incidents recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($incidents as $inc): ?>
                            <tr>
                                <td>#<?= $inc["id"] ?></td>
                                <td><strong><?= htmlspecialchars($inc["title"]) ?></strong></td>
                                <td><span class="badge badge-<?= $inc["severity"] ?>"><?= $inc["severity"] ?></span></td>
                                <td><code><?= $inc["status"] ?></code></td>
                                <td><?= htmlspecialchars($inc["username"] ?? "System") ?></td>
                                <td><?= $inc["created_at"] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
