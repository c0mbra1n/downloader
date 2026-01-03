# 📥 Private Web Downloader

A self-hosted, private web downloader application built with Laravel. Download files from direct URLs and video platforms (YouTube, Vimeo, etc.) with background processing, file management, and media streaming capabilities.

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## ✨ Features

- **🔗 Multi-Source Downloads**
  - Direct file URLs (via aria2c with multi-connection support)
  - YouTube, Vimeo, Twitter, TikTok, and 20+ video platforms (via yt-dlp)
  
- **⚡ Background Processing**
  - Laravel Queue-based downloads (non-blocking)
  - Real-time progress tracking
  - Auto-retry on failure
  
- **📁 File Manager**
  - Categorized view (Videos, Audio, Documents, Archives, Others)
  - Grid and List view toggle
  - Download and delete files
  
- **🎬 Media Streaming**
  - In-browser video/audio playback
  - HTTP Range Request support (seeking works!)
  
- **📱 Responsive UI**
  - Material Design flat theme
  - Mobile-friendly with hamburger menu
  - Dark/Light optimized
  
- **🔐 Single User Auth**
  - Protected routes with Laravel authentication
  - Session-based login

## 📋 Requirements

- PHP >= 8.2
- Composer
- MySQL / SQLite
- [aria2c](https://aria2.github.io/) - for direct file downloads
- [yt-dlp](https://github.com/yt-dlp/yt-dlp) - for video platform downloads
- [ffmpeg](https://ffmpeg.org/) - for video processing (optional)

## 🚀 Installation

### 1. Clone the repository

```bash
git clone https://github.com/c0mbra1n/downloader.git
cd downloader
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database:

```env
DB_CONNECTION=sqlite
# or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=downloader
# DB_USERNAME=root
# DB_PASSWORD=
```

### 4. Run migrations and seed admin user

```bash
php artisan migrate --seed
```

### 5. Create storage link

```bash
php artisan storage:link
```

### 6. Install system dependencies

**macOS:**
```bash
brew install aria2 yt-dlp ffmpeg
```

**Ubuntu/Debian:**
```bash
sudo apt install aria2 ffmpeg
pip install yt-dlp
```

## 🏃 Running the Application

### Start the development server

```bash
php artisan serve --port=8000
```

### Start the queue worker (required for downloads!)

In a separate terminal:

```bash
php artisan queue:work
```

### Access the application

Open http://localhost:8000 in your browser.

**Default credentials:**
- Email: `admin@example.com`
- Password: `password`

## 📁 Project Structure

```
app/
├── Enums/
│   └── DownloadStatus.php    # Status enum (queued/downloading/completed/failed)
├── Http/Controllers/
│   ├── Auth/LoginController.php
│   ├── DownloadController.php
│   ├── FileManagerController.php
│   └── StreamController.php   # Media streaming with Range Request
├── Jobs/
│   └── DownloadJob.php        # Background download processing
├── Models/
│   └── Download.php
└── Services/
    ├── Aria2Service.php       # aria2c wrapper for direct URLs
    └── YtDlpService.php       # yt-dlp wrapper for video platforms

storage/app/downloads/
├── videos/
├── audios/
├── documents/
├── archives/
└── others/
```

## ⚙️ Configuration

Environment variables for download settings (`.env`):

```env
DOWNLOAD_MAX_CONNECTIONS=16     # Max connections per file
DOWNLOAD_SPLIT_COUNT=16         # File splits for parallel download
DOWNLOAD_MAX_SIMULTANEOUS=5     # Max concurrent downloads
```

## 📝 Usage

### Adding a download

1. Go to **Downloads** page
2. Paste a URL (direct file or video platform URL)
3. Click **Download**
4. The file will be queued and processed by the queue worker

### Playing media

1. Go to **File Manager**
2. Click **▶ Play** on video/audio files
3. Media plays in a modal with seeking support

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- [aria2](https://aria2.github.io/) - Ultra fast download utility
- [yt-dlp](https://github.com/yt-dlp/yt-dlp) - Video downloader
- [Laravel](https://laravel.com/) - PHP Framework
- [Alpine.js](https://alpinejs.dev/) - Lightweight JS framework
