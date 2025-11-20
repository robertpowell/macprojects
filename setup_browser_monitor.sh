#!/bin/bash
# Setup script for browser-based price monitor

echo "Setting up browser-based price monitor..."
echo ""

# Check if p3env exists, create if not
if [ ! -d "p3env" ]; then
    echo "Creating virtual environment p3env..."
    python3 -m venv p3env
fi

# Activate virtual environment
echo "Activating virtual environment..."
source p3env/bin/activate

# Make the script executable
chmod +x price_monitor_browser.py

# Install Python dependencies
echo "Installing Python dependencies..."
pip install requests beautifulsoup4 playwright

echo ""
echo "Installing Playwright browser (Chromium)..."
python -m playwright install chromium

# Test the script
echo ""
echo "Testing the price monitor..."
python price_monitor_browser.py

echo ""
echo "================================================================"
echo "Setup complete!"
echo "================================================================"
echo ""
echo "To set up automated monitoring, add this to your crontab:"
echo "(Run: crontab -e)"
echo ""
echo "# Check price every 2 hours"
echo "0 */2 * * * cd $(pwd) && $(pwd)/p3env/bin/python $(pwd)/price_monitor_browser.py"
echo ""
echo "Or for every 4 hours (recommended to avoid detection):"
echo "0 */4 * * * cd $(pwd) && $(pwd)/p3env/bin/python $(pwd)/price_monitor_browser.py"
echo ""
echo "Files:"
echo "  - Logs: $(pwd)/price_monitor.log"
echo "  - Price history: $(pwd)/price_history.json"
echo "  - Debug HTML: $(pwd)/debug_page.html"
echo ""
echo "Optional: Configure email alerts by setting environment variables:"
echo "  export SMTP_SERVER='smtp.gmail.com'"
echo "  export SMTP_PORT='587'"
echo "  export SMTP_USER='your-email@gmail.com'"
echo "  export SMTP_PASSWORD='your-app-password'"
echo "  export ALERT_EMAIL='your-email@gmail.com'"
echo ""
echo "View logs: tail -f $(pwd)/price_monitor.log"
