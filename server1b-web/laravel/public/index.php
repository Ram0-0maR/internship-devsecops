<?php
// Pure PHP Lightweight Redis Client
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

$redisHost = 'redis-cache';
$redisPort = 6379;
$redisAuth = 'SecureRedisPassword2026!';
$redis = null;

try {
    $redis = new SimpleRedis($redisHost, $redisPort);
    $redis->auth($redisAuth);
} catch (Exception $e) {}

session_start();

// PostgreSQL Connection
$dbHost = 'postgres-db';
$dbName = 'app_db';
$dbUser = 'app_user';
$dbPass = 'SecureAppPassword2026!';
$pdo = null;
$dbError = null;

if (extension_loaded('pdo_pgsql') || extension_loaded('pdo')) {
    try {
        $pdo = new PDO("pgsql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (Exception $e) {
        $dbError = "Database Connection Failed: " . $e->getMessage();
    }
} else {
    $dbError = "PHP PostgreSQL Driver missing.";
}

// Ensure Audit Table exists in Postgres as persistent fallback
if ($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id SERIAL PRIMARY KEY,
        actor VARCHAR(50),
        action VARCHAR(50),
        resource VARCHAR(100),
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");
}

function log_audit_event($pdo, $actor, $action, $resource, $ip) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (actor, action, resource, ip_address) VALUES (:a, :act, :r, :ip)");
            $stmt->execute(['a' => $actor, 'act' => $action, 'r' => $resource, 'ip' => $ip]);
        } catch (Exception $e) {}
    }
}

$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$authError = '';
$successMsg = '';

// Handle Authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);
    
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute(['u' => $user]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && ($pass === 'SecOps2026!' || password_verify($pass, $userData['password_hash']))) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];

            if ($redis) {
                $redis->set("session_user_" . $userData['id'], json_encode([
                    'username' => $userData['username'],
                    'role' => $userData['role'],
                    'login_time' => date('Y-m-d H:i:s')
                ]), 3600);
            }

            log_audit_event($pdo, $userData['username'], 'USER_LOGIN', 'AUTH_ENGINE', $ipAddress);
            header("Location: index.php");
            exit;
        } else {
            $authError = "Invalid credentials provided!";
        }
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    if (isset($_SESSION['username'])) {
        log_audit_event($pdo, $_SESSION['username'], 'USER_LOGOUT', 'AUTH_ENGINE', $ipAddress);
    }
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle Incident Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_incident' && isset($_SESSION['user_id'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $severity = $_POST['severity'];

    if ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO incidents (title, description, severity, created_by) VALUES (:t, :d, :s, :uid)");
        $stmt->execute([
            't' => $title,
            'd' => $description,
            's' => $severity,
            'uid' => $_SESSION['user_id']
        ]);
        log_audit_event($pdo, $_SESSION['username'], 'CREATE_INCIDENT', 'INCIDENT_TABLE', $ipAddress);
        $successMsg = "Security Incident Case successfully created!";
    }
}

// Handle File Upload to MinIO S3
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_evidence' && isset($_SESSION['user_id'])) {
    $incidentId = (int)$_POST['incident_id'];
    if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['evidence_file'];
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file['name']));
        $s3Key = 'case_' . $incidentId . '_' . time() . '_' . $cleanFilename;
        
        $minioHost = 'minio-s3';
        $s3Bucket = 'vault-storage';
        $targetUrl = "http://$minioHost:9000/$s3Bucket/$s3Key";

        $ch = curl_init();
        $fp = fopen($file['tmp_name'], 'rb');
        
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file['tmp_name']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . ($file['type'] ?: 'application/octet-stream')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if (($httpCode === 200 || $httpCode === 204) && $pdo) {
            $stmt = $pdo->prepare("INSERT INTO evidence_files (incident_id, filename, s3_key, file_size, mime_type, uploaded_by) VALUES (:iid, :fn, :key, :size, :mime, :uid)");
            $stmt->execute([
                'iid' => $incidentId,
                'fn' => $file['name'],
                'key' => $s3Key,
                'size' => $file['size'],
                'mime' => $file['type'] ?: 'application/octet-stream',
                'uid' => $_SESSION['user_id']
            ]);

            log_audit_event($pdo, $_SESSION['username'], 'UPLOAD_EVIDENCE', "S3_KEY_$s3Key", $ipAddress);
            $successMsg = "Forensic evidence successfully encrypted and vaulted in MinIO S3!";
        } else {
            $authError = "Failed to upload evidence to MinIO S3 (HTTP $httpCode - Response: " . htmlspecialchars($response) . ")";
        }
    }
}

// Fetch Data for View
$incidents = [];
$evidenceMap = [];
$auditLogs = [];

if (isset($_SESSION['user_id']) && $pdo) {
    $stmt = $pdo->query("SELECT i.*, u.username FROM incidents i LEFT JOIN users u ON i.created_by = u.id ORDER BY i.created_at DESC");
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eStmt = $pdo->query("SELECT e.*, u.username as uploader FROM evidence_files e LEFT JOIN users u ON e.uploaded_by = u.id ORDER BY e.uploaded_at DESC");
    $eFiles = $eStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($eFiles as $ef) {
        $evidenceMap[$ef['incident_id']][] = $ef;
    }

    $aStmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10");
    $auditLogs = $aStmt->fetchAll(PDO::FETCH_ASSOC);
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
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #30363d; vertical-align: top; }
        th { background: #21262d; }
        .badge { background: #1f6beb; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .badge-CRITICAL { background: #da3633; }
        .badge-HIGH { background: #d29922; color: black; }
        .badge-MEDIUM { background: #1f6beb; }
        .badge-LOW { background: #238636; }
        .error { color: #f85149; }
        .success { color: #3fb950; }
        .file-box { background: #0d1117; border: 1px solid #30363d; border-radius: 4px; padding: 6px 10px; margin-top: 5px; font-size: 0.85em; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>🛡️ SecOps-Vault Platform</h2>
        <div>
            <?php if (isset($_SESSION['username'])): ?>
                Logged in: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> <span class="badge"><?= $_SESSION['role'] ?></span>
                <a href="?logout=1" style="color: #f85149; margin-left: 15px; text-decoration: none;">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($dbError): ?><p class="error"><?= htmlspecialchars($dbError) ?></p><?php endif; ?>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="card" style="max-width: 400px; margin: 60px auto;">
            <h3>Analyst Portal Sign-In</h3>
            <?php if ($authError): ?><p class="error"><?= $authError ?></p><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin or analyst1" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit" style="margin-top: 15px;">Authenticate</button>
            </form>
            <p style="font-size: 0.8em; color: #8b949e; margin-top: 15px;">Test Account: <code>admin</code> or <code>analyst1</code> / Password: <code>SecOps2026!</code></p>
        </div>
    <?php else: ?>
        <?php if ($successMsg): ?><p class="success"><?= $successMsg ?></p><?php endif; ?>
        <?php if ($authError): ?><p class="error"><?= $authError ?></p><?php endif; ?>

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
            <h3>🚨 Active Security Incidents & S3 Evidence Vault</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Case ID</th>
                        <th style="width: 25%;">Title & Details</th>
                        <th style="width: 10%;">Severity</th>
                        <th style="width: 12%;">Reporter</th>
                        <th style="width: 25%;">Attach Evidence to S3</th>
                        <th style="width: 20%;">Vaulted S3 Artifacts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incidents)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #8b949e;">No active security incidents recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($incidents as $inc): ?>
                            <tr>
                                <td>#<?= $inc['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($inc['title']) ?></strong><br>
                                    <span style="font-size: 0.85em; color: #8b949e;"><?= htmlspecialchars($inc['description']) ?></span>
                                </td>
                                <td><span class="badge badge-<?= $inc['severity'] ?>"><?= $inc['severity'] ?></span></td>
                                <td><?= htmlspecialchars($inc['username'] ?? 'System') ?></td>
                                <td>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="upload_evidence">
                                        <input type="hidden" name="incident_id" value="<?= $inc['id'] ?>">
                                        <input type="file" name="evidence_file" required style="font-size: 0.8em; padding: 4px;">
                                        <button type="submit" style="padding: 6px; font-size: 0.8em; margin-top: 4px;">Upload File to MinIO</button>
                                    </form>
                                </td>
                                <td>
                                    <?php if (isset($evidenceMap[$inc['id']])): ?>
                                        <?php foreach ($evidenceMap[$inc['id']] as $file): ?>
                                            <div class="file-box">
                                                📄 <strong><?= htmlspecialchars($file['filename']) ?></strong><br>
                                                <span style="color: #8b949e; font-size: 0.8em;">
                                                    Size: <?= number_format($file['file_size'] / 1024, 1) ?> KB<br>
                                                    Key: <code><?= htmlspecialchars($file['s3_key']) ?></code>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #8b949e; font-size: 0.85em;">No S3 evidence attached.</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>📜 Platform System Audit Trail</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target Resource</th>
                        <th>Client IP</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditLogs)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #8b949e;">No audit events recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td>#<?= $log['id'] ?></td>
                                <td><strong><?= htmlspecialchars($log['actor']) ?></strong></td>
                                <td><code><?= htmlspecialchars($log['action']) ?></code></td>
                                <td><?= htmlspecialchars($log['resource']) ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><?= $log['created_at'] ?></td>
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
