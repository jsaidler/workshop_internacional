@echo off
setlocal
php "%~dp0tools\build-dist.php" %*
exit /b %ERRORLEVEL%
