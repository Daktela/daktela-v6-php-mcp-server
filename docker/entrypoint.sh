#!/bin/sh
set -e

# Ensure session directory exists and is writable
mkdir -p /app/var/sessions
chmod 777 /app/var/sessions

# Shut down both processes on exit
cleanup() {
    kill "$FPM_PID" "$NGINX_PID" 2>/dev/null || true
}
trap cleanup TERM INT

# Start PHP-FPM in the background
php-fpm --fpm-config /etc/php-fpm.conf &
FPM_PID=$!

# Start nginx in the foreground — if it exits, the container stops
nginx -g 'daemon off;' &
NGINX_PID=$!

# Wait for either process to exit; if one dies, shut down the other
while kill -0 "$FPM_PID" 2>/dev/null && kill -0 "$NGINX_PID" 2>/dev/null; do
    sleep 1
done

echo "One of the processes exited, shutting down..."
cleanup
exit 1
