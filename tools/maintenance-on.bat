@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

REM ══════════════════════════════════════════════════════════════════════════
REM  GLOBALO — Activer la maintenance
REM  Usage : maintenance-on.bat [action] [progress] [eta] [message]
REM
REM  Actions disponibles :
REM    deploy    Déploiement fichiers (défaut)
REM    migration Migration base de données
REM    pays      Changement de zone géographique
REM    patch     Correctif urgent
REM    backup    Sauvegarde & maintenance préventive
REM    config    Reconfiguration serveur
REM
REM  Exemples :
REM    maintenance-on.bat
REM    maintenance-on.bat deploy 30
REM    maintenance-on.bat pays 50 "2026-06-01 14:00"
REM    maintenance-on.bat migration 20 "" "Migration vers nouvelle structure"
REM ══════════════════════════════════════════════════════════════════════════

SET ROOT=%~dp0..
SET FLAG=%ROOT%\.maintenance

REM ── Paramètres ──────────────────────────────────────────────────────────────
SET ACTION=%~1
SET PROGRESS=%~2
SET ETA=%~3
SET MESSAGE=%~4

IF "%ACTION%"=="" SET ACTION=deploy
IF "%PROGRESS%"=="" SET PROGRESS=10

REM ── Construction du JSON ────────────────────────────────────────────────────
SET JSON={
SET JSON=!JSON! "action": "%ACTION%",
SET JSON=!JSON! "progress": %PROGRESS%,
SET JSON=!JSON! "contact": "admin@globalo.secogesarl.com"

IF NOT "%ETA%"=="" (
    SET JSON=!JSON!, "eta": "%ETA%"
)
IF NOT "%MESSAGE%"=="" (
    SET JSON=!JSON!, "message": "%MESSAGE%"
)

REM ── Écriture du fichier flag ─────────────────────────────────────────────────
(
    echo {
    echo   "action": "%ACTION%",
    echo   "progress": %PROGRESS%,
    IF NOT "%ETA%"=="" echo   "eta": "%ETA%",
    IF NOT "%MESSAGE%"=="" echo   "message": "%MESSAGE%",
    echo   "contact": "admin@globalo.secogesarl.com"
    echo }
) > "%FLAG%"

echo.
echo ✅  Maintenance ACTIVÉE
echo     Fichier : %FLAG%
echo     Action  : %ACTION%
echo     Progress: %PROGRESS%%%
IF NOT "%ETA%"=="" echo     ETA     : %ETA%
echo.
echo  → Uploadez maintenant vos fichiers en production.
echo  → Puis lancez maintenance-off.bat pour remettre en ligne.
echo.
pause
