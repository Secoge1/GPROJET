@echo off
REM Jonction : public\uploads pointe vers le dossier globalo\uploads (meme contenu que UPLOAD_PATH).
REM A lancer depuis la racine du projet : tools\link-uploads-windows.bat
set ROOT=%~dp0..
set PUB=%ROOT%\public
if exist "%PUB%\uploads" (
  echo Suppression de public\uploads existant...
  rmdir "%PUB%\uploads"
)
mklink /J "%PUB%\uploads" "%ROOT%\uploads"
if errorlevel 1 (
  echo Echec. Executer l'invite en administrateur si necessaire.
  exit /b 1
)
echo OK : public\uploads --^> ..\uploads
exit /b 0
