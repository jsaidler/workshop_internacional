@echo off
setlocal
php "%~dp0tools\build-dist.php" %*
if errorlevel 1 exit /b %ERRORLEVEL%
php "%~dp0tools\write-deploy-manifest.php" "%~dp0dist"
exit /b %ERRORLEVEL%
