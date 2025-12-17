#!/bin/bash
set -e

PTERO_DIR="/var/www/pterodactyl"
DB_PASS="bagus2134"

clear
echo "======================================"
echo "  PTERODACTYL AUTO INSTALLER"
echo "======================================"
echo "1. Install Panel"
echo "2. Install Wings"
echo "3. Install Tema (Blueprint + Darkenate)"
echo "4. Uninstall (Panel / Wings)"
echo "======================================"
read -rp "Pilih menu [1-4]: " MENU

install_panel() {
  echo "== INSTALL PANEL =="

  bash <(curl -s https://pterodactyl-installer.se) <<EOF
0


$DB_PASS
Asia/Makassar
admin@bagusx.my.id
admin@bagusx.my.id
bagus2134
Bagus
Xixepen
EOF

  echo
  echo "Jika muncul pilihan sertifikat SSL:"
  echo "Pilih nomor 2 (Renew & replace certificate)"
  echo "Jika tidak muncul, abaikan."
}

install_wings() {
  echo "== INSTALL WINGS =="

  bash <(curl -s https://pterodactyl-installer.se) <<EOF
1
y
y
y

y

$DB_PASS
y
y
EOF
}

install_tema() {
  echo "== INSTALL TEMA =="

  apt update
  apt install -y zip unzip git curl wget

  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
  export NVM_DIR="$HOME/.nvm"
  source "$NVM_DIR/nvm.sh"

  nvm install 20
  npm i -g yarn

  cd $PTERO_DIR
  yarn
  yarn add cross-env

  wget "$(curl -s https://api.github.com/repos/BlueprintFramework/framework/releases/latest \
    | grep 'browser_download_url' \
    | cut -d '"' -f 4)" -O release.zip

  unzip -o release.zip
  chmod +x blueprint.sh
  bash blueprint.sh

  cd $PTERO_DIR
  wget https://github.com/JasonHorkles/darkenate/releases/download/v2.0.2/darkenate.blueprint

  blueprint -install darkenate
}

uninstall_all() {
  echo "== UNINSTALL PANEL / WINGS =="

  bash <(curl -s https://pterodactyl-installer.se) <<EOF
3
y
y
y
EOF
}

case "$MENU" in
  1) install_panel ;;
  2) install_wings ;;
  3) install_tema ;;
  4) uninstall_all ;;
  *)
    echo "Menu tidak valid."
    exit 1
    ;;
esac

echo
echo "======================================"
echo "  SELESAI"
echo "======================================"