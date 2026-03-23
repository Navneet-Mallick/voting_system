-- ============================================================
--  DATABASE UPGRADE SCRIPT
--  Adds: positions, updates candidates & votes tables
-- ============================================================

USE voting_system;

-- ============================================================
--  NEW TABLE: positions
-- ============================================================
CREATE TABLE IF NOT EXISTS positions (
    position_id   INT PRIMARY KEY AUTO_INCREMENT,
    position_name VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    display_order INT DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Sample positions
INSERT INTO positions (position_name, description, display_order) VALUES
('President', 'Chief executive officer of the organization', 1),
('Vice President', 'Second in command', 2),
('Secretary', 'Handles documentation and communications', 3),
('Treasurer', 'Manages finances and budgets', 4),
('Executive Member', 'General executive board member', 5);

-- ============================================================
--  ALTER TABLE: candidates (add position_id)
-- ============================================================
ALTER TABLE candidates 
ADD COLUMN position_id INT NULL AFTER party_id;

-- Add foreign key constraint
ALTER TABLE candidates
ADD CONSTRAINT fk_candidates_position 
FOREIGN KEY (position_id) REFERENCES positions(position_id);

-- Set default position for existing candidates (if any)
UPDATE candidates SET position_id = 1 WHERE position_id IS NULL;

-- Make position_id required after setting defaults
ALTER TABLE candidates 
MODIFY COLUMN position_id INT NOT NULL;

-- ============================================================
--  ALTER TABLE: votes (add position_id, update unique constraint)
-- ============================================================
-- Drop old unique constraint (one vote per election)
ALTER TABLE votes DROP INDEX uq_user_election;

-- Add position_id column
ALTER TABLE votes 
ADD COLUMN position_id INT NULL AFTER election_id;

-- Add foreign key
ALTER TABLE votes
ADD CONSTRAINT fk_votes_position 
FOREIGN KEY (position_id) REFERENCES positions(position_id);

-- Update existing votes with position from their candidate
UPDATE votes v
JOIN candidates c ON v.candidate_id = c.candidate_id
SET v.position_id = c.position_id;

-- Make position_id required
ALTER TABLE votes 
MODIFY COLUMN position_id INT NOT NULL;

-- Add new unique constraint (one vote per user per position per election)
ALTER TABLE votes 
ADD UNIQUE KEY uq_user_election_position (user_id, election_id, position_id);

-- ============================================================
--  VERIFICATION QUERIES
-- ============================================================
-- Check tables structure
-- DESCRIBE positions;
-- DESCRIBE candidates;
-- DESCRIBE votes;

-- Check constraints
-- SELECT * FROM information_schema.KEY_COLUMN_USAGE 
-- WHERE TABLE_SCHEMA = 'voting_system' 
-- AND TABLE_NAME IN ('candidates', 'votes');
