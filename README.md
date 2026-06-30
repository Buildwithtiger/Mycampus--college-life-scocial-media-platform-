# Mycampus--college-life-scocial-media-platform-
"My Campus is a social platform connecting college students, teachers, and staff. It features a live feed for news and events, project groups for collaboration, real-time messaging, assignment sharing, attendance tracking, and event discovery—all designed to make campus life more interactive and engaging."

## 📌 Overview

**My Campus** is a dedicated social networking platform designed specifically for college and university environments. It bridges the gap between students, teachers, and staff by providing a centralized digital space for communication, collaboration, and community building.【4†L4-L6】

The system enables students to share posts (text, images, videos), follow peers, like and comment on content, and receive real-time notifications. It includes a QR code scanner for quick event check-ins and profile sharing, along with an admin panel for managing campus events and activities. A privacy-aware follow system with public/private profiles ensures secure and meaningful interactions.【4†L4-L6】

---

## 🎯 Problem Statement

Student interaction and information sharing within educational institutions have become increasingly fragmented and inefficient. Traditional methods such as notice boards, email chains, and WhatsApp groups lead to:

- **Limited Engagement** - Students are less likely to participate in campus activities when communication is scattered.
- **Missed Opportunities** - Important announcements, event details, and collaboration requests often go unnoticed.
- **Lack of Real-Time Interaction** - No efficient way to share posts, like, comment, or receive instant notifications.
- **Privacy Concerns** - Public group chats do not allow users to control who sees their content.
- **No Centralized Platform** - Students must juggle multiple apps for academic updates, social interaction, and event participation.
- **Inefficient Event Management** - Manual check-ins and paper-based attendance are time-consuming and error-prone.【4†L8-L9】

---

## ✨ Features

### 🔹 Social Interaction
- Share posts with text, images, and videos
- Like and comment on content
- Follow/Unfollow system with public/private profile options
- Mutual follow requirement for private profiles
- Real-time notifications for likes, comments, and follows【4†L4-L6】

### 🔹 Event Management
- Admin panel to create, update, and delete campus events
- Event details with images, descriptions, dates, and venues
- Upcoming events displayed in a carousel on the homepage
- QR code scanner for quick event check-ins and attendance tracking【4†L4-L6】

### 🔹 User Features
- Easy registration and login
- Profile management (update picture, bio, class, year)
- Infinite scroll feed for seamless browsing
- Search functionality to find other students by username
- Mobile-responsive design with bottom navigation【4†L15-L16】

---

## 🛠️ Tech Stack

### Frontend
- **HTML / CSS** - Structure and styling
- **JavaScript** - Interactive elements and dynamic content
- **Responsive Design** - Mobile-first approach with Bootstrap/Tailwind

### Backend
- **PHP** - Server-side logic and database interaction

### Database
- **MySQL** - Data storage and management

### Additional Tools
- **QR Code Library** - For event check-ins and profile sharing
- **AJAX** - For real-time notifications and seamless updates【4†L18】

---

## 💻 Hardware & Software Requirements

### Hardware
- **Hard Disk**: 6 GB & Above
- **RAM**: 8 GB
- **Processor**: Intel Pentium Gold or equivalent【4†L18】

### Software
- **Operating System**: Windows 7 and above (latest version recommended)
- **Development Environment**: Visual Studio Code
- **Server**: Apache / XAMPP / WAMP
- **Languages**: PHP, JavaScript, CSS
- **Database**: MySQL【4†L18】

---

## 📂 Project Structure

```
my-campus/
├── index.php                     # Main feed / homepage (13 KB version)
├── login.php                     # User login page
├── register.php                  # User registration page
├── profile.php                   # User profile page
├── posts.php                     # Posts management / display
├── events.php                    # Events listing & management
├── students.php                  # Student directory / search
├── chat.php                      # Real‑time chat interface
├── notifications.php             # Notification center
├── settings.php                  # User settings
├── leaderbord.php                # Leaderboard (engagement stats)
├── scanner.php                   # QR code scanner for check‑ins
├── scanner_handler.php           # Backend for QR scanning
├── save_attendance.php           # Save attendance records
├── add_post.php                  # Create a new post
├── add_feed_comment.php          # Add comment to feed post
├── delete_feed_comment.php       # Delete a feed comment
├── comment_handler.php           # Comment actions (like, delete)
├── follow_handler.php            # Follow/unfollow logic
├── like_handler.php              # Like/unlike posts
├── get_comments.php              # Fetch comments (AJAX)
├── get_feed_comments.php         # Fetch feed comments (AJAX)
├── get_message.php               # Fetch messages (AJAX)
├── get_posts.php                 # Fetch posts (AJAX)
├── get_user_posts.php            # Fetch user‑specific posts (AJAX)
├── message_handler.php           # Send/receive messages
├── mark_notification_read.php    # Mark notification as read
├── search_handler.php            # Search users (AJAX)
├── logout.php                    # Logout script
├── con.php                       # Database connection (legacy)
├── config.php                    # Configuration (DB, constants)
├── debug_paths.php               # Debugging utility
├── ap.php                        # API endpoint (maybe)
├── R.PHP                         # Unknown (possibly rename later)
├── ip.html                       # IP detection page
├── ddd.sql                       # Database dump / schema
├── admin/                        # Admin panel (dashboard, events CRUD)
│   └── (admin PHP files)
├── assets/                       # CSS, JS, images, etc.
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/                      # User‑uploaded content
│   ├── events/                   # Event images
│   ├── posts/                    # Post images/videos
│   └── profiles/                 # Profile pictures
├── events/                       # (Optional) Additional event pages
├── posts/                        # (Optional) Additional post pages
└── profiles/                     # (Optional) Profile‑related includes
```

---

## 🚀 Installation Guide

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/my-campus.git
```

### Step 2: Set Up Database
1. Open phpMyAdmin or MySQL command line
2. Create a new database: `mycampus_db`
3. Import the SQL file from `/sql/database.sql`

### Step 3: Configure Database Connection
Update the `includes/config.php` file with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mycampus_db');
```

### Step 4: Run the Application
- Place the project folder in your web server directory (e.g., `htdocs` for XAMPP)
- Start Apache and MySQL services
- Access the application at: `http://localhost/my-campus/`

---

## 📖 Usage Guide

### For Students
1. **Register** with your college email and create a profile
2. **Explore** the campus feed for posts, announcements, and events
3. **Connect** with peers by following them (mutual follow required for private profiles)
4. **Share** your thoughts, projects, and updates via posts
5. **Engage** with content through likes and comments
6. **Discover** and **check-in** to campus events using QR codes
7. **Search** for other students by username

### For Teachers & Admin
1. **Login** with admin credentials
2. **Manage** campus events (create, update, delete)
3. **Post** announcements and important updates
4. **Monitor** student engagement and activity
5. **Track** event attendance via QR check-ins【4†L15-L16】

---

## 🖼️ Screenshots

| Feature | Description |
|---------|-------------|
| **Home Feed** | Infinite scroll with posts from followed users |
| **Profile Page** | User information, posts, and privacy settings |
| **Admin Dashboard** | Event creation and management interface |
| **QR Scanner** | Quick event check-in and profile sharing |
| **Notifications** | Real-time alerts for interactions |

---

## ⚠️ Limitations

- Real-time notifications depend on internet connectivity; poor network access may result in delayed updates.
- Requires smartphones or computers with modern browsers; may limit accessibility for students without personal devices.
- Initial setup cost includes web hosting, domain registration, and database configuration.
- Relies on active participation; low user adoption can reduce overall engagement.
- Privacy and content moderation are manual to some extent; inappropriate posts may not be caught instantly.
- Current version is web-based; no native iOS/Android apps available.
- QR code scanning requires a working camera on the user's device.
- Scalability to very large institutions may require additional server resources.
- Academic integration (LMS, chat messaging) is not included in the current version.【4†L26】

---

## 🔮 Future Enhancements

### 🤖 AI & Machine Learning
- Predictive content recommendations based on user interests
- Automatic friend/study group suggestions using collaborative filtering

### 📱 Mobile Apps
- Native Android and iOS apps with push notifications
- Offline mode, voice/video posts, and real-time chat

### 📚 Academic Integration
- Link with LMS for assignment deadlines and exam schedules
- Study groups, note sharing, and project collaboration

### 🏆 Gamification
- Badges, points, and leaderboards for active participation
- Digital certificates for top contributors

### 📊 Advanced Analytics
- Insights on student engagement and popular events
- Community sentiment analysis for administrators

### 🔗 Cross-Campus Collaboration
- Connect students from different colleges and universities
- Inter-college events and knowledge sharing

### 🛡️ Enhanced Privacy & Moderation
- AI-based content moderation for spam and inappropriate content
- Granular privacy controls (hide specific posts from certain followers)【4†L27-L28】

---

## 👥 Contributors

- **Onkar Shivaji Sawant** - Developer
- **Prof. A.S. Tanpure** - Project Guide【4†L1】

---

## 📚 References

- **Google** - www.google.com
- **W3Schools** - www.w3schools.com
- **ChatGPT** - www.chatgpt.com【4†L30】

---

## 📄 License

This project is developed for academic purposes as part of the TYBBA(CA) curriculum at Khed Taluka Shikshan Prasarak Mandal's Hutatma Rajguru Mahavidyalaya, Rajgurunagar, Pune.【4†L1】

---

## 🙏 Acknowledgements

I express my sincere and profound thanks to our guide, **Prof. A.S. Tanpure**, for providing valuable guidance and pointing me in the right direction. I also extend my gratitude to the Head of Department, the Principal, and the entire faculty for their encouragement and support. Special thanks to my family and friends for their constant motivation. Lastly, I appreciate all the users and testers who helped in making this project better.【4†L5】

---

> **My Campus** - *Making campus life smarter, more connected, and engaged.*
