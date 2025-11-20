#!/usr/bin/env python3
"""
Price Monitor for SAXX Quest Tight product
Monitors price and sends alerts when it drops below threshold
"""

import requests
from bs4 import BeautifulSoup
import json
import sys
import os
from datetime import datetime
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# Configuration
PRODUCT_URL = "https://www.ldmountaincentre.com/ski-c26/clothing-c69/thermals-baselayers-c141/saxx-quest-tight-fly-p42029/s248374"
PRICE_THRESHOLD = 29.00  # Alert if price goes below this
LOG_FILE = os.path.join(os.path.dirname(__file__), "price_monitor.log")
PRICE_HISTORY_FILE = os.path.join(os.path.dirname(__file__), "price_history.json")

# Email configuration (set these environment variables)
# SMTP_SERVER = os.getenv('SMTP_SERVER', 'smtp.gmail.com')
# SMTP_PORT = int(os.getenv('SMTP_PORT', '587'))
# SMTP_USER = os.getenv('SMTP_USER', '')
# SMTP_PASSWORD = os.getenv('SMTP_PASSWORD', '')
# ALERT_EMAIL = os.getenv('ALERT_EMAIL', '')


def log_message(message):
    """Log message to file and print to console"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_entry = f"[{timestamp}] {message}"
    print(log_entry)

    with open(LOG_FILE, 'a') as f:
        f.write(log_entry + '\n')


def fetch_page():
    """Fetch the product page with proper headers"""
    # Try using curl first (often bypasses bot protection better)
    try:
        import subprocess
        curl_cmd = [
            'curl', '-s', '-L',
            '-H', 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            '-H', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            '-H', 'Accept-Language: en-GB,en;q=0.9',
            '-H', 'Accept-Encoding: gzip, deflate, br',
            '-H', 'Connection: keep-alive',
            '-H', 'Upgrade-Insecure-Requests: 1',
            '--compressed',
            PRODUCT_URL
        ]
        result = subprocess.run(curl_cmd, capture_output=True, text=True, timeout=30)
        if result.returncode == 0 and result.stdout and len(result.stdout) > 1000:
            log_message("Successfully fetched page using curl")
            return result.stdout
    except Exception as e:
        log_message(f"curl method failed: {e}, trying requests...")

    # Fallback to requests with session and retries
    session = requests.Session()
    headers = {
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language': 'en-GB,en-US;q=0.9,en;q=0.8',
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Fetch-Dest': 'document',
        'Sec-Fetch-Mode': 'navigate',
        'Sec-Fetch-Site': 'none',
        'Sec-Fetch-User': '?1',
        'Cache-Control': 'max-age=0',
        'Referer': 'https://www.ldmountaincentre.com/',
    }

    try:
        # First visit the homepage to get cookies
        session.get('https://www.ldmountaincentre.com/', headers=headers, timeout=15)

        # Now fetch the product page
        import time
        time.sleep(2)  # Brief delay to appear more human
        response = session.get(PRODUCT_URL, headers=headers, timeout=30)
        response.raise_for_status()
        return response.text
    except requests.RequestException as e:
        log_message(f"Error fetching page: {e}")
        return None


def extract_price(html):
    """Extract price from HTML"""
    soup = BeautifulSoup(html, 'html.parser')

    # Try multiple common price selectors
    price_selectors = [
        {'class': 'price'},
        {'class': 'product-price'},
        {'class': 'current-price'},
        {'itemprop': 'price'},
        {'class': 'sale-price'},
        {'class': 'actual-price'},
        {'class': 'now-price'},
    ]

    price_text = None

    # Try each selector
    for selector in price_selectors:
        element = soup.find(attrs=selector)
        if element:
            price_text = element.get_text(strip=True)
            break

    # Also try meta tags
    if not price_text:
        meta_price = soup.find('meta', {'property': 'product:price:amount'})
        if meta_price:
            price_text = meta_price.get('content', '')

    if not price_text:
        # Try to find any element with £ symbol
        elements = soup.find_all(string=lambda text: text and '£' in text)
        if elements:
            # Get the first one that looks like a price
            for elem in elements:
                if any(char.isdigit() for char in elem):
                    price_text = elem.strip()
                    break

    if price_text:
        # Clean and parse the price
        price_text = price_text.replace('£', '').replace(',', '').strip()
        # Extract first number (handles cases like "£29.99 RRP £50.00")
        import re
        match = re.search(r'(\d+\.?\d*)', price_text)
        if match:
            try:
                return float(match.group(1))
            except ValueError:
                pass

    return None


def save_price_history(price):
    """Save price to history file"""
    history = []
    if os.path.exists(PRICE_HISTORY_FILE):
        try:
            with open(PRICE_HISTORY_FILE, 'r') as f:
                history = json.load(f)
        except:
            pass

    history.append({
        'timestamp': datetime.now().isoformat(),
        'price': price
    })

    # Keep last 100 entries
    history = history[-100:]

    with open(PRICE_HISTORY_FILE, 'w') as f:
        json.dump(history, f, indent=2)


def send_email_alert(current_price):
    """Send email alert when price drops below threshold"""
    smtp_user = os.getenv('SMTP_USER', '')
    smtp_password = os.getenv('SMTP_PASSWORD', '')
    alert_email = os.getenv('ALERT_EMAIL', '')

    if not all([smtp_user, smtp_password, alert_email]):
        log_message("Email credentials not configured. Skipping email alert.")
        return False

    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = f'Price Alert: SAXX Quest Tight now £{current_price:.2f}!'
        msg['From'] = smtp_user
        msg['To'] = alert_email

        text = f"""
Price Alert!

The SAXX Quest Tight product has dropped below your threshold of £{PRICE_THRESHOLD:.2f}

Current Price: £{current_price:.2f}

Product URL: {PRODUCT_URL}

Time: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}
"""

        html = f"""
<html>
  <body>
    <h2>🎉 Price Alert!</h2>
    <p>The <strong>SAXX Quest Tight</strong> product has dropped below your threshold of £{PRICE_THRESHOLD:.2f}</p>
    <p><strong>Current Price: £{current_price:.2f}</strong></p>
    <p><a href="{PRODUCT_URL}">View Product</a></p>
    <p><small>Time: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}</small></p>
  </body>
</html>
"""

        part1 = MIMEText(text, 'plain')
        part2 = MIMEText(html, 'html')
        msg.attach(part1)
        msg.attach(part2)

        smtp_server = os.getenv('SMTP_SERVER', 'smtp.gmail.com')
        smtp_port = int(os.getenv('SMTP_PORT', '587'))

        with smtplib.SMTP(smtp_server, smtp_port) as server:
            server.starttls()
            server.login(smtp_user, smtp_password)
            server.send_message(msg)

        log_message(f"Email alert sent to {alert_email}")
        return True
    except Exception as e:
        log_message(f"Error sending email: {e}")
        return False


def send_desktop_notification(current_price):
    """Send desktop notification (Linux/Mac)"""
    title = "Price Alert!"
    message = f"SAXX Quest Tight is now £{current_price:.2f} (below £{PRICE_THRESHOLD:.2f})"

    try:
        if sys.platform == 'darwin':  # macOS
            os.system(f"""osascript -e 'display notification "{message}" with title "{title}"'""")
        elif sys.platform.startswith('linux'):  # Linux
            os.system(f'notify-send "{title}" "{message}"')
        log_message("Desktop notification sent")
    except Exception as e:
        log_message(f"Error sending desktop notification: {e}")


def check_price():
    """Main function to check price and alert if needed"""
    log_message("Starting price check...")

    html = fetch_page()
    if not html:
        log_message("Failed to fetch page")
        return False

    price = extract_price(html)

    if price is None:
        log_message("Failed to extract price from page")
        # Save HTML for debugging
        debug_file = os.path.join(os.path.dirname(__file__), "debug_page.html")
        with open(debug_file, 'w') as f:
            f.write(html)
        log_message(f"Saved HTML to {debug_file} for debugging")
        return False

    log_message(f"Current price: £{price:.2f}")
    save_price_history(price)

    if price < PRICE_THRESHOLD:
        log_message(f"🎉 ALERT! Price £{price:.2f} is below threshold £{PRICE_THRESHOLD:.2f}")
        send_email_alert(price)
        send_desktop_notification(price)
        return True
    else:
        log_message(f"Price is above threshold (£{price:.2f} >= £{PRICE_THRESHOLD:.2f})")
        return False


if __name__ == '__main__':
    try:
        check_price()
    except Exception as e:
        log_message(f"Unexpected error: {e}")
        sys.exit(1)
