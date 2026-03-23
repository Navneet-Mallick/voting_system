# 🗳️ VoteSecure — Online Voting System
### DBMS Project | PHP · MySQL · HTML · CSS · JavaScript

---

## 📁 Project Structure

```
voting_system/
├── index.html          ← Login / Register page
├── dashboard.html      ← Voter dashboard (browse & vote)
├── results.html        ← Live election results
├── admin.html          ← Admin panel (full CRUD)
│
├── css/
│   └── styles.css      ← Complete stylesheet
│
├── js/
│   └── app.js          ← Shared JS utilities
│
├── php/
│   ├── config.php      ← DB connection + helpers
│   ├── auth.php        ← Login / Register / Logout
│   ├── elections.php   ← Elections CRUD
│   ├── candidates.php  ← Candidates CRUD
│   ├── votes.php       ← Cast votes + results
│   └── users.php       ← User management
│
└── database.sql        ← Schema + seed data
```

---

## ⚙️ Setup Instructions

### Requirements
- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.3+
- Apache / Nginx (or PHP built-in server for dev)

### Steps

1. **Clone / copy** the `voting_system/` folder into your web server root
   (e.g. `htdocs/` for XAMPP, `www/` for WAMP)

2. **Create the database:**
   ```sql
   mysql -u root -p < database.sql
   ```
   Or paste the contents of `database.sql` in phpMyAdmin.

3. **Configure DB credentials** in `php/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'voting_system');
   ```

4. **Open** `http://localhost/voting_system/` in your browser.

5. **Default admin login:**
   - Email: `admin@vote.com`
   - Password: Update the hash in database.sql using:
     ```php
     echo password_hash('yourpassword', PASSWORD_BCRYPT);
     ```

---

## 🗂️ Database Normalization (1NF → 3NF)

### Tables

| Table       | Purpose                                      |
|-------------|----------------------------------------------|
| `roles`     | Lookup table for user roles                  |
| `users`     | Registered voters and admins                 |
| `elections` | Election events with date ranges             |
| `parties`   | Political parties (normalized out)           |
| `candidates`| Candidates linked to elections and parties   |
| `votes`     | One vote per user per election               |

### Normalization Proof

**1NF (First Normal Form)**
- All columns are atomic (no multi-valued attributes)
- No repeating groups
- Every table has a primary key

**2NF (Second Normal Form)**
- All tables use single-column surrogate PKs (AUTO_INCREMENT)
- No partial dependencies possible

**3NF (Third Normal Form)**
- `role_name` removed from `users` → extracted to `roles` table
  (eliminated transitive dependency: user_id → role_id → role_name)
- `party_name`, `party_logo`, `description` removed from `candidates`
  → extracted to `parties` table
  (eliminated transitive dependency: candidate_id → party_id → party_name)
- `election` details not repeated in `votes` — only FK stored

### ER Diagram (text)

```
roles ──< users >── (created) ── elections
                                     │
parties ──< candidates >─────────────┘
                │
               votes <── users
```

---

## 🔒 Security Features

- Passwords hashed with `password_hash()` (bcrypt)
- All DB queries use PDO **prepared statements** (prevents SQL injection)
- Session-based authentication
- Role-based access control (voter vs admin)
- Double-vote prevention via UNIQUE constraint on `(user_id, election_id)`
- Candidate ownership validated before vote insertion

---

## 📋 CRUD Operations

| Resource   | Create | Read | Update | Delete |
|------------|--------|------|--------|--------|
| Elections  | ✅ Admin | ✅ All | ✅ Admin | ✅ Admin |
| Candidates | ✅ Admin | ✅ All | ✅ Admin | ✅ Admin |
| Votes      | ✅ Voter | ✅ Admin | ❌ (immutable) | ❌ (immutable) |
| Users      | ✅ Register | ✅ Admin | ✅ Admin | ✅ Admin |

---

## 🚀 Features

### Voter
- Register & login securely
- Browse all elections with status filters
- Cast one vote per election
- See live results with animated progress bars

### Admin
- Dashboard with stats (users, elections, votes)
- Full CRUD on elections (with status management)
- Full CRUD on candidates (linked to elections & parties)
- User management (activate/deactivate, delete)
- Vote audit log per election
