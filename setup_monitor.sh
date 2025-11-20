#!/bin/bash
# Setup script for price monitor

echo "Setting up price monitor..."

# Check if p3env exists, create if not
if [ ! -d "p3env" ]; then
    echo "Creating virtual environment p3env..."
    python3 -m venv p3env
fi

# Activate virtual environment
echo "Activating virtual environment..."
source p3env/bin/activate

# Make the script executable
chmod +x price_monitor.py

# Install Python dependencies
echo "Installing dependencies..."
pip install -r requirements_monitor.txt

# Test the script
echo ""
echo "Testing the price monitor..."
python price_monitor.py

echo ""
echo "Setup complete!"
echo ""
echo "To set up automated monitoring, add this to your crontab:"
echo "(Run: crontab -e)"
echo ""
echo "# Check price every hour"
echo "0 * * * * cd $(pwd) && /usr/bin/python3 $(pwd)/price_monitor.py"
echo ""
echo "Or for every 30 minutes:"
echo "*/30 * * * * cd $(pwd) && /usr/bin/python3 $(pwd)/price_monitor.py"
echo ""
echo "Logs will be saved to: $(pwd)/price_monitor.log"
echo "Price history: $(pwd)/price_history.json"
echo ""
echo "Optional: Configure email alerts by setting these environment variables:"
echo "  export SMTP_SERVER='smtp.gmail.com'"
echo "  export SMTP_PORT='587'"
echo "  export SMTP_USER='your-email@gmail.com'"
echo "  export SMTP_PASSWORD='your-app-password'"
echo "  export ALERT_EMAIL='your-email@gmail.com'"
