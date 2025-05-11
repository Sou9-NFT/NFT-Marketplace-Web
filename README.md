# NFT Marketplace Web

A full-featured NFT Marketplace web application built with Symfony. This platform allows users to create, auction, trade, and manage NFTs (Non-Fungible Tokens) with a modern, user-friendly interface for both front office (public) and back office (admin) operations.

## Features
- NFT creation and management
- Live auctions and bidding system
- Raffle system for NFT giveaways
- User wallet integration and top-up requests
- Artwork categories and search
- Admin dashboard for managing users, artworks, trades, and raffles
- AI-powered NFT description generator (Gemini integration)
- Secure authentication and role-based access

## Getting Started

### Prerequisites
- PHP 8.1+
- Composer
- MySQL or compatible database
- Node.js & npm (for asset building, if needed)
- Symfony CLI (recommended)

### Installation
1. **Clone the repository:**
   ```sh
   git clone https://github.com/yourusername/NFT-Marketplace-Web.git
   cd NFT-Marketplace-Web
   ```
2. **Install PHP dependencies:**
   ```sh
   composer install
   ```
3. **Configure environment:**
   - Copy `.env` to `.env.local` and set your database credentials:
     ```env
     DATABASE_URL="mysql://root:@127.0.0.1:3306/Sou9_NFT"
     ```
4. **Run database migrations:**
   ```sh
   symfony console doctrine:database:create
   symfony console make:migration
   symfony console doctrine:migrations:migrate
   ```
5. **Start the Symfony server:**
   ```sh
   symfony server:start
   # or
   symfony serve
   ```
6. **(Optional) Build assets:**
   If you modify or add frontend assets, use npm/yarn as needed.

### Useful Commands
- Clear cache:
  ```sh
  symfony console cache:clear
  ```
- See custom commands: [Commands.md](./Commands.md)

### Troubleshooting
- If you encounter `symfony.lock` or `composer.lock` errors, delete them and run `composer install` again.
- For SSL/cURL issues, set the `curl.cainfo` path in your `php.ini` to the provided `cacert.pem` file.
- If you have foreign key constraint issues, drop and recreate the database before running migrations.

## Folder Structure
- `src/` - Symfony PHP source code (Controllers, Entities, Services, etc.)
- `templates/` - Twig templates for frontend and backend
- `public/` - Public assets (entry point, CSS, JS, images)
- `assets/` - Source assets (if using asset mapper)
- `migrations/` - Doctrine migration files
- `config/` - Symfony configuration
- `tests/` - Automated tests

## Database
- Default database name: `Sou9_NFT`
- Update your `.env.local` as needed for your environment

## License
This project is for educational and demonstration purposes. See LICENSE file if present.

---

> **Note:**
> - Change asset paths in Twig files as needed (e.g., `css/style.css` → `front_office/css/style.css` or `back_office/css/style.css`).
> - For OAuth and API integrations, see `oath.md` and related documentation.
