@echo off
title SRSP - Serveur rapide (opcache)
cd /d "%~dp0"
php -d "zend_extension=C:\Users\Nahitantsoa\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\ext\php_opcache.dll" -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.validate_timestamps=1 artisan serve
pause