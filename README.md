# Echo Atlas — a map of found sound

A small PHP + HTML/CSS/JS app: an interactive world map where anyone can drop a
pin at a location and attach a sound recording to it. Other visitors click the
pin to listen.

## How it works

- **Frontend**: Leaflet.js map, dark "sonar cartography" theme. Click **Place a
  sound**, then click anywhere on the map to open the upload form.
- **Backend**: plain PHP, no framework, no database — pins are stored as JSON
  in `data/pins.json`, audio files live in `uploads/`.
- **Ownership**: your browser remembers (via `localStorage`) which pins you
  created, so only you see the "Remove this pin" option for your own uploads.
  This is a lightweight convenience, not real auth.

## Requirements

- PHP 8.0+ with the `fileinfo` extension (used to verify real audio MIME
  types, not just file extensions).
- Any web server that can run PHP (Apache, Nginx+PHP-FPM), or just PHP's
  built-in server for local testing.

## Run it locally

```bash
cd soundmap
php -S localhost:8000
```

Then open **http://localhost:8000** in your browser.

## Deploying

1. Upload the whole `soundmap/` folder to your PHP host.
2. Make sure `data/` and `uploads/` are writable by the web server user:
   ```bash
   chmod 775 data uploads
   ```
3. That's it — no build step, no database setup.

## Project structure

```
soundmap/
├── index.php              Main page (map + modals)
├── assets/
│   ├── css/style.css       All styling
│   └── js/app.js           Map logic, upload flow, playback
├── api/
│   ├── functions.php       Shared helpers (JSON read/write with locking)
│   ├── upload.php          POST — validates & saves a new pin + sound file
│   ├── pins.php             GET  — returns all pins as JSON
│   └── delete.php           POST — removes a pin + its audio file
├── data/
│   ├── pins.json            All pin metadata (title, coords, filename…)
│   └── .htaccess            Blocks direct web access to this folder
└── uploads/
    ├── .htaccess             Blocks script execution inside this folder
    └── (uploaded audio files land here)
```

## Notes & limits

- Accepted audio types: mp3, wav, ogg, m4a, webm. Checked by real file
  content (via `fileinfo`), not just the extension — so renamed files are
  rejected.
- Max upload size: 15MB (edit `MAX_FILE_BYTES` in `api/functions.php` to
  change). Also check your PHP's `upload_max_filesize` / `post_max_size` in
  `php.ini` if you raise this.
- Filenames are randomized on upload; nothing about the original filename is
  trusted or stored.
- `uploads/.htaccess` disables script execution in that folder as a defense
  in depth measure (Apache only — if you're on Nginx, add an equivalent rule
  in your server block to deny `.php` execution under `/uploads/`).
- There's no real authentication. The "who can delete a pin" check is purely
  client-side (`localStorage`), so treat this as a fun/demo-grade project,
  not something to expose publicly without adding real access control,
  rate-limiting, and moderation if you expect untrusted users.

## Ideas to extend it

- Add audio waveform previews (e.g. wavesurfer.js) instead of the plain
  `<audio>` player.
- Add tags/categories (nature, city, music, voice…) with filterable pins.
- Add a report/moderation queue before a pin goes public.
- Swap the JSON file for SQLite/MySQL if you expect heavier traffic.
