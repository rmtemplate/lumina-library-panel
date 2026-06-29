# syntax=docker/dockerfile:1

##############################################################################
# Stage 1 — Frontend assets (Vite)
#
# Built independently from the PHP image so this Nginx image is fully
# self-contained and buildable on its own — no runtime dependency on, or
# shared volume with, the app/php image just to obtain static files.
##############################################################################
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

##############################################################################
# Stage 2 — Nginx runtime
##############################################################################
FROM nginx:1.27-alpine AS runtime

LABEL org.opencontainers.image.title="lumina-library-nginx" \
      org.opencontainers.image.description="Lumina Library — Nginx reverse proxy (static assets + PHP-FPM/Reverb upstream)"

# Static, request-independent files only — index.php is NOT copied (PHP
# execution happens entirely in the `app` container; Nginx only needs the
# front controller's *name* for fastcgi routing, not its contents).
COPY public/ /var/www/html/public/
COPY --from=frontend /app/public/build /var/www/html/public/build

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/ /etc/nginx/conf.d/

# index.php is intentionally NOT copied — PHP execution happens entirely
# in the `app` container; Nginx only ever forwards matching requests to
# it via fastcgi_pass (see conf.d/app.conf), it never reads the file.
# /var/www/html/storage is an empty mount point here — the real directory
# is the `storage_public` named volume shared with the app/queue
# containers (mode 775, world-readable), so Nginx can read uploaded files
# without needing to run as the same UID that wrote them.
RUN rm -f /var/www/html/public/index.php \
    && mkdir -p /var/www/html/storage/app/public \
    && chown -R nginx:nginx /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1/healthz || exit 1
