#!/usr/bin/env bash
# ══════════════════════════════════════════════════════════════════════════════
#  GLOBALO — Gestion de la maintenance (serveur Linux / cPanel SSH)
#
#  Usage :
#    ./maintenance.sh on [action] [progress] [eta] [message]
#    ./maintenance.sh off
#    ./maintenance.sh status
#
#  Actions disponibles : deploy | migration | pays | patch | backup | config
#
#  Exemples :
#    ./maintenance.sh on
#    ./maintenance.sh on deploy 30
#    ./maintenance.sh on pays 50 "2026-06-01 14:00" "Changement Burkina → Bénin"
#    ./maintenance.sh on migration 20 "" "Migration base de données"
#    ./maintenance.sh off
#    ./maintenance.sh status
# ══════════════════════════════════════════════════════════════════════════════

set -euo pipefail

# ── Détection racine du projet ───────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
FLAG="$ROOT/.maintenance"

# ── Couleurs ─────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

# ─────────────────────────────────────────────────────────────────────────────
cmd="${1:-}"

case "$cmd" in

  # ── ACTIVER ───────────────────────────────────────────────────────────────
  on)
    ACTION="${2:-deploy}"
    PROGRESS="${3:-10}"
    ETA="${4:-}"
    MESSAGE="${5:-}"
    CONTACT="admin@globalo.secogesarl.com"

    # Construire le JSON
    JSON="{\n"
    JSON+="  \"action\":   \"$ACTION\",\n"
    JSON+="  \"progress\": $PROGRESS,\n"
    JSON+="  \"contact\":  \"$CONTACT\""

    if [[ -n "$ETA" ]]; then
      JSON+=",\n  \"eta\": \"$ETA\""
    fi
    if [[ -n "$MESSAGE" ]]; then
      # Échapper les guillemets dans le message
      MSG_ESCAPED="${MESSAGE//\"/\\\"}"
      JSON+=",\n  \"message\": \"$MSG_ESCAPED\""
    fi
    JSON+="\n}"

    printf "$JSON" > "$FLAG"

    echo -e "${GREEN}${BOLD}✅  Maintenance ACTIVÉE${RESET}"
    echo -e "    Fichier  : ${CYAN}$FLAG${RESET}"
    echo -e "    Action   : ${BOLD}$ACTION${RESET}"
    echo -e "    Progress : ${BOLD}$PROGRESS%${RESET}"
    [[ -n "$ETA" ]] && echo -e "    ETA      : ${BOLD}$ETA${RESET}"
    [[ -n "$MESSAGE" ]] && echo -e "    Message  : $MESSAGE"
    echo ""
    echo -e "  ${YELLOW}→ Uploadez maintenant vos fichiers via FTP.${RESET}"
    echo -e "  ${YELLOW}→ Puis lancez : ./maintenance.sh off${RESET}"
    ;;

  # ── DÉSACTIVER ────────────────────────────────────────────────────────────
  off)
    if [[ -f "$FLAG" ]]; then
      rm -f "$FLAG"
      echo -e "${GREEN}${BOLD}✅  Maintenance DÉSACTIVÉE — site remis en ligne.${RESET}"
      echo -e "    Fichier supprimé : ${CYAN}$FLAG${RESET}"
    else
      echo -e "${CYAN}ℹ️   Aucun fichier .maintenance — le site était déjà en ligne.${RESET}"
    fi
    echo ""
    echo -e "  → ${BOLD}https://globalo.secogesarl.com${RESET}"
    ;;

  # ── STATUT ────────────────────────────────────────────────────────────────
  status)
    if [[ -f "$FLAG" ]]; then
      echo -e "${YELLOW}${BOLD}⚠️  Site en MAINTENANCE${RESET}"
      echo -e "    Fichier : ${CYAN}$FLAG${RESET}"
      echo -e "    Contenu :"
      cat "$FLAG" | sed 's/^/      /'
    else
      echo -e "${GREEN}${BOLD}✅  Site en LIGNE — pas de maintenance active.${RESET}"
    fi
    ;;

  # ── AIDE ──────────────────────────────────────────────────────────────────
  *)
    echo -e "${BOLD}GLOBALO — Script de maintenance${RESET}"
    echo ""
    echo -e "  ${CYAN}Activer  :${RESET}  ./maintenance.sh on [action] [progress%] [eta] [message]"
    echo -e "  ${CYAN}Désactiver:${RESET} ./maintenance.sh off"
    echo -e "  ${CYAN}Statut   :${RESET}  ./maintenance.sh status"
    echo ""
    echo -e "  Actions : deploy | migration | pays | patch | backup | config"
    echo ""
    echo -e "  Exemples :"
    echo -e "    ./maintenance.sh on"
    echo -e "    ./maintenance.sh on deploy 30"
    echo -e "    ./maintenance.sh on pays 50 \"2026-06-01 14:00\" \"Mise à jour Bénin\""
    echo -e "    ./maintenance.sh on migration 20 \"\" \"Migration DB\""
    ;;

esac
