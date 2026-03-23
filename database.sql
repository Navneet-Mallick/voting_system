-- ============================================================
--  ONLINE VOTING SYSTEM DATABASE
--  Normalized to 3NF (Third Normal Form)
-- ============================================================

DROP DATABASE IF EXISTS voting_system;
CREATE DATABASE voting_system;
USE voting_system;

-- ============================================================
--  TABLE 1: roles  (1NF → 3NF)
--  Stores user roles to avoid repeating role names in users
-- ============================================================
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS elections;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS parties;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    role_id   INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE   -- 'admin', 'voter'
);

INSERT INTO roles (role_name) VALUES ('admin'), ('voter');

-- ============================================================
--  TABLE 2: users  (3NF)
--  role_id → role_name  moved out to `roles` table (no transitive dependency)
-- ============================================================
CREATE TABLE users (
    user_id       INT PRIMARY KEY AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id       INT NOT NULL DEFAULT 2,            -- default: voter
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- ============================================================
--  TABLE 3: elections  (3NF)
-- ============================================================
CREATE TABLE elections (
    election_id   INT PRIMARY KEY AUTO_INCREMENT,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    start_date    DATETIME NOT NULL,
    end_date      DATETIME NOT NULL,
    status        ENUM('upcoming','active','closed') NOT NULL DEFAULT 'upcoming',
    created_by    INT NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- ============================================================
--  TABLE 4: parties  (3NF)
--  Separated from candidates to avoid repeating party info
-- ============================================================
CREATE TABLE parties (
    party_id    INT PRIMARY KEY AUTO_INCREMENT,
    party_name  VARCHAR(150) NOT NULL UNIQUE,
    party_logo  VARCHAR(255),
    description TEXT
);

-- ============================================================
--  TABLE 5: positions  (3NF)
--  Voting positions (President, Secretary, etc.)
-- ============================================================
CREATE TABLE positions (
    position_id   INT PRIMARY KEY AUTO_INCREMENT,
    position_name VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    display_order INT DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 6: candidates  (3NF)
--  party_name removed → references parties table
--  position_id added for position-based voting
-- ============================================================
CREATE TABLE candidates (
    candidate_id  INT PRIMARY KEY AUTO_INCREMENT,
    election_id   INT NOT NULL,
    party_id      INT NOT NULL,
    position_id   INT NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    bio           TEXT,
    photo         VARCHAR(255),
    FOREIGN KEY (election_id) REFERENCES elections(election_id) ON DELETE CASCADE,
    FOREIGN KEY (party_id)    REFERENCES parties(party_id),
    FOREIGN KEY (position_id) REFERENCES positions(position_id)
);

-- ============================================================
--  TABLE 7: votes  (3NF)
--  One vote per user per position per election
-- ============================================================
CREATE TABLE votes (
    vote_id      INT PRIMARY KEY AUTO_INCREMENT,
    user_id      INT NOT NULL,
    election_id  INT NOT NULL,
    position_id  INT NOT NULL,
    candidate_id INT NOT NULL,
    voted_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_election_position (user_id, election_id, position_id),
    FOREIGN KEY (user_id)      REFERENCES users(user_id),
    FOREIGN KEY (election_id)  REFERENCES elections(election_id) ON DELETE CASCADE,
    FOREIGN KEY (position_id)  REFERENCES positions(position_id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id)
);

-- ============================================================
--  NORMALIZATION NOTES
--  1NF : All columns atomic; no repeating groups
--  2NF : No partial dependencies (all PKs are single-column)
--  3NF : No transitive dependencies:
--        • role_name extracted to `roles`
--        • party_name/info extracted to `parties`
--        • election details not duplicated in votes
-- ============================================================

-- ============================================================
--  SAMPLE DATA - CUSTOMIZE AS NEEDED
-- ============================================================

-- Admin User (Email: admin@vote.com, Password: admin123)
-- To change password: use generate_password_hash.php
INSERT INTO users (full_name, email, password_hash, role_id)
VALUES ('Admin User', 'admin@vote.com',
        '$2y$10$BulwvejkHay7tUonMIF4oeFDE0PqhGqGd3BOwUEJrTmlDmZOJYMZ6', 1);

-- Political Parties (Customize names and descriptions)
INSERT INTO parties (party_name, description) VALUES
('Tech Innovators Alliance', 'Championing digital transformation, smart campus initiatives, coding competitions, hackathons, and cutting-edge technology integration in education.'),
('Progressive Engineers Forum', 'Committed to student welfare, affordable education, improved lab facilities, mental health support, and academic excellence for all engineering disciplines.'),
('United Students Coalition', 'Building an inclusive campus through cultural diversity programs, equal opportunities, collaborative projects, and strong inter-departmental unity.'),
('Future Leaders Party', 'Focused on career development, industry partnerships, internship opportunities, entrepreneurship support, and preparing students for professional success.');

-- Voting Positions
INSERT INTO positions (position_name, description, display_order) VALUES
('President',     'Chief executive officer of the organization', 1),
('Vice President', 'Second in command, assists the president', 2),
('Secretary',     'Handles documentation and communications', 3),
('Treasurer',     'Manages finances and budgets', 4);

-- Election
INSERT INTO elections (title, description, start_date, end_date, status, created_by)
VALUES ('Student Union Election 2025',
        'Vote for your student union representatives for the academic year 2025.',
        '2025-01-01 00:00:00', '2025-12-31 23:59:59', 'active', 1);

-- Candidates (party_id: 1=TIA, 2=PEF, 3=USC, 4=FLP | position_id: 1=President, 2=VP, 3=Secretary, 4=Treasurer)
INSERT INTO candidates (election_id, party_id, position_id, full_name, bio) VALUES
-- President candidates (1 from each party)
(1, 1, 1, 'Rajesh Sharma',     'Computer Engineering student passionate about tech innovation and smart campus initiatives.'),
(1, 2, 1, 'Suman Thapa',       'Mechanical Engineering senior with proven leadership in student welfare projects.'),
(1, 3, 1, 'Anita Gurung',      'Civil Engineering student committed to building inclusive campus culture.'),
(1, 4, 1, 'Bikash Tamang',     'Electronics Engineering major focused on career development and industry connections.'),
-- Vice President candidates (1 from each party)
(1, 1, 2, 'Prakash Adhikari',  'Software Engineering student with strong technical and organizational skills.'),
(1, 2, 2, 'Meera Shrestha',    'Electrical Engineering major dedicated to academic excellence and student support.'),
(1, 3, 2, 'Sunita Rai',        'Architecture student promoting diversity and collaborative campus environment.'),
(1, 4, 2, 'Ramesh Karki',      'Industrial Engineering student with focus on professional skill development.'),
-- Secretary candidates (1 from each party)
(1, 1, 3, 'Priya Poudel',      'IT Engineering student with excellent documentation and communication abilities.'),
(1, 2, 3, 'Arun Shahi',        'Aerospace Engineering major skilled in event coordination and record management.'),
(1, 3, 3, 'Sabina Magar',      'Chemical Engineering student committed to transparent communication systems.'),
(1, 4, 3, 'Deepak Basnet',     'Biomedical Engineering major with strong administrative experience.'),
-- Treasurer candidates (1 from each party)
(1, 1, 4, 'Kritika Thapa',     'Computer Engineering student with financial management and budgeting expertise.'),
(1, 2, 4, 'Suresh Pandey',     'Mechanical Engineering major experienced in fund allocation and auditing.'),
(1, 3, 4, 'Nisha Gurung',      'Civil Engineering student with strong analytical and financial planning skills.'),
(1, 4, 4, 'Rohan Shrestha',    'Electronics Engineering major focused on transparent financial operations.');
