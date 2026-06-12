@echo off
set PHP_EXE=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.1_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe

if not exist "%PHP_EXE%" (
    echo PHP introuvable: %PHP_EXE%
    echo Ouvrez un nouveau terminal et essayez: php -S localhost:8000
    pause
    exit /b 1
)

cd /d "%~dp0"
"%PHP_EXE%" -S localhost:8000
