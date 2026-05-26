#!/bin/sh
# Production (Linux) : expose globalo/uploads sous l'URL /uploads/…
# Les fichiers restent dans le dossier à la racine (UPLOAD_PATH), comme en PHP.
# Usage : depuis la racine du projet : chmod +x tools/link-uploads-linux.sh && ./tools/link-uploads-linux.sh
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUB="$ROOT/public"
TARGET="$ROOT/uploads"
cd "$PUB" || exit 1
if [ -L uploads ]; then
  rm -f uploads
elif [ -e uploads ] && [ ! -L uploads ]; then
  echo "Erreur : $PUB/uploads existe et n'est pas un lien symbolique."
  echo "Sauvegardez le contenu, placez-le dans $TARGET si besoin, supprimez ce dossier, puis relancez."
  exit 1
fi
ln -sfn "$TARGET" uploads
echo "OK : $PUB/uploads -> $TARGET"
ls -la uploads
