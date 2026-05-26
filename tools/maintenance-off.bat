@echo off
chcp 65001 >nul

REM ══════════════════════════════════════════════════════════════════════════
REM  GLOBALO — Désactiver la maintenance
REM  Lance maintenance-off.bat après avoir uploadé tous les fichiers.
REM ══════════════════════════════════════════════════════════════════════════

SET ROOT=%~dp0..
SET FLAG=%ROOT%\.maintenance

IF EXIST "%FLAG%" (
    DEL /F /Q "%FLAG%"
    echo.
    echo ✅  Maintenance DÉSACTIVÉE — site remis en ligne.
    echo     Fichier supprimé : %FLAG%
) ELSE (
    echo.
    echo ℹ️   Aucun fichier .maintenance trouvé — le site était déjà en ligne.
)

echo.
echo  → Vérifiez le site : https://globalo.secogesarl.com
echo.
pause
