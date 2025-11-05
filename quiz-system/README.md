# 🎯 Interactive Quiz System

A comprehensive web-based quiz platform for creating and managing multiple-choice quizzes with real-time participation tracking, QR code generation, and mobile-optimized interface.

## ✨ Features

### Core Features
- **Easy Quiz Management**: Upload quizzes via CSV or JSON files
- **QR Code Generation**: Instant QR codes for each quiz session
- **Anonymous Participation**: Color-coded participant identities (no personal data collection)
- **Random Question Selection**: Each participant gets a unique random set from question pool
- **Real-time Progress Tracking**: Monitor participant progress as they take quizzes
- **Timer-based Quizzes**: Configurable time limits per quiz
- **Mobile-Optimized**: Fully responsive design optimized for smartphones
- **Instant Results**: Live leaderboards and detailed answer reviews
- **Comprehensive Logging**: Track all quiz activities and participation

### Admin Features
- Password-protected admin panel (.htaccess)
- Topic and quiz management
- Enable/disable quizzes and topics
- Launch quiz sessions with QR codes
- Real-time monitoring dashboard
- Session results and analytics
- System activity logs

### Participant Features
- Join via QR code or 6-digit code
- Clean, distraction-free quiz interface
- Visual timer countdown
- Question navigation
- Instant feedback after completion
- Detailed answer review with explanations
- Live leaderboard

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- SQLite support (built into most PHP installations)
- mod_rewrite enabled (for .htaccess)

### Installation

1. **Clone or download this repository**
```bash
cd /var/www/html
git clone <your-repo-url> quiz-system
cd quiz-system
```

2. **Set up file permissions**
```bash
chmod 755 -R .
chmod 777 data logs assets/qrcodes
```

3. **Create admin credentials**
```bash
php setup-admin.php
```

Follow the prompts to create your admin username and password.

4. **Configure your web server**

For Apache, ensure `.htaccess` files are enabled in your virtual host configuration.

5. **Access the application**
- Main page: `http://your-domain/quiz-system/`
- Admin panel: `http://your-domain/quiz-system/admin/`
- Join quiz: `http://your-domain/quiz-system/participant/join.php`

## 📁 Project Structure

```
quiz-system/
├── admin/              # Admin panel
│   ├── index.php      # Dashboard
│   ├── topics.php     # Manage topics
│   ├── quizzes.php    # Manage quizzes
│   ├── upload.php     # Upload quiz files
│   ├── launch-quiz.php # Launch and QR code
│   ├── monitor-quiz.php # Real-time monitoring
│   ├── sessions.php   # Session management
│   └── logs.php       # System logs
├── participant/       # Participant interface
│   ├── join.php       # Join quiz
│   ├── waiting.php    # Waiting room
│   ├── quiz.php       # Take quiz
│   └── results.php    # View results
├── api/               # API endpoints
│   ├── submit-answer.php
│   ├── complete-quiz.php
│   ├── get-participants.php
│   └── check-session-status.php
├── assets/
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   ├── colors.json    # Participant colors
│   └── qrcodes/       # Generated QR codes
├── data/
│   ├── quizzes/       # Uploaded quiz files
│   └── quiz_database.sqlite # SQLite database
├── includes/          # PHP includes
│   ├── config.php     # Configuration
│   ├── db.php         # Database class
│   └── qr-generator.php # QR code generation
├── logs/              # Activity logs
└── index.php          # Landing page
```

## 📝 Creating Quizzes

### CSV Format

Create a CSV file with the following structure:

```csv
question,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty
"What is 2+2?","3","4","5","6","B","Basic addition","easy"
"What is the capital of France?","London","Paris","Berlin","Madrid","B","Paris is the capital of France","medium"
"Who wrote Romeo and Juliet?","Charles Dickens","William Shakespeare","Jane Austen","Mark Twain","B","Shakespeare wrote many famous plays","medium"
```

**Field Descriptions:**
- `question` - The question text (required)
- `option_a` through `option_d` - Answer choices (required)
- `correct_answer` - Letter A, B, C, or D (required)
- `explanation` - Why the answer is correct (optional)
- `difficulty` - easy, medium, or hard (optional, default: medium)

### JSON Format

```json
[
  {
    "question_text": "What is 2+2?",
    "option_a": "3",
    "option_b": "4",
    "option_c": "5",
    "option_d": "6",
    "correct_answer": "B",
    "explanation": "Basic addition",
    "difficulty": "easy"
  },
  {
    "question_text": "What is the capital of France?",
    "option_a": "London",
    "option_b": "Paris",
    "option_c": "Berlin",
    "option_d": "Madrid",
    "correct_answer": "B",
    "explanation": "Paris is the capital of France",
    "difficulty": "medium"
  }
]
```

### Nested JSON Format (also supported)

```json
{
  "quiz_name": "General Knowledge Quiz",
  "questions": [
    {
      "question_text": "What is 2+2?",
      "option_a": "3",
      "option_b": "4",
      "option_c": "5",
      "option_d": "6",
      "correct_answer": "B"
    }
  ]
}
```

## 🎮 Usage Guide

### For Administrators

1. **Create Topics**
   - Navigate to Admin → Topics
   - Create categories for your quizzes (e.g., "Mathematics", "Science")

2. **Upload Quiz**
   - Go to Admin → Upload Quiz
   - Select topic, set time limit, and number of questions
   - Upload your CSV or JSON file

3. **Launch Quiz**
   - Go to Admin → Quizzes
   - Click "Launch" on desired quiz
   - Share QR code or session code with participants
   - Wait for participants to join
   - Click "Start Quiz" when ready

4. **Monitor Progress**
   - Real-time view of participant progress
   - See completion rates and scores
   - End session when complete

5. **View Results**
   - Access from Sessions page
   - View leaderboard and statistics
   - Export results to CSV

### For Participants

1. **Join Quiz**
   - Scan QR code OR enter 6-digit code
   - Assigned a random color identity
   - Wait in waiting room

2. **Take Quiz**
   - Answer questions within time limit
   - Navigate between questions freely
   - See time remaining at all times

3. **View Results**
   - Instant results upon completion
   - See correct/incorrect answers
   - View explanations
   - Check your rank on leaderboard

## 🔧 Configuration

### config.php Settings

```php
// Time limits
define('SESSION_TIMEOUT', 7200); // 2 hours
define('DEFAULT_QUIZ_TIME_LIMIT', 600); // 10 minutes

// Limits
define('MAX_PARTICIPANTS_PER_QUIZ', 100);
```

### Database

The system uses SQLite by default. To switch to MySQL:

1. Update `includes/db.php`:
```php
private function __construct() {
    $dsn = "mysql:host=localhost;dbname=quiz_system;charset=utf8mb4";
    $this->db = new PDO($dsn, 'username', 'password');
    // ... rest of constructor
}
```

2. Run the schema manually or let the system create tables on first run.

## 📊 Sample Quiz Files

### Sample CSV (sample-quiz.csv)

```csv
question,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty
"What is the largest planet in our solar system?","Earth","Jupiter","Saturn","Mars","B","Jupiter is the largest planet","easy"
"Who painted the Mona Lisa?","Vincent van Gogh","Leonardo da Vinci","Pablo Picasso","Michelangelo","B","Da Vinci painted the Mona Lisa","medium"
"What is the speed of light?","299,792 km/s","150,000 km/s","400,000 km/s","250,000 km/s","A","Light travels at approximately 299,792 kilometers per second","hard"
"What is H2O?","Hydrogen Peroxide","Water","Salt","Sugar","B","H2O is the chemical formula for water","easy"
"In which year did World War II end?","1943","1944","1945","1946","C","World War II ended in 1945","medium"
```

## 🔒 Security Features

- Password-protected admin panel via .htaccess
- CSRF protection on all forms
- Input sanitization
- SQL injection prevention (PDO prepared statements)
- Session-based authentication
- No storage of participant personal information

## 📱 Mobile Optimization

The participant interface is fully optimized for mobile devices:
- Touch-friendly buttons
- Responsive layouts
- Fixed timer for easy viewing
- Optimized font sizes
- Minimal data usage

## 🐛 Troubleshooting

### QR Codes Not Generating
- Ensure `assets/qrcodes/` directory exists and is writable
- Check that `allow_url_fopen` is enabled in PHP
- Verify internet connectivity (uses Google Charts API by default)

### Database Errors
- Check `data/` directory permissions (should be 777)
- Ensure SQLite extension is enabled in PHP
- Verify database file path in `config.php`

### .htaccess Not Working
- Enable mod_rewrite in Apache
- Check AllowOverride is set to All in virtual host config
- Verify .htpasswd file path is absolute in .htaccess

### Session Issues
- Ensure sessions are enabled in php.ini
- Check session save path permissions
- Verify cookies are enabled in browser

## 📈 Analytics & Logs

### Available Logs
- `quiz-launches.log` - Quiz session creations and launches
- `quiz-participation.log` - Participant joins and completions
- `quiz-uploads.log` - Quiz file uploads
- `topics.log` - Topic management activities

### Viewing Logs
Navigate to Admin → Logs to view all system activities.

## 🚀 Advanced Features

### Question Pools
Upload more questions than needed per quiz. Each participant receives a random selection:
- Upload 30 questions
- Set "Questions Per Quiz" to 10
- Each participant gets different 10 questions

### Custom Colors
Edit `assets/colors.json` to customize participant color palette.

### Time Limits
Set different time limits for different quizzes:
- Quick polls: 2-5 minutes
- Standard quizzes: 10-15 minutes
- Comprehensive tests: 30-60 minutes

## 🤝 Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues.

## 📄 License

This project is open source and available under the MIT License.

## 🆘 Support

For issues, questions, or feature requests, please create an issue in the repository.

## 🎉 Credits

Built with:
- PHP
- SQLite
- Vanilla JavaScript
- QRCode.js
- Custom CSS (mobile-first design)

---

**Happy Quizzing! 🎯**
