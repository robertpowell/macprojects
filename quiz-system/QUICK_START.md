# 🚀 Quick Start Guide

Get your quiz system up and running in 5 minutes!

## Installation (3 steps)

### 1. Setup Admin Account
```bash
cd quiz-system
php setup-admin.php
```

Enter your admin username and password when prompted.

### 2. Set Permissions
```bash
chmod 777 data logs assets/qrcodes
```

### 3. Access the System
Open in your browser:
- Main page: `http://localhost/quiz-system/`
- Admin panel: `http://localhost/quiz-system/admin/`

## First Quiz (5 steps)

### 1. Create a Topic
- Go to **Admin → Topics**
- Click "Create New Topic"
- Name: "General Knowledge"
- Click "Create Topic"

### 2. Upload Sample Quiz
- Go to **Admin → Upload Quiz**
- Select topic: "General Knowledge"
- Quiz name: "Sample Quiz"
- Time limit: 10 minutes
- Questions per quiz: 10
- Upload file: `sample-quiz.csv`
- Click "Upload Quiz"

### 3. Launch the Quiz
- Go to **Admin → Quizzes**
- Find your quiz and click "Launch"
- You'll see a QR code and session code

### 4. Join as Participant
- Open a new browser tab (or use your phone)
- Go to: `http://localhost/quiz-system/participant/join.php`
- Enter the 6-digit session code
- You'll be assigned a random color

### 5. Start & Take the Quiz
- Back in admin panel, click "Start Quiz"
- Answer the questions in participant window
- View results when complete!

## Common Commands

### Create Admin Account
```bash
php setup-admin.php
```

### Check File Permissions
```bash
ls -la data logs assets/qrcodes
```

### View Logs
```bash
tail -f logs/quiz-launches.log
tail -f logs/quiz-participation.log
```

### Test Database Connection
```bash
sqlite3 data/quiz_database.sqlite "SELECT COUNT(*) FROM topics;"
```

## Quick Troubleshooting

### Can't access admin panel?
```bash
php setup-admin.php
```

### Database errors?
```bash
chmod 777 data
rm data/quiz_database.sqlite
php setup-admin.php
```

### QR codes not showing?
```bash
mkdir -p assets/qrcodes
chmod 777 assets/qrcodes
```

## File Structure at a Glance

```
quiz-system/
├── admin/          # Admin panel pages
├── participant/    # Quiz-taking interface
├── api/           # Backend API endpoints
├── data/          # Database & uploaded files
├── logs/          # Activity logs
├── sample-quiz.csv # Example quiz file
└── README.md      # Full documentation
```

## What's Next?

1. **Customize**: Edit `assets/colors.json` to change participant colors
2. **Create Quizzes**: Use the sample files as templates
3. **Monitor**: Check Admin → Logs for all activities
4. **Export**: Download results as CSV from session results
5. **Mobile Test**: Try joining from your phone!

## Need Help?

- 📖 Read the full [README.md](README.md)
- 🔧 Check [INSTALLATION.md](INSTALLATION.md) for detailed setup
- 📝 View sample quiz files for format reference
- 🐛 Check logs/ directory for error details

## Pro Tips

1. **Create question pools**: Upload 30 questions but set quiz to only use 10 - each participant gets different questions!
2. **Test mode**: Launch a quiz, join yourself, then cancel before starting
3. **Multiple topics**: Organize quizzes by subject for easy management
4. **Time limits**: Adjust based on question difficulty
5. **Mobile first**: Participants can use phones - no app needed!

---

**Ready to quiz! 🎯 Have fun!**
