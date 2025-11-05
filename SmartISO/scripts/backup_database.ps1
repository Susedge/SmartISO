# Backup Database PowerShell helper
# Usage: .\backup_database.ps1
# This script will invoke the bundled PHP CLI backup script and rotate old backups.

$phpExe = "php" # rely on php in PATH; adjust if needed (e.g., C:\xampp\php\php.exe)
$repoRoot = Join-Path $PSScriptRoot ".."
$scriptPath = Join-Path $repoRoot "scripts\backup_database.php"

if (-not (Test-Path $scriptPath)) {
    Write-Error "backup_database.php not found at $scriptPath"
    exit 1
}

$process = Start-Process -FilePath $phpExe -ArgumentList `"$scriptPath`" -NoNewWindow -Wait -PassThru
if ($process.ExitCode -ne 0) {
    Write-Error "Backup script failed with exit code $($process.ExitCode)"
    exit $process.ExitCode
}

Write-Output "Backup completed successfully."

# Optional: you can add code here to upload the backup to remote storage (S3, FTP, network share)
# or send a notification email. Keep the script small for Task Scheduler.

exit 0
