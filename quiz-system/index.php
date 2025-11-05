<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🎯 Interactive Quiz System</h1>
        </div>
    </header>

    <main>
        <div class="container" style="max-width: 800px; margin-top: 4rem;">
            <div class="card text-center">
                <div style="font-size: 5rem; margin: 2rem 0;">🎓</div>
                <h2>Welcome to the Quiz System</h2>
                <p style="font-size: 1.25rem; margin: 2rem 0; line-height: 1.8;">
                    An interactive platform for creating and taking multiple choice quizzes.
                    Perfect for classrooms, training sessions, and educational activities.
                </p>

                <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                    <a href="participant/join.php" class="btn btn-primary btn-block" style="padding: 2rem;">
                        <div style="font-size: 3rem;">📱</div>
                        <div style="font-size: 1.5rem; margin-top: 1rem;">Join Quiz</div>
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.5rem;">
                            Enter your code
                        </div>
                    </a>

                    <a href="admin/" class="btn btn-secondary btn-block" style="padding: 2rem;">
                        <div style="font-size: 3rem;">⚙️</div>
                        <div style="font-size: 1.5rem; margin-top: 1rem;">Admin Panel</div>
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.5rem;">
                            Manage quizzes
                        </div>
                    </a>
                </div>
            </div>

            <div class="card">
                <h3>Features</h3>
                <ul style="line-height: 2; text-align: left;">
                    <li>✅ Easy quiz management with CSV/JSON upload</li>
                    <li>✅ QR code generation for quick participant joining</li>
                    <li>✅ Random question selection from question pools</li>
                    <li>✅ Real-time progress tracking</li>
                    <li>✅ Timer-based quizzes</li>
                    <li>✅ Anonymous participation with color-coded identities</li>
                    <li>✅ Mobile-optimized interface</li>
                    <li>✅ Instant results and leaderboards</li>
                    <li>✅ Comprehensive logging system</li>
                </ul>
            </div>

            <div class="card">
                <h3>How It Works</h3>
                <div class="stats-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem;">1️⃣</div>
                        <h4>Create</h4>
                        <p>Upload your quiz questions via CSV or JSON</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem;">2️⃣</div>
                        <h4>Launch</h4>
                        <p>Start a session and share the QR code</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem;">3️⃣</div>
                        <h4>Play</h4>
                        <p>Participants join and answer questions</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="text-align: center; padding: 2rem; color: #666; margin-top: 4rem; border-top: 1px solid #e5e7eb;">
        <p>Quiz System | Built with PHP, JavaScript, and SQLite</p>
    </footer>
</body>
</html>
