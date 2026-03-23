# 🚀 Deploy VoteSecure to Render

## Quick Deployment Steps

### 1. Create Render Account
- Go to https://render.com
- Sign up with GitHub account

### 2. Create MySQL Database

1. Click "New +" → "PostgreSQL" (Wait! We need MySQL)
   
   **Note:** Render's free tier doesn't include MySQL. You have 2 options:

   **Option A: Use External MySQL (Recommended)**
   - Use [FreeSQLDatabase.com](https://www.freesqldatabase.com/)
   - Or [db4free.net](https://www.db4free.net/)
   - Get connection details (host, port, user, password, database)

   **Option B: Use Railway (Alternative)**
   - Railway.app offers free MySQL
   - Better for PHP + MySQL projects

### 3. Deploy Web Service on Render

1. Click "New +" → "Web Service"
2. Connect your GitHub repository: `Navneet-Mallick/voting_system`
3. Configure:
   - **Name**: votesecure
   - **Environment**: Docker
   - **Plan**: Free
   - **Dockerfile Path**: ./Dockerfile

4. Add Environment Variables:
   ```
   DB_HOST=your-mysql-host
   DB_PORT=3306
   DB_USER=your-mysql-user
   DB_PASS=your-mysql-password
   DB_NAME=voting_system
   ```

5. Click "Create Web Service"

### 4. Import Database

Once deployed, you need to import the database:

**Method 1: Using phpMyAdmin (if available)**
- Upload `database.sql` file

**Method 2: Using MySQL command line**
```bash
mysql -h YOUR_HOST -P 3306 -u YOUR_USER -p YOUR_DATABASE < database.sql
```

**Method 3: Using online SQL executor**
- Copy contents of `database.sql`
- Paste into your MySQL provider's SQL executor

### 5. Update Admin Password

After deployment, immediately change admin password:
1. Visit: `https://your-app.onrender.com/change_password.php`
2. Email: admin@vote.com
3. Set new secure password

---

## Alternative: Deploy to Railway (Easier for PHP + MySQL)

Railway is better suited for PHP + MySQL projects:

### 1. Create Railway Account
- Go to https://railway.app
- Sign up with GitHub

### 2. Create New Project
1. Click "New Project"
2. Select "Deploy from GitHub repo"
3. Choose `Navneet-Mallick/voting_system`

### 3. Add MySQL Database
1. Click "New" → "Database" → "Add MySQL"
2. Railway will automatically create MySQL instance
3. Note the connection details

### 4. Configure Environment Variables
Railway auto-detects from MySQL, but verify:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_DATABASE`

Update `php/config.example.php` to use Railway's variable names if needed.

### 5. Deploy
- Railway automatically deploys on git push
- Get your public URL from Railway dashboard

### 6. Import Database
Use Railway's MySQL client or phpMyAdmin to import `database.sql`

---

## Alternative: Deploy to InfinityFree (Free PHP Hosting)

InfinityFree offers free PHP + MySQL hosting:

### 1. Create Account
- Go to https://infinityfree.net
- Sign up for free account

### 2. Create Hosting Account
- Choose subdomain or use custom domain
- Wait for account activation (instant)

### 3. Upload Files
**Via File Manager:**
1. Login to control panel
2. Go to "Online File Manager"
3. Navigate to `htdocs` folder
4. Upload all project files (except .git folder)

**Via FTP:**
1. Get FTP credentials from control panel
2. Use FileZilla or similar FTP client
3. Upload files to `htdocs` folder

### 4. Create MySQL Database
1. Go to "MySQL Databases" in control panel
2. Create new database
3. Create database user
4. Note: host, database name, username, password

### 5. Update Config
1. Edit `php/config.php` via File Manager
2. Update database credentials:
   ```php
   define('DB_HOST', 'sqlXXX.infinityfree.net');
   define('DB_PORT', '3306');
   define('DB_USER', 'epiz_XXXXXXXX');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'epiz_XXXXXXXX_voting');
   ```

### 6. Import Database
1. Go to "phpMyAdmin" in control panel
2. Select your database
3. Click "Import" tab
4. Upload `database.sql`
5. Click "Go"

### 7. Access Your Site
- URL: `http://your-subdomain.infinityfreeapp.com`
- Login: admin@vote.com / admin123
- Change password immediately!

---

## Troubleshooting

### Database Connection Failed
- Verify environment variables are set correctly
- Check database host is accessible
- Ensure database exists and user has permissions

### 500 Internal Server Error
- Check PHP error logs in Render dashboard
- Verify all PHP extensions are installed (PDO, pdo_mysql)
- Check file permissions

### Cannot Import Database
- Try importing tables one by one
- Check for syntax errors in SQL
- Ensure MySQL version compatibility

### Admin Cannot Login
- Use `change_password.php` to reset
- Check if users table was imported correctly
- Verify bcrypt hash is correct

---

## Security Checklist After Deployment

- [ ] Change admin password from default
- [ ] Update database credentials (don't use 'root')
- [ ] Enable HTTPS (Render provides free SSL)
- [ ] Remove or protect `generate_password_hash.php`
- [ ] Set strong database password
- [ ] Review and set production PHP settings in config
- [ ] Test all functionality (login, vote, results)
- [ ] Monitor error logs regularly

---

## Recommended: Railway for This Project

For PHP + MySQL projects like VoteSecure, **Railway** is the best free option:

✅ Native MySQL support
✅ Automatic deployments from GitHub
✅ Easy environment variable management
✅ Built-in phpMyAdmin alternative
✅ Better performance than Render for PHP
✅ Free tier includes MySQL database

**Render** is better for Node.js/Python projects but requires external MySQL.

---

## Cost Comparison

| Platform | Web Hosting | MySQL | Total | Best For |
|----------|-------------|-------|-------|----------|
| Railway | Free | Free | Free | PHP + MySQL |
| Render | Free | External | Free | Node.js, Python |
| InfinityFree | Free | Free | Free | Simple PHP sites |
| Vercel | Free | External | Free | Static/Next.js |

---

**Recommendation:** Use Railway for easiest deployment with MySQL included.
