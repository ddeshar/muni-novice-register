#!/bin/bash
set -e

mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT 1 FROM registrations LIMIT 1" > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo "Initializing database..."
    mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < /docker-entrypoint-initdb.d/init.sql
    echo "Database initialized successfully!"
else
    echo "Database already initialized, skipping..."
fi