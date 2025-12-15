#!/bin/bash
set -e

# ===============================
# CONFIG
# ===============================
PTERODACTYL_DIRECTORY="/var/www/pterodactyl"
WEBUSER="www-data"
OWNERSHIP="www-data:www-data"
USERSHELL="/bin/bash"

echo "== Blueprint Auto Installer =="

# ===============================
# ROOT CHECK
# ===============================
if [ "$EUID" -ne 0 ]; then
  echo "Jalankan script ini sebagai root"
  exit 1
fi

# ===============================
# VALIDASI PTERODACTYL
# ===============================
if [ ! -f "$PTERODACTYL_DIRECTORY/artisan" ]; then
  echo "Pterodactyl tidak ditemukan di $PTERODACTYL_DIRECTORY"
  exit 1
fi

cd $PTERODACTYL_DIRECTORY

# ===============================
# DEPENDENCIES
# ===============================
echo "[+] Install dependency sistem"
apt update -y
apt install -y \
  ca-certificates \
  curl \
  git \
  gnupg \
  unzip \
  wget \
  zip

# ===============================
# NODEJS 20 (WAJIB UNTUK BLUEPRINT)
# ===============================
if ! node -v | grep -q "v20"; then
  echo "[+] Install NodeJS v20"
  mkdir -p /etc/apt/keyrings
  curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg

  echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] \
https://deb.nodesource.com/node_20.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list

  apt update -y
  apt install -y nodejs
fi

npm install -g yarn

# ===============================
# DOWNLOAD BLUEPRINT
# ===============================
echo "[+] Download Blueprint terbaru"
LATEST_RELEASE=$(curl -s https://api.github.com/repos/BlueprintFramework/framework/releases/latest \
  | grep browser_download_url \
  | cut -d '"' -f 4)

wget -O blueprint.zip "$LATEST_RELEASE"
unzip -o blueprint.zip
rm blueprint.zip

# ===============================
# INSTALL NODE DEPENDENCIES
# ===============================
echo "[+] Install Node dependencies"
yarn install

# ===============================
# KONFIGURASI .blueprintrc
# ===============================
echo "[+] Setup .blueprintrc"
cat > $PTERODACTYL_DIRECTORY/.blueprintrc <<EOF
WEBUSER="$WEBUSER";
OWNERSHIP="$OWNERSHIP";
USERSHELL="$USERSHELL";
EOF

# ===============================
# JALANKAN BLUEPRINT
# ===============================
echo "[+] Menjalankan Blueprint"
chmod +x blueprint.sh
bash blueprint.sh

echo "================================"
echo " BLUEPRINT BERHASIL DI-INSTALL "
echo "================================"