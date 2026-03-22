# ECHOBASE – Your Rebel Project Command Center

*A beautiful, self-hosted Kanban board for personal projects and small teams.*

Built with love for anyone who wants a clean, fast, no-account-needed board that feels like it belongs on Hoth.

## ✨ Features

- Drag & drop between 5 status columns
- Click any card to edit (or use the ✏️ button)
- Full modal with Details / Notes / Documents tabs
- File uploads & document management
- Priority badges, tags, and rich descriptions
- Light/dark theme with one click
- Toast notifications (no pop-up hell)
- Safe Delete Project (with confirmation)
- Zero dependencies — pure PHP + vanilla JS

## 🚀 Quick Start

1. Clone the repo
   ```bash
   git clone https://github.com/steppsr/echobase.git
   cd echobase
   ```

2. Create the database
   ```bash
   # Run this once
   mysql -u youruser -p < database-setup.sql
   ```

3. Configure `config.php` with your DB credentials

4. Make sure the `uploads` folder is writable:
   ```bash
   chmod 777 uploads
   ```
5. Drop the files on your local web server (_Warning: this is not a secure application and shouldn't be hosted publically._)

## ⚠️ Security – Credentials

1. Copy `.env.example` to `.env` (or create `.env` manually)
2. Fill in your real database credentials
3. Never commit `.env` to git

   ```bash
   cp .env.example .env
   # then edit .env with your values
   ```

## 📁 Folder Structure
    
    echobase/
    ├── index.php
    ├── config.php
    ├── api/
    │   ├── projects.php
    │   ├── notes.php
    │   └── documents.php
    ├── uploads/
	├── database-setup.sql
    └── assets/
    	├── css/main.css
    	└── js/app.js
    	
## 🛠 Tech Stack

* PHP 8.2+
* MySQL
* Vanilla JavaScript + CSS
* No frameworks. No bloat. Pure signal.

## Contributing

Pull requests welcome! Whether you want to add due dates, mobile improvements, or turn the whole thing into a full Death Star command interface, the Force is strong with this one.

