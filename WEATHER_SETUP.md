# 3-Day Weather Forecast - Setup Instructions

This weather page displays a 3-day forecast for the following locations:
- Farnham, Surrey, UK
- London, England
- Nantgarw, Wales
- Norwich, Norfolk
- New York, USA

## Features

- Current weather conditions (temperature in Celsius)
- High and low temperatures for each day
- Precipitation chance and amount (in mm)
- Weather conditions description
- Automatic updates on page load
- Manual refresh button
- Responsive design

## Setup Instructions

### Step 1: Get a Free API Key

1. Go to https://www.weatherapi.com/signup.aspx
2. Sign up for a free account
3. After signing up, you'll receive an API key
4. Copy your API key

### Step 2: Configure the API Key

1. Open `weather_api.php` in a text editor
2. Find the line: `$API_KEY = 'YOUR_API_KEY_HERE';`
3. Replace `YOUR_API_KEY_HERE` with your actual API key
4. Save the file

### Step 3: Set Up a Local Web Server

You need a web server with PHP support to run this application.

#### Option A: Using PHP Built-in Server (Easiest)

```bash
# Navigate to the project directory
cd /home/user/macprojects

# Start the PHP development server
php -S localhost:8000
```

Then open your browser and go to: http://localhost:8000/weather.html

#### Option B: Using XAMPP/WAMP/MAMP

1. Install XAMPP (Windows/Linux) or MAMP (Mac)
2. Copy the files to the htdocs folder
3. Start Apache
4. Open browser and go to: http://localhost/weather.html

#### Option C: Using Apache/Nginx

1. Copy files to your web server's document root
2. Ensure PHP is enabled
3. Access via your server's URL

## Files

- `weather.html` - Main HTML page with embedded CSS and JavaScript
- `weather_api.php` - PHP backend that fetches weather data from WeatherAPI.com
- `WEATHER_SETUP.md` - This setup guide

## Usage

1. Open `weather.html` in your web browser (via the web server)
2. The page will automatically load weather data for all locations
3. Click the "Refresh Weather" button to update the data
4. Weather data includes:
   - Current temperature (°C)
   - Feels like temperature
   - Humidity percentage
   - 3-day forecast with high/low temps
   - Precipitation chance and amount
   - Weather conditions

## Troubleshooting

### "API key not configured" error
- Make sure you've added your API key to `weather_api.php`

### Weather data not loading
- Check that your web server is running
- Check browser console for errors (F12)
- Verify your API key is correct
- Ensure you have internet connectivity

### CORS errors
- Make sure you're accessing the page through a web server (not opening the HTML file directly)
- The PHP file includes CORS headers to allow access

## API Limits

The free tier of WeatherAPI.com includes:
- 1,000,000 API calls per month
- 3-day forecast
- Current weather
- No credit card required

## Customization

### Adding More Locations

Edit the `locations` array in `weather.html`:

```javascript
const locations = [
    { name: 'Your Location Name', query: 'City,Country' },
    // Add more locations here
];
```

### Changing Update Frequency

The page updates when:
- The page is loaded/refreshed
- The "Refresh Weather" button is clicked

To add auto-refresh, add this to the script section:

```javascript
// Auto-refresh every 30 minutes
setInterval(loadAllWeather, 30 * 60 * 1000);
```

## License

This is a custom weather application created for personal use.
