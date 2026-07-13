# Physistrong

Physistrong is a self-hosted workout tracker. It runs on your own computer or
home server, keeps every bit of your training data on your own hardware, and
works great from a phone browser. No cloud account, no subscription, no
telemetry.

## What it does

- **Log workouts** with sets, reps, weight, time, and distance. Four exercise
  styles are supported: resistance training, timed holds, distance work, and
  intervals.
- **Track against targets.** Each set records what you planned and what you
  actually did.
- **Supersets and circuits.** Group exercises and log them round by round.
- **A ready-made exercise library.** Nearly 900 exercises are included out of
  the box, organized by equipment. Add your own exercises and equipment too.
- **Templates.** Save a workout structure you repeat, then start new sessions
  from it. Existing workouts can also be copied.
- **Progress charts and records.** See how any exercise trends over time and
  where your personal records stand.
- **Multiple people.** Everyone in the house can register their own account;
  each account's data is private.
- **Pounds or kilograms**, light and dark themes, phone-first design.

## What you need

- A computer that is always on when you want to log workouts (a small home
  server, a mini PC, or an old laptop is plenty).
- [Docker](https://docs.docker.com/engine/install/) with Docker Compose.
- The address you will type into your browser: either the machine's network
  address (for example `192.168.1.50`) or a hostname if your router or DNS
  can provide one.

## Install

1. Get the code onto the machine:

   ```
   git clone https://github.com/justincdotme/physistrong.git
   cd physistrong
   ```

2. Run the setup script with the address you will use in the browser:

   ```
   ./init.sh 192.168.1.50
   ```

   The script builds everything, starts the app, fills in the exercise
   library, and prints the address when it finishes. The first run takes a
   few minutes; it is safe to run again at any time.

3. Open `https://<your address>` in a browser. The first visit shows a
   security warning because the app creates its own certificate; choose
   "Advanced" and continue (or see [The certificate warning](#the-certificate-warning)
   below to make the warning go away for good).

4. Tap **Register**, create your account, and start logging.

## The certificate warning

Physistrong serves HTTPS with a certificate it generates itself, so browsers
warn that it is not from a public authority. The connection is still
encrypted. You can either accept the warning once per browser, or install the
certificate file `docker/nginx/certs/dev.crt` on your devices so they trust
it permanently (search "install trusted certificate" plus your device name
for the steps).

## Backups

Your data lives in a MySQL database inside Docker. Two good options:

- **Copy the database automatically.** The app's database can be archived on
  a schedule with any MySQL backup approach you already use. A simple,
  reliable pattern: stop the app, copy the database files, start the app
  again, and ship the archive to another disk or machine. If you keep the
  archives on storage that another backup tool already watches (an external
  drive, a NAS, a synced folder), you get off-site copies for free.
- **At minimum**, take an occasional manual copy of the whole project folder
  while the app is stopped (`docker compose down`, copy, then
  `docker compose up -d`).

To restore, stop the app, put the database files back where they came from,
and start it again.

## Updating

From the project folder:

```
git pull
./init.sh <your address>
```

The setup script rebuilds what changed and restarts the app. Your data is
untouched by updates.

## Email (optional)

Out of the box the app does not send real email; password-reset messages are
written to a log file instead (the newest file in `storage/logs/`; the
reset link is in the message body). If you want real password-reset emails, put your mail
provider's settings into the `.env` file (`MAIL_MAILER=smtp`, `MAIL_HOST`,
`MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`) and run
`docker compose -f docker-compose.yml up -d` to apply.

## Troubleshooting

- **The address will not load at all.** Make sure Docker is running
  (`docker ps` should list several `physistrong-` containers). If another
  program on the machine already uses the standard web ports, set
  `NGINX_HTTP_PORT` and `NGINX_HTTPS_PORT` to free ports in the `.env` file
  and re-run `./init.sh <your address>`, then browse to
  `https://<your address>:<https port>`.
- **The page is blank after an update.** Re-run `./init.sh <your address>`
  so the app's files are rebuilt.
- **Registering fails with a server error.** Re-run `./init.sh <your
  address>`; it repairs the app's sign-in keys and starting data.
- **I forgot my password and get no email.** See [Email](#email-optional):
  the reset link is written to the newest log file in `storage/logs/`.

## License

Released under the GNU Affero General Public License v3.0. See
[`LICENSE`](LICENSE).
