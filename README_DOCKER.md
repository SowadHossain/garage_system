# Running Screw Dheela Management System with Docker

This repository can be run with Docker (PHP + Apache) and a MySQL container using docker-compose.

Quick steps

1. Copy this project to a machine with Docker and Docker Compose installed.
2. (Optional) Edit `docker-compose.yml` to change database passwords and ports.
3. Build and start the stack:

```bash
docker compose up --build -d
```

4. Open the app in your browser:

http://localhost:8080

Notes

- The web root is `public/` — the Dockerfile configures Apache to serve from that folder.
- The app reads DB settings from environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`) — `config/db.php` supports these.
- The compose file will create a MySQL database named `garage_db` and a user `garage_user` automatically using the official MySQL image initialization.

Security reminders (change these before production)

- Replace `root_password_change_me` with a strong MySQL root password in `docker-compose.yml`.
- Do not use the example DB password in production — change `GaragePass123!` to a secure secret.
- Run behind HTTPS in production and avoid binding MySQL to the host network unless required.

If you want an initial SQL schema imported at container startup, place a `.sql` file in `docker/mysql/init/` and map it to `/docker-entrypoint-initdb.d/` in the `db` service in `docker-compose.yml` (I can add this for you if you give me a dump or approve an inferred schema).
