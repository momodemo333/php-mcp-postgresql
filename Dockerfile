# Production Dockerfile for PHP MCP PostgreSQL Server
FROM php:8.3-cli-alpine

# Install PostgreSQL client and PHP extensions
RUN apk add --no-cache \
    postgresql-client \
    postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apk del postgresql-dev

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies (production only)
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# Copy application code
COPY . .

# Make server executable
RUN chmod +x bin/server.php

# Create non-root user for security
RUN adduser -D -s /bin/sh mcpuser
USER mcpuser

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r "echo 'OK';" || exit 1

# Default command
ENTRYPOINT ["php", "bin/server.php"]

# Labels
LABEL maintainer="momodemo333" \
      version="1.0.0-beta" \
      description="PostgreSQL MCP Server for Claude Code"