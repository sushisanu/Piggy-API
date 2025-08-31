How to run (MAMP default port 8888 example)
-------------------------------------------
1. Place this folder in MAMP htdocs or point your virtual host to the `public` folder.
2. Copy `.env.example` to `.env` and edit DB credentials.
3. Import SQL: use `sql/schema.sql` into your MySQL (via DbGate/Navicat/phpMyAdmin).
4. Start server and visit endpoints (examples use port 8888):
   - POST /auth/register
   {
    "email": "testuser2@email.com",
    "username": "test5",
    "password": "test5"
   }
   - POST /auth/login
   - GET /pigs (Bearer)
   - GET  /foods/catalog
   - GET /foods/inventory
   - POST /pigs/feed (Bearer)
   {
    "pig_id": 1,
    "food_id": 1
   }
   - GET /quests/daily (Bearer)
   - POST /quests/claim (Bearer)
   {
    "quest_id": 1
   }
   - POST /inventory/add (Bearer) 
   {
    "food_id": 1,
    "qty": 5
   }
   - POST /admin/ban (Bearer admin)
   {
    "user_id": ...,
    "reason": "Testing the ban hammer!"
   }

Notes
-----
- Feed cooldown configurable via settings table or .env FEED_COOLDOWN_SECONDS
- Quests reset is handled lazily when `/quests/daily` is requested (creates today's quests if missing).
- For a strict reset, run the provided reset script `tools/reset_quests.php` via cron at midnight.

Video : https://youtu.be/-7N3HwkcELY
