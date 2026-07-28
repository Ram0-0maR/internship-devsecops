-- Clean up old test tables if present
DROP TABLE IF EXISTS evidence_files CASCADE;
DROP TABLE IF EXISTS incidents CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. Users & RBAC Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'Analyst', -- Admin, Analyst, Auditor
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Incident Cases Table
CREATE TABLE incidents (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    severity VARCHAR(20) NOT NULL CHECK (severity IN ('LOW', 'MEDIUM', 'HIGH', 'CRITICAL')),
    status VARCHAR(20) NOT NULL DEFAULT 'OPEN' CHECK (status IN ('OPEN', 'INVESTIGATING', 'RESOLVED', 'CLOSED')),
    created_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Evidence Files Metadata Table (Objects reside in MinIO S3)
CREATE TABLE evidence_files (
    id SERIAL PRIMARY KEY,
    incident_id INT REFERENCES incidents(id) ON DELETE CASCADE,
    filename VARCHAR(255) NOT NULL,
    s3_key VARCHAR(255) UNIQUE NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT REFERENCES users(id) ON DELETE SET NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Default Test Accounts
-- Password for both accounts is: SecOps2026!
INSERT INTO users (username, password_hash, role) 
VALUES 
  ('admin', '$2y$10$y58o98S2K6Lz8cE3X5w1neG/1J0gVpB.G.9L3Z7wQ4v2K.3V2G1H0', 'Admin'),
  ('analyst1', '$2y$10$y58o98S2K6Lz8cE3X5w1neG/1J0gVpB.G.9L3Z7wQ4v2K.3V2G1H0', 'Analyst')
ON CONFLICT (username) DO NOTHING;
