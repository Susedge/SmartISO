# SmartISO Database Backup Script
# This script runs the automated database backup command

# Set error handling
$ErrorActionPreference = "Stop"

try {
    # Set the working directory to SmartISO root
    Set-Location "c:\xampp\htdocs\SmartISO-5\SmartISO"

    # Get current timestamp
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    
    # Log start
    $logFile = ".\writable\logs\backup-schedule.log"
    Add-Content -Path $logFile -Value "[$timestamp] Starting scheduled backup..."

    # Run the backup command
    $output = php spark db:backup 2>&1
    
    # Log output
    Add-Content -Path $logFile -Value $output
    
    # Log completion
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $logFile -Value "[$timestamp] Backup completed successfully"
    Add-Content -Path $logFile -Value "----------------------------------------"
    
} catch {
    # Log error
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $errorMsg = $_.Exception.Message
    Add-Content -Path $logFile -Value "[$timestamp] ERROR: $errorMsg"
    Add-Content -Path $logFile -Value "----------------------------------------"
    
    # Exit with error code
    exit 1
}
