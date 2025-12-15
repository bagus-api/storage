#!/bin/bash
set -e

PTERODACTYL_DIRECTORY="/var/www/pterodactyl"
THEME_URL="https://github.com/JasonHorkles/darkenate/releases/download/v2.0.2/darkenate.blueprint"

echo "=== AUTO INSTALL BLUEPRINT + DARKENATE ==="

# ===============================
# ROOT CHECK
# ===============================
if [ "$EUID" -ne 0 ]; then
  echo "Jalankan sebagai root"
  exit 1
fi

# ===============================
# VALIDASI PANEL
# ===============================
if [ ! -f "$PTERODACTYL_DIRECTORY/artisan" ]; then
  echo "Pterodactyl tidak ditemukan di $PTERODACTYL_DIRECTORY"
  exit 1
fi

# ===============================
# DEPENDENCY SISTEM
# ===============================
apt update -y
apt install -y zip unzip git curl wget ca-certificates gnupg

# ===============================
# INSTALL NVM
# ===============================
if [ ! -d "/root/.nvm" ]; then
  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
fi

export NVM_DIR="/root/.nvm"
source "$NVM_DIR/nvm.sh"

# ===============================
# NODEJS 20
# ===============================
if ! node -v 2>/dev/null | grep -q "v20"; then
  nvm install 20
fi

# ===============================
# YARN
# ===============================
npm install -g yarn

# ===============================
# MASUK FOLDER PANEL
# ===============================
cd "$PTERODACTYL_DIRECTORY"

# ===============================
# INSTALL NODE DEPENDENCIES PANEL
# ===============================
yarn
yarn add cross-env

# ===============================
# DOWNLOAD BLUEPRINT
# ===============================
LATEST_RELEASE=$(curl -s https://api.github.com/repos/BlueprintFramework/framework/releases/latest \
  | grep browser_download_url \
  | cut -d '"' -f 4)

wget -O release.zip "$LATEST_RELEASE"
unzip -o release.zip
rm release.zip

# ===============================
# JALANKAN BLUEPRINT
# ===============================
chmod +x blueprint.sh
bash blueprint.sh

# ===============================
# DOWNLOAD THEME
# ===============================
wget -O "$PTERODACTYL_DIRECTORY/darkenate.blueprint" "$THEME_URL"

# ===============================
# INSTALL THEME
# ===============================
blueprint -install darkenate

echo "========================================="
echo " BLUEPRINT + DARKENATE BERHASIL DIPASANG "
echo "========================================="