#!/bin/bash
# Health check script for PostgreSQL

set -e

# Try pg_isready first (most reliable)
if command -v pg_isready &> /dev/null; then
    pg_isready -U "${POSTGRES_USER}" -h localhost
else
    # Fallback: try psql
    if command -v psql &> /dev/null; then
        psql -U "${POSTGRES_USER}" -h localhost -d "${POSTGRES_DB}" -c "SELECT 1"
    else
        # Last resort: try to connect via TCP
        nc -z localhost 5432
    fi
fi
