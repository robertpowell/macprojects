# Price Monitor for SAXX Quest Tight

Monitors the price of the SAXX Quest Tight product and alerts when it drops below £29.

## Product URL
https://www.ldmountaincentre.com/ski-c26/clothing-c69/thermals-baselayers-c141/saxx-quest-tight-fly-p42029/s248374

## Two Versions Available

### Version 1: Simple HTTP (price_monitor.py)
- Lightweight, fast
- May be blocked by bot protection
- Good for sites without Cloudflare/bot protection

### Version 2: Browser-Based (price_monitor_browser.py) - **RECOMMENDED**
- Uses Playwright (real browser automation)
- Bypasses most bot protection including Cloudflare
- Handles JavaScript and dynamic content
- Slightly slower but more reliable

## Quick Start (Browser-Based - Recommended)

1. **Run the setup script:**
   ```bash
   bash setup_browser_monitor.sh
   ```
   This will install all dependencies including Playwright and Chromium.

2. **Test the monitor manually:**
   ```bash
   python3 price_monitor_browser.py
   ```

## Alternative: Simple HTTP Version

1. **Install dependencies:**
   ```bash
   pip3 install requests beautifulsoup4
   ```

2. **Test the monitor:**
   ```bash
   python3 price_monitor.py
   ```

Note: This version may not work if the site has bot protection.

## Features

- ✅ Fetches product page with proper headers to avoid blocking
- ✅ Extracts current price from HTML
- ✅ Logs all price checks to `price_monitor.log`
- ✅ Saves price history to `price_history.json`
- ✅ Desktop notifications when price drops below threshold
- ✅ Optional email alerts
- ✅ Saves HTML for debugging if price extraction fails

## Alert Methods

### 1. Desktop Notifications (Default)
Works automatically on macOS and Linux. You'll see a system notification when the price drops.

### 2. Email Alerts (Optional)
Set up email alerts by configuring environment variables:

```bash
export SMTP_SERVER='smtp.gmail.com'
export SMTP_PORT='587'
export SMTP_USER='your-email@gmail.com'
export SMTP_PASSWORD='your-app-password'  # Use App Password for Gmail
export ALERT_EMAIL='your-email@gmail.com'
```

For Gmail, you'll need to create an [App Password](https://support.google.com/accounts/answer/185833).

Add these to your `~/.bashrc` or `~/.zshrc` to make them permanent.

## Automated Monitoring with Cron

To check the price automatically, add a cron job:

1. Edit your crontab:
   ```bash
   crontab -e
   ```

2. Add one of these lines:

   **Every hour:**
   ```
   0 * * * * cd /home/user/macprojects && /usr/bin/python3 /home/user/macprojects/price_monitor.py
   ```

   **Every 30 minutes:**
   ```
   */30 * * * * cd /home/user/macprojects && /usr/bin/python3 /home/user/macprojects/price_monitor.py
   ```

   **Every 4 hours:**
   ```
   0 */4 * * * cd /home/user/macprojects && /usr/bin/python3 /home/user/macprojects/price_monitor.py
   ```

## Configuration

Edit `price_monitor.py` to change:

- `PRICE_THRESHOLD`: Alert threshold (default: £29.00)
- `PRODUCT_URL`: Product page to monitor

## Files

- `price_monitor.py` - Main monitoring script
- `price_monitor.log` - Log of all price checks
- `price_history.json` - Historical price data
- `debug_page.html` - Saved when price extraction fails (for debugging)

## Troubleshooting

### Price extraction fails
If the script can't extract the price, it saves the HTML to `debug_page.html`. You can:
1. Open the file and search for price elements
2. Update the `extract_price()` function with the correct CSS selectors

### Website blocking requests
The script uses realistic browser headers, but if the site blocks it:
- Try running it less frequently
- Check if the site has a robots.txt policy
- The script will log errors for debugging

### Cron job not running
- Make sure paths are absolute in the crontab
- Check cron logs: `grep CRON /var/log/syslog` (Linux)
- Verify Python path: `which python3`

## Viewing Logs

```bash
# View recent logs
tail -f price_monitor.log

# View price history
cat price_history.json | python3 -m json.tool
```

## Manual Testing

```bash
# Single check
python3 price_monitor.py

# View what was logged
tail price_monitor.log
```
