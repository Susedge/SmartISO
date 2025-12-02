@echo off
echo ====================================================
echo CALENDAR DEBUG LOG VIEWER
echo ====================================================
echo.
echo Please refresh the calendar page first, then press any key...
pause > nul
echo.
echo Showing recent calendar logs:
echo.
powershell -Command "Get-Content 'writable\logs\log-2025-11-23.log' | Select-String -Pattern 'CALENDAR ACCESS START|Calendar accessed|Calendar -|Department Admin Calendar' | Select-Object -Last 40"
echo.
echo ====================================================
pause
