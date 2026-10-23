# Set the base image for subsequent instructions
FROM ubuntu:22.04

# Set environment variables to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Update packages and install dependencies
RUN apt-get update && apt-get install -y \
    software-properties-common \
    ca-certificates \
    lsb-release \
    apt-transport-https \
    curl \
    wget \
    git \
    zip \
    unzip \
    gnupg2 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Add PHP repository (Ondrej PPA for latest stable PHP)
RUN add-apt-repository ppa:ondrej/php -y

# Install PHP 8.3 FPM (latest stable) and required extensions
RUN apt-get update && apt-get install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-xml \
    php8.3-curl \
    php8.3-gd \
    php8.3-mbstring \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-readline \
    php8.3-opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Python3 and dependencies for QR code extraction
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    libzbar0 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install MySQL client tools (includes mysqldump for database backups)
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create virtual environment for Python packages (to avoid externally-managed-environment error)
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --upgrade pip \
    && /opt/venv/bin/pip install Pillow pyzbar 'qrcode[pil]'

# Add virtual environment to PATH so python3/pip commands use it
ENV PATH="/opt/venv/bin:$PATH"

# # Install Node.js LTS (using NodeSource repository)
# RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
#     && apt-get install -y nodejs \
#     && apt-get clean \
#     && rm -rf /var/lib/apt/lists/*

# # Install Google Chrome Stable and dependencies for Puppeteer/WhatsApp Web.js
# RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
#     && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list \
#     && apt-get update \
#     && apt-get install -y \
#     google-chrome-stable \
#     fonts-liberation \
#     libasound2 \
#     libatk-bridge2.0-0 \
#     libatk1.0-0 \
#     libatspi2.0-0 \
#     libcups2 \
#     libdbus-1-3 \
#     libdrm2 \
#     libgbm1 \
#     libgtk-3-0 \
#     libnspr4 \
#     libnss3 \
#     libwayland-client0 \
#     libxcomposite1 \
#     libxdamage1 \
#     libxfixes3 \
#     libxkbcommon0 \
#     libxrandr2 \
#     xdg-utils \
#     && apt-get clean \
#     && rm -rf /var/lib/apt/lists/*

# # Set Puppeteer to use system Chrome
# ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
#     PUPPETEER_EXECUTABLE_PATH=/usr/bin/google-chrome-stable

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create www-data user with proper UID/GID (33 is standard for www-data)
RUN usermod -u 1000 www-data && \
    groupmod -g 1000 www-data && \
    mkdir -p /var/www && \
    chown -R www-data:www-data /var/www

# Configure PHP-FPM to run as www-data
RUN mkdir -p /run/php /var/log && \
    touch /var/log/php8.3-fpm.log && \
    chown www-data:www-data /var/log/php8.3-fpm.log && \
    sed -i 's/listen = \/run\/php\/php8.3-fpm.sock/listen = 9000/g' /etc/php/8.3/fpm/pool.d/www.conf && \
    sed -i 's/;daemonize = yes/daemonize = no/g' /etc/php/8.3/fpm/php-fpm.conf && \
    sed -i 's/^user = www-data/user = www-data/g' /etc/php/8.3/fpm/pool.d/www.conf && \
    sed -i 's/^group = www-data/group = www-data/g' /etc/php/8.3/fpm/pool.d/www.conf && \
    sed -i 's/;error_log = log\/php8.3-fpm.log/error_log = \/var\/log\/php8.3-fpm.log/g' /etc/php/8.3/fpm/php-fpm.conf

# Set working directory
WORKDIR /var/www

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm8.3"]