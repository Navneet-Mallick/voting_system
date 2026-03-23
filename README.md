# 🗳️ VoteSecure — Online Voting System
### DBMS Project | PHP · MySQL · HTML · CSS · JavaScript

<p align="center">
  <img src="images/logo.svg" alt="VoteSecure Logo" width="120" height="120">
</p>

A comprehensive position-based voting system with party management, real-time results, and complete admin controls.

---

## 🚀 Quick Start

### Requirements
- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.3+
- Apache / Nginx (or PHP built-in server)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd voting_system
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < database.sql
   ```
   Or import `database.sql` via phpMyAdmin

3. **Configure database connection** in `php/config.php`:
   
   If `php/config.php` doesn't exist, copy from example:
   ```bash
   cp php/config.example.php php/config.php
   ```
   
   Then edit `php/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'voting_system');
   ```

4. **Access the application**
   ```
   http://localhost/voting_system/
   ```

## 🔐 Change Admin Password

### Method 1: Using Web Interface (Easiest)
1. Open `http://localhost/voting_system/change_password.php` in your browser
2. Enter email: `admin@vote.com`
3. Enter your new password (min 6 characters)
4. Click "Change Password"

### Method 2: Using Command Line
```bash
php generate_password_hash.php yourpassword
```
Copy the hash and run in MySQL:
```sql
UPDATE users SET password_hash = 'PASTE_HASH_HERE' WHERE email = 'admin@vote.com';
```

---

## 📁 Project Structure

```
voting_system/
├── index.html          ← Login / Register page
├── dashboard.html      ← Voter dashboard (browse & vote)
├── results.html        ← Live election results (3 views)
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
│   ├── parties.php     ← Parties CRUD
│   ├── positions.php   ← Positions CRUD
│   ├── candidates.php  ← Candidates CRUD
│   ├── votes.php       ← Cast votes + results
│   └── users.php       ← User management
│
├── database.sql        ← Initial schema + seed data
└── database_upgrade.sql ← Upgrade script (if needed)
```

---

## ✨ Key Features

### For Voters
- Secure registration and login
- Browse active elections
- Vote once per position (President, Secretary, etc.)
- View real-time results in 3 formats
- Responsive mobile-friendly interface

### For Administrators
- Complete election management (create, edit, delete)
- Party management (create parties with descriptions)
- Position management (define voting positions)
- Candidate management (assign to party + position)
- User management (activate/deactivate accounts)
- Vote audit log with full details
- Dashboard with system statistics

### Real-Time Results
- **Position-wise**: See winner for each position
- **Party-wise**: Total votes per party
- **Individual**: All candidates ranked
- Auto-refresh every 5 seconds (AJAX)

---

## 🗂️ Database Schema

### Core Tables

| Table       | Purpose                                      |
|-------------|----------------------------------------------|
| `roles`     | User roles (voter, admin)                    |
| `users`     | Registered voters and administrators         |
| `elections` | Election events with date ranges             |
| `parties`   | Political parties with descriptions          |
| `positions` | Voting positions (President, Secretary, etc.)|
| `candidates`| Candidates linked to elections, parties, positions |
| `votes`     | One vote per user per position per election  |

### Normalization (3NF Compliant)

**1NF**: All columns atomic, no repeating groups, primary keys defined
**2NF**: Single-column surrogate keys, no partial dependencies
**3NF**: No transitive dependencies
- Party details extracted to `parties` table
- Position details extracted to `positions` table
- Role names extracted to `roles` table

### ER Diagram
```
roles ──< users >── votes ──> candidates ──< elections
                      │            │
                      │            ├──> parties
                      │            └──> positions
                      │
                      └──> positions
```

---

## 🔐 Security Features

- ✅ Password hashing with bcrypt (`password_hash()`)
- ✅ Prepared statements (prevents SQL injection)
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Double-vote prevention (unique constraint per position)
- ✅ Input validation and sanitization
- ✅ XSS protection (output escaping)

---

## 📋 CRUD Operations

| Resource   | Create | Read | Update | Delete | Access |
|------------|--------|------|--------|--------|--------|
| Elections  | ✅ | ✅ | ✅ | ✅ | Admin |
| Parties    | ✅ | ✅ | ✅ | ✅ | Admin |
| Positions  | ✅ | ✅ | ✅ | ✅ | Admin |
| Candidates | ✅ | ✅ | ✅ | ✅ | Admin |
| Votes      | ✅ | ✅ | ❌ | ❌ | Voter/Admin |
| Users      | ✅ | ✅ | ✅ | ✅ | Admin |

---

## 🎯 Usage Workflow

### Admin Setup
1. Login to admin panel
2. Create parties (Parties tab)
3. Create positions (Positions tab)
4. Create election (Elections tab)
5. Add candidates with party + position
6. Set election status to "Active"

### Voter Experience
1. Register/login to system
2. Browse active elections
3. Click "Vote Now" on election
4. Vote for each position separately
5. View live results

### Viewing Results
1. Select election from dropdown
2. Switch between result views:
   - **By Position**: Winner per position
   - **By Party**: Total party votes
   - **By Candidate**: All candidates ranked
3. Results auto-update every 5 seconds

---

## 🔌 API Endpoints

### Authentication
```
POST php/auth.php?action=register
POST php/auth.php?action=login
POST php/auth.php?action=logout
GET  php/auth.php?action=check
```

### Elections
```
GET  php/elections.php?action=list
GET  php/elections.php?action=get&id=X
POST php/elections.php?action=create
POST php/elections.php?action=update
POST php/elections.php?action=delete
```

### Parties
```
GET  php/parties.php?action=list
GET  php/parties.php?action=get&id=X
POST php/parties.php?action=create
POST php/parties.php?action=update
POST php/parties.php?action=delete
```

### Positions
```
GET  php/positions.php?action=list
GET  php/positions.php?action=get&id=X
POST php/positions.php?action=create
POST php/positions.php?action=update
POST php/positions.php?action=delete
```

### Candidates
```
GET  php/candidates.php?action=list&election_id=X
GET  php/candidates.php?action=get&id=X
GET  php/candidates.php?action=positions
POST php/candidates.php?action=create
POST php/candidates.php?action=update
POST php/candidates.php?action=delete
```

### Votes
```
POST php/votes.php?action=cast
GET  php/votes.php?action=check&election_id=X
GET  php/votes.php?action=results&election_id=X
GET  php/votes.php?action=party_results&election_id=X
GET  php/votes.php?action=candidate_results&election_id=X
GET  php/votes.php?action=log&election_id=X
```

### Users
```
GET  php/users.php?action=list
GET  php/users.php?action=get&id=X
POST php/users.php?action=update
POST php/users.php?action=delete
```

---

## 🔄 Real-Time Updates (AJAX)

Results page uses JavaScript polling to fetch updates every 5 seconds:

```javascript
// Auto-refresh implementation
refreshInterval = setInterval(fetchAndDisplayResults, 5000);
```

Benefits:
- No page reload required
- Works with standard HTTP
- Compatible with all browsers
- Minimal server load
- Suitable for 100-1000 concurrent users

For higher traffic, consider WebSocket or Server-Sent Events (SSE).

---

## 🛠️ Upgrading from Basic Version

If you have the basic single-vote system, run the upgrade script:

```bash
# Backup first!
mysqldump -u root -p voting_system > backup.sql

# Run upgrade
mysql -u root -p voting_system < database_upgrade.sql
```

The upgrade adds:
- Party management system
- Position-based voting
- Enhanced results with 3 views
- Real-time AJAX updates
- Updated database schema

---

## 🧪 Testing Checklist

- [ ] Admin can create parties
- [ ] Admin can create positions
- [ ] Admin can add candidates with party + position
- [ ] Voter can vote once per position
- [ ] Voter cannot vote twice for same position
- [ ] Results show position-wise winners
- [ ] Results show party-wise totals
- [ ] Results show individual rankings
- [ ] Results auto-refresh every 5 seconds
- [ ] Vote log shows all vote details
- [ ] Foreign key constraints work
- [ ] SQL injection prevented (prepared statements)

---

## 🐛 Troubleshooting

### Cannot Login as Admin
- Use `change_password.php` to reset password
- Default email: `admin@vote.com`

### Database Connection Failed
- Check credentials in `php/config.php`
- Verify MySQL service is running
- Ensure database exists

### Cannot Vote
- Check election status is "Active"
- Verify user is logged in as voter
- Check if already voted for that position

### Results Not Updating
- Check browser console for errors
- Verify API endpoints are accessible
- Clear browser cache

### Foreign Key Constraint Error
- Ensure upgrade script ran completely
- Check all required tables exist
- Verify data integrity

---

## 📊 Performance Considerations

- Efficient JOINs with proper foreign keys
- Indexes automatically created on FKs
- AJAX polling every 5 seconds (configurable)
- Single query per result type
- Client-side rendering reduces server load
- No N+1 query problems

---

## 🌐 Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS/Android)

---

## 🚀 Future Enhancements

- Candidate photo uploads
- Export results to PDF/Excel
- Email notifications for election events
- Voter turnout analytics
- Multi-language support
- Dark mode theme
- WebSocket for instant updates
- Vote verification system
- Mobile app (React Native/Flutter)
- Accessibility improvements (WCAG 2.1)

---

## 📄 License

This project is open source and available for educational purposes.

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a pull request

---

## 📞 Support

For issues or questions:
1. Check this README
2. Review PHP error logs
3. Check browser console for JS errors
4. Test API endpoints directly
5. Verify database schema

---

**Built with ❤️ for secure, transparent, and efficient online voting**
