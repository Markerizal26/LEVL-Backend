# Production deployment with Docker and K3s

## Architecture

- K3s single node on the VPS.
- Laravel Octane/FrankenPHP: one pod, two workers.
- Queue worker: one pod.
- Scheduler: one CronJob per minute.
- PostgreSQL: existing VPS PostgreSQL or a private PostgreSQL endpoint.
- Redis: one small persistent pod managed by Kubernetes.
- Uploaded media: `levl-media` 30Gi PVC backed by `/srv/levl/media` on the VPS,
  mounted in the container at `/app/storage/app`.

The media PVC is deliberately separate from the application image. Replacing the
image does not delete uploaded files. The PVC is local to the VPS, so it must be
backed up separately.

## Prerequisites

This guide assumes a fresh Ubuntu 22.04/24.04 VPS with a public IPv4 address,
root/sudo access, and a DNS name such as `api.example.com`. The 2 vCPU, 4GiB RAM,
and 60GiB SSD plan is suitable for the single-node layout below.

## One-time VPS setup

Update the OS and install the host services. PostgreSQL is kept outside K3s so
its data is not coupled to application pods:

```bash
sudo apt update
sudo apt full-upgrade -y
sudo apt install -y ca-certificates curl git openssl postgresql postgresql-contrib ufw
sudo systemctl enable --now ssh postgresql
```

If the VPS has swap enabled, disable it before installing K3s and remove its
entry from `/etc/fstab` after verifying the server has enough memory:

```bash
sudo swapoff -a
free -h
```

Allow only SSH, HTTP, and HTTPS from the public internet. Do not expose
PostgreSQL, Redis, or the Kubernetes API publicly:

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
```

Create the PostgreSQL database and user:

```bash
sudo -u postgres psql
```

Run this inside `psql`, replacing the password:

```sql
CREATE ROLE levl LOGIN PASSWORD 'REPLACE_WITH_A_STRONG_DATABASE_PASSWORD';
CREATE DATABASE levl OWNER levl;
\connect levl
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;
\q
```

Find the PostgreSQL configuration files:

```bash
sudo -u postgres psql -Atc "SHOW config_file; SHOW hba_file;"
```

In `postgresql.conf`, listen on the VPS private IP (replace `10.0.0.10`):

```ini
listen_addresses = '10.0.0.10,127.0.0.1'
```

In `pg_hba.conf`, allow only the K3s pod network and the VPS private address:

```text
host    levl    levl    10.42.0.0/16    scram-sha-256
host    levl    levl    10.0.0.10/32    scram-sha-256
```

Then restart PostgreSQL. The pod-network rule is private cluster traffic; do
not replace it with `0.0.0.0/0`:

```bash
sudo systemctl restart postgresql
sudo ufw allow from 10.42.0.0/16 to any port 5432 proto tcp
```

Create a dedicated deployment user:

```bash
sudo adduser --disabled-password --gecos "" levl
sudo install -d -m 700 -o levl -g levl /home/levl/.ssh
sudo install -d -m 750 -o levl -g levl /opt/levl/repository
```

Generate an SSH key on your workstation for GitHub Actions. Put the private
key in the repository secret `SSH_PRIVATE_KEY`, and append the public key to
`/home/levl/.ssh/authorized_keys` on the VPS:

```bash
ssh-keygen -t ed25519 -C "github-actions-levl" -f ~/.ssh/levl_actions
```

The VPS also needs a separate read-only GitHub deploy key because the workflow
checks out `main` on the server:

```bash
sudo -u levl ssh-keygen -t ed25519 -C "levl-vps-readonly" \
  -f /home/levl/.ssh/github_deploy -N ""
sudo -u levl ssh-keyscan github.com | sudo tee -a /home/levl/.ssh/known_hosts >/dev/null
sudo -u levl cat /home/levl/.ssh/github_deploy.pub
```

Add that public key in GitHub repository **Settings → Deploy keys** with
read-only access, then create `/home/levl/.ssh/config`:

```sshconfig
Host github.com
  IdentityFile /home/levl/.ssh/github_deploy
  IdentitiesOnly yes
```

Fix ownership and clone the repository:

```bash
sudo chown -R levl:levl /home/levl/.ssh /opt/levl
sudo chmod 600 /home/levl/.ssh/github_deploy
sudo -u levl git clone git@github.com:Markerizal26/LEVL-Backend.git /opt/levl/repository
```

Install K3s:

```bash
curl -sfL https://get.k3s.io | sh -
sudo k3s kubectl get nodes
```

Allow the deployment user to run only the K3s CLI without an interactive sudo
password. The workflow uses this for `kubectl` and container image pruning:

```bash
printf '%s\n' 'levl ALL=(root) NOPASSWD: /usr/local/bin/k3s' | sudo tee /etc/sudoers.d/levl-k3s >/dev/null
sudo chmod 440 /etc/sudoers.d/levl-k3s
sudo visudo -cf /etc/sudoers.d/levl-k3s
sudo -u levl sudo -n k3s kubectl get nodes
```

Prepare the persistent media directory outside `/var/www`:

```bash
sudo mkdir -p /srv/levl/media
sudo chown -R 33:33 /srv/levl/media
sudo chmod 750 /srv/levl/media
```

The Kubernetes PersistentVolume maps this directory to the Laravel storage
disk. The image can be replaced without deleting uploaded files.

Install cert-manager for automatic Let's Encrypt certificates:

```bash
sudo k3s kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.21.2/cert-manager.yaml
sudo k3s kubectl -n cert-manager wait --for=condition=Available deployment/cert-manager --timeout=300s
```

Create the namespace and the private GHCR pull secret. The token needs read-only
package access:

```bash
sudo k3s kubectl create namespace levl
read -rsp "GHCR read token: " GHCR_READ_TOKEN; echo
sudo k3s kubectl -n levl create secret docker-registry ghcr-auth \
  --docker-server=ghcr.io \
  --docker-username=GITHUB_USERNAME \
  --docker-password="$GHCR_READ_TOKEN"
unset GHCR_READ_TOKEN
```

Create `/opt/levl/.env.production` on the VPS with mode `600`. Do not commit it.
It must contain the PostgreSQL connection and application secrets, for example:

```bash
sudo -u levl nano /opt/levl/.env.production
sudo chmod 600 /opt/levl/.env.production
```

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=replace-with-a-new-key
APP_URL=https://api.example.com
JWT_SECRET=replace-with-a-new-secret
SUPERADMIN_EMAIL=admin@example.com
SUPERADMIN_NAME=Superadmin
SUPERADMIN_USERNAME=superadmin
SUPERADMIN_PASSWORD=replace-with-a-random-password-at-least-12-characters

DB_CONNECTION=pgsql
DB_HOST=10.0.0.10
DB_PORT=5432
DB_DATABASE=levl
DB_USERNAME=levl
DB_PASSWORD=replace-with-a-strong-password

REDIS_HOST=levl-redis
REDIS_PORT=6379
REDIS_PASSWORD=null

FILESYSTEM_DISK=public
MEDIA_DISK=public
UPLOAD_MAX_FILE_SIZE_KB=2048
UPLOAD_MAX_VIDEO_SIZE_KB=51200
```

Load it into Kubernetes:

```bash
sudo k3s kubectl -n levl create secret generic levl-env \
  --from-env-file=/opt/levl/.env.production \
  --dry-run=client -o yaml | sudo k3s kubectl apply -f -
```

Use the VPS private IP or a Kubernetes Service name for `DB_HOST`; `127.0.0.1`
inside the application pod is the application container itself.

After the first successful application rollout, initialize production master
data and the superadmin account once:

```bash
sudo k3s kubectl -n levl exec deploy/levl-app -- \
  php artisan db:seed --class=Database\\Seeders\\ProductionSeeder --force
```

The command reads `SUPERADMIN_EMAIL` and `SUPERADMIN_PASSWORD` from the
Kubernetes Secret and refuses a password shorter than 12 characters. Do not run
the general `DatabaseSeeder` in production because it creates demo/UAT data.

## DNS and TLS

Before the first push to `main`:

1. Point an `A` record such as `api.example.com` to the VPS public IP.
2. Open TCP ports `80` and `443` on the VPS firewall.
3. Replace `api.domain-anda.com` in `k8s/configmap.yaml` and
   `k8s/ingress.yaml` with the real API domain.
4. Replace `admin@example.com` in `k8s/cluster-issuer.yaml` with a real email.

The Ingress annotation requests the `levl-tls` Secret automatically from
Let's Encrypt through cert-manager. Do not create that Secret manually.

## GitHub Actions secrets

Configure these repository secrets:

- `DEPLOY_SERVER` — VPS public IP or hostname
- `DEPLOY_USER` — `levl`
- `SSH_PRIVATE_KEY` — private key matching `/home/levl/.ssh/authorized_keys`
- `DEPLOY_PATH` — `/opt/levl/repository`
- `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` if deployment notifications are used

The workflow already has `packages: write`. When the first image is published,
ensure the `levl-backend` GHCR package is connected to this repository and that
the repository workflow has package admin permission; otherwise the final
previous-image cleanup job cannot delete old versions.

Push the prepared changes to `main`:

```bash
git add .
git commit -m "Deploy LEVL to local VPS with K3s"
git push origin main
```

The workflow builds `ghcr.io/<owner>/<repository>:<commit-sha>`, deploys that
immutable image, waits for migration and rollout completion, prunes unused
containerd images on the VPS, and then keeps only one GHCR package version.

The repository/package must grant the workflow admin permission to delete package
versions. If that permission is missing, deployment succeeds but the registry
cleanup job fails and must be fixed in GitHub Package settings.

## Deployment behavior

Every push to `main` runs:

1. Composer, PHP syntax, and frontend validation.
2. Docker build and push to GHCR.
3. Database migration Job.
4. Laravel, queue, scheduler, and Ingress apply.
5. Rollout health checks.
6. VPS image prune.
7. GHCR cleanup, retaining only the current package version.

Because old images are deleted, this deployment intentionally does not support
image rollback. Keep database migrations backward-compatible during a release.

## Verification and troubleshooting

On GitHub, wait for `validate`, `build`, `deploy`, and `cleanup-registry` to
finish successfully. On the VPS, verify the workloads and certificate:

```bash
sudo k3s kubectl -n levl get pods,svc,ingress,certificate
sudo k3s kubectl -n cert-manager get pods
curl -I https://api.example.com/up
```

Common failures:

- `ImagePullBackOff`: recreate `ghcr-auth` with a GitHub classic PAT having
  `read:packages` and verify the package access settings.
- Migration Job fails: check `DB_HOST`, database password, PostgreSQL
  `listen_addresses`, and `pg_hba.conf`.
- Certificate stays pending: verify the DNS A record and that ports 80/443 are
  reachable from the internet.
- PVC stays pending: verify `/srv/levl/media` exists and inspect
  `sudo k3s kubectl get pv,pvc -A`.
- API is not ready: inspect
  `sudo k3s kubectl -n levl logs deploy/levl-app`.

Back up `/srv/levl/media` and the PostgreSQL data separately. The media path is
outside the repository and image, but it is still physically stored on this VPS.
