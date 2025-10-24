# WordPress on Replit with PostgreSQL

## 👋 About Me

Hi! I'm **Dawid**, and I created this repository to make WordPress compatible with Replit's infrastructure. Since Replit uses PostgreSQL databases instead of MySQL, getting WordPress to work requires some special configuration. I've done the heavy lifting for you, so you can get your WordPress site up and running in minutes instead of hours.

Below you'll find everything you need to quickly install and deploy WordPress on Replit. Enjoy! 🚀

---

## 📖 What is This Repository?

This is a **WordPress starter template** specially configured to work seamlessly with Replit's PostgreSQL database.

Standard WordPress installations require MySQL, but Replit provides PostgreSQL. This repository bridges that gap using the **PG4WP (PostgreSQL for WordPress)** plugin, which translates all MySQL queries to PostgreSQL-compatible syntax automatically.

### What's Included:
- ✅ WordPress (latest stable version)
- ✅ PG4WP plugin for PostgreSQL compatibility
- ✅ Automatic database configuration (no manual setup needed!)
- ✅ Automatic authentication key generation
- ✅ Automatic HTTPS handling for Replit domains
- ✅ Dynamic domain detection (works in dev and production)
- ✅ PHP 8.2 environment setup

### What's NOT Included:
- ❌ WordPress is not yet installed - you'll run the installation wizard on first launch (Before publishing it to the production make sure you have deleted wordpress/wp-config.php, to start installation on the fresh instance otherwise you need to update URLs in the database manually)
- ❌ Empty database - WordPress creates tables during installation

This ensures each deployment (dev, production, your teammate's fork) gets its own fresh WordPress installation with unique security keys.

---

## 🚀 Quick Installation Guide

Follow these simple steps to get WordPress running on Replit:

### Step 1: Fork This Repository

1. Click the **"Fork"** button at the top right of this repository
2. This creates your own copy that you can modify freely

### Step 2: Get Your Repository URL

1. On your forked repository page, click the green **"Code"** button
2. Copy the HTTPS URL (it should look like: `https://github.com/YOUR-USERNAME/wordpress-replit.git`)

### Step 3: Import to Replit

1. Go to [Replit](https://replit.com)
2. Click **"Create Repl"**
3. Select **"Import from GitHub"**
4. Paste your forked repository URL
5. Click **"Import from GitHub"**

Alternatively, you can import directly from this repository without forking (though forking is recommended so you can customize it):
- Use the original repository URL when importing to Replit

### Step 4: Create PostgreSQL Database

Once your Repl is created:

1. In your Repl, look at the left sidebar
2. Click on the **"Tools"** icon (wrench/screwdriver icon)
3. Find and click **"Database"**
4. Click **"Create PostgreSQL Database"**
5. Wait for the database to be created (this may take a minute)

The database connection details will be automatically available as environment variables.

### Step 5: Run WordPress

If you wish to install WordPress in your dev environment: 

1. Click the green **"Run"** button at the top of your Repl
2. Wait for the server to start (you'll see logs in the console)
3. A webview will open automatically

If you want to make it on the production machine:

1. Goto to publishing
2. **Setup Build command:** `php -S 0.0.0.0:5000 -t wordpress wordpress/router.php`
3. Configure your instance
4. Setup your domain
5. Publish

### Step 6: Automatic Configuration (No manual setup required!)

When you first visit your site, the automatic setup will run:

1. **Automatic setup page appears** showing "Configuration Complete!" ✓
2. Database credentials are automatically configured from Replit environment variables
3. Unique security keys are fetched from WordPress.org and inserted automatically
4. You'll be redirected to the WordPress installation wizard (after 3 seconds)

**You don't need to enter any database information manually!** Everything is configured automatically.

### Step 7: Complete WordPress Installation

You'll be taken to the **WordPress installation wizard** where you only need to provide:

1. **Site Title**: Your website name
2. **Username**: Admin username (don't use "admin" for security)
3. **Password**: Strong password (save this!)
4. **Email**: Your email address
5. Click **"Install WordPress"**
6. Log in with your credentials

🎉 **Congratulations!** Your WordPress site is now live on Replit!

### Important: Each Environment Has Its Own Installation

- **Development environment**: Has its own WordPress installation and database
- **Production deployment**: Has its own separate WordPress installation and database
- **Each deployment is independent**: This means you'll run the installation wizard separately for each environment (rememeber wp-config.php)

This ensures:
- ✅ Unique security keys for each environment
- ✅ Separate content and settings
- ✅ No conflicts between dev and production

---

## 🔧 Configuration Details

### Automatic Database Configuration

The `wp-config-sample.php` file is pre-configured to automatically read database credentials from Replit's environment variables. On first launch, the automatic setup script:

1. Copies `wp-config-sample.php` to `wp-config.php`
2. Fetches unique authentication keys from WordPress.org API
3. Inserts the keys into the configuration file
4. Redirects you to the WordPress installation wizard

The database configuration uses these environment variables (automatically provided by Replit):

```php
define( 'DB_NAME', getenv('PGDATABASE') );
define( 'DB_USER', getenv('PGUSER') );
define( 'DB_PASSWORD', getenv('PGPASSWORD') );
define( 'DB_HOST', getenv('PGHOST') . ':' . getenv('PGPORT') );
```

**You never need to manually enter database credentials!**

### Authentication Keys and Salts

The automatic setup generates **unique authentication keys** from WordPress.org's secret-key API. These keys are used to encrypt cookies and secure user sessions.

- ✅ Each installation gets unique keys from WordPress.org API
- ✅ Development and production have different keys (more secure)
- ✅ No manual configuration needed
- ✅ Keys are automatically inserted during first launch

### PG4WP Plugin

This repository includes the **PG4WP (PostgreSQL for WordPress)** plugin, which is essential for WordPress to work with PostgreSQL.

**What it does:**
- Intercepts all MySQL queries from WordPress
- Translates them to PostgreSQL-compatible syntax
- Handles differences between MySQL and PostgreSQL data types
- Rewrites SQL statements on-the-fly

**Plugin location:** `wordpress/wp-content/pg4wp/`

**Drop-in file:** `wordpress/wp-content/db.php` - This file automatically loads PG4WP before WordPress initializes

### HTTPS and Domain Handling

The configuration uses **dynamic domain detection** that automatically works across all environments:

**How it works:**
- Detects the current domain from the HTTP request (`$_SERVER['HTTP_HOST']`)
- Automatically configures WordPress URLs to match the current domain
- Forces HTTPS for all connections (as required by Replit)

**What this means for you:**
- ✅ **Development environment**: Automatically uses your dev domain (e.g., `xyz.replit.dev`)
- ✅ **Production deployment**: Automatically uses your production domain (e.g., `yourdomain.app.replit.com`)
- ✅ **Custom domains**: Automatically works with your custom domain if you set one up
- ✅ **No manual configuration needed**: URLs update automatically based on where the site is installed

This dynamic approach eliminates the need to manually update WordPress URLs when moving between environments or changing domains.

---

## 🛠️ Troubleshooting

### Issue: Database Connection Error

**Symptoms:** You see "Error establishing a database connection"

**Solutions:**
1. Make sure you've created a PostgreSQL database in Replit (see Step 4 above)
2. Check that the database is running:
   - Go to the Database tab in Replit
   - Verify the status shows "Active" or "Running"
3. Restart your Repl by clicking the Stop button, then Run again
4. Check the environment variables:
   - Open the Shell in Replit
   - Run: `env | grep PG`
   - You should see variables like `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `PGHOST`, `PGPORT`

### Issue: Setup Page Doesn't Appear

**Symptoms:** Site shows errors instead of automatic setup page

**Solutions:**
1. Make sure `wp-config.php` doesn't exist in the `wordpress/` directory (it should be created automatically)
2. Check that `wp-config-sample.php` exists
3. Verify the database is created and environment variables are set
4. Restart the Repl

### Issue: White Screen or Errors After Installation

**Symptoms:** Blank page or PHP errors displayed

**Solutions:**
1. Check the debug log:
   - Look at `wordpress/wp-content/debug.log`
   - This will show any WordPress errors
2. Verify PG4WP is loaded:
   - Check that `wordpress/wp-content/db.php` exists
   - Check that `wordpress/wp-content/pg4wp/` directory exists
3. Clear your browser cache and refresh
4. Check the PG4WP error log at `wordpress/wp-content/pg4wp/logs/pg4wp_errors.log`

### Issue: Changes Not Appearing

**Symptoms:** You edit files but don't see changes on the site

**Solutions:**
1. Restart the Repl completely (Stop, then Run)
2. Clear your browser cache or try incognito/private mode
3. Check if caching plugins are installed and disable them

### Issue: Images Not Uploading

**Symptoms:** Error when trying to upload media files

**Solutions:**
1. Check folder permissions for `wordpress/wp-content/uploads/`
2. Replit may have storage limits - check your plan's storage quota
3. Try uploading a smaller image first to test

### Issue: Permalink/URL Problems

**Symptoms:** Pages show 404 errors, only homepage works

**Solutions:**
1. The PHP development server doesn't support `.htaccess` rules
2. WordPress should work with the basic router setup included
3. If issues persist, go to WordPress Admin → Settings → Permalinks
4. Try using "Plain" permalinks temporarily
5. For production deployment, consider using Replit's deployment feature

### Issue: Wrong Domain or Broken URLs

**Symptoms:** Site shows wrong domain, CSS/images not loading, or mixed content errors

**Solutions:**
1. **This setup uses dynamic domain detection** - URLs are automatically set based on the current domain
2. The configuration reads from `$_SERVER['HTTP_HOST']` and sets WordPress URLs accordingly
3. **Don't manually change `WP_HOME` or `WP_SITEURL` in the WordPress admin** - they're automatically set in `wp-config.php`
4. If you see the wrong domain:
   - Check that you're accessing the site from the correct URL
   - Clear your browser cache
   - The site will automatically use whatever domain you access it from
5. For production deployments, make sure you're using the production URL (not the dev URL)

### Issue: Plugin Installation Fails

**Symptoms:** Can't install plugins from WordPress admin

**Solutions:**
1. Some plugins may not be compatible with PostgreSQL
2. PG4WP handles most standard plugins well
3. If a plugin fails, check the PG4WP error logs
4. Consider manually uploading plugins via the file manager
5. Popular plugins like Yoast SEO, Contact Form 7, etc. generally work fine

---

## 📚 Additional Resources

- [WordPress Documentation](https://wordpress.org/documentation/)
- [PG4WP GitHub Repository](https://github.com/PostgreSQL-For-Wordpress/postgresql-for-wordpress)
- [Replit Documentation](https://docs.replit.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

## ⚠️ Important Notes

1. **Security:** WordPress automatically generates unique authentication keys during setup. Each environment (dev, production) gets its own unique keys for better security.

2. **Separate Installations:** Development and production environments have completely separate WordPress installations and databases. The automatic setup runs separately for each environment.

3. **Backups:** Replit provides automatic backups, but for important sites, consider additional backup solutions

4. **Performance:** The development server is great for testing but consider deploying to production for better performance

5. **Database Limits:** Check your Replit plan for database size limits

6. **Updates:** Be cautious when updating WordPress core - test on a fork first to ensure PG4WP compatibility

---

## 🤝 Contributing

Found an issue or have an improvement? Feel free to:
- Open an issue
- Submit a pull request
- Share your feedback

---

## 📄 License

This repository includes:
- WordPress (GPL v2 or later)
- PG4WP Plugin (GPL v2 or later)
- Configuration files (MIT License)

---

## 💬 Support

If you run into any issues not covered in the troubleshooting section:
1. Check the WordPress debug log
2. Check the PG4WP error log  
3. Open an issue in this repository
4. Consult the Replit community forums

---

**Happy WordPress-ing on Replit! 🎉**

*Made with ❤️ in Poland*
