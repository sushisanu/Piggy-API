PiggyQuest - Pure PHP Backend (Complete per assignment)
======================================================

How to run (MAMP default port 8888 example)
-------------------------------------------
1. Place this folder in MAMP htdocs or point your virtual host to the `public` folder.
2. Copy `.env.example` to `.env` and edit DB credentials.
3. Import SQL: use `sql/schema.sql` into your MySQL (via DbGate/Navicat/phpMyAdmin).
4. Start server and visit endpoints (examples use port 8888):
   - POST /auth/register
   - POST /auth/login
   - GET /pigs (Bearer)
   - POST /pigs/feed (Bearer)
   - GET /quests/daily (Bearer)
   - POST /quests/claim (Bearer)
   - POST /inventory/add (Bearer)
   - POST /admin/ban (Bearer admin)

Notes
-----
- Feed cooldown configurable via settings table or .env FEED_COOLDOWN_SECONDS
- Quests reset is handled lazily when `/quests/daily` is requested (creates today's quests if missing).
- For a strict reset, run the provided reset script `tools/reset_quests.php` via cron at midnight.
