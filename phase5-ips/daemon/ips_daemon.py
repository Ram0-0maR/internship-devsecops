#!/usr/bin/env python3
import time
import re
import os
import subprocess
from cassandra.cluster import Cluster

# Connect to Cassandra
cluster = Cluster(['127.0.0.1'], port=9042)
session = cluster.connect('ips_security')

# Threat Signatures (Regex)
PATTERNS = {
    'SQL_INJECTION': re.compile(r"(?i)(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|OR\s+1=1|--|\')"),
    'PATH_TRAVERSAL': re.compile(r"(\.\./\.\.|/etc/passwd|\.\.\/)"),
    'XSS_ATTACK': re.compile(r"(?i)(<script>|javascript:|onerror=)")
}

THREAT_THRESHOLD = 30  # Ban IP when threat score >= 30

def ban_ip_nftables(ip):
    """Enforce a kernel-level drop rule via nftables"""
    print(f"[!] ENFORCING KERNEL BAN ON IP: {ip}")
    try:
        subprocess.run(["sudo", "nft", "add", "element", "inet", "ips_filter", "banned_ips", f"{{ {ip} }}"], check=True)
    except Exception as e:
        print(f"[-] Failed to execute nftables ban: {e}")

def record_attack(ip, attack_type, path):
    """Update Cassandra threat scores and logs"""
    session.execute("""
        INSERT INTO attack_logs (event_id, source_ip, attack_type, uri_path, timestamp)
        VALUES (uuid(), %s, %s, %s, toTimestamp(now()))
    """, (ip, attack_type, path))

    # Fetch existing score
    res = session.execute("SELECT threat_score FROM ip_threat_scores WHERE source_ip=%s", (ip,))
    current_score = res[0].threat_score if res else 0
    new_score = current_score + 10

    banned = new_score >= THREAT_THRESHOLD
    session.execute("""
        INSERT INTO ip_threat_scores (source_ip, threat_score, banned, last_attack_type, updated_at)
        VALUES (%s, %s, %s, %s, toTimestamp(now()))
    """, (ip, new_score, banned, attack_type))

    print(f"[+] Attack Detected: {attack_type} from {ip} | New Threat Score: {new_score}")

    if banned:
        ban_ip_nftables(ip)

if __name__ == "__main__":
    print("[*] IPS Daemon operational. Monitoring security events...")
