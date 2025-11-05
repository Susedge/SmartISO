# SmartISO - Manage Scheduled Backup Task
# This script creates, updates, or deletes the Windows Scheduled Task for database backups

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("create", "update", "delete", "status")]
    [string]$Operation,
    
    [Parameter(Mandatory=$false)]
    [string]$Time = "02:00",
    
    [Parameter(Mandatory=$false)]
    [string]$ProjectPath = "c:\xampp\htdocs\SmartISO-5\SmartISO"
)

$TaskName = "SmartISO Database Backup"
$ErrorActionPreference = "Stop"

try {
    # Get existing task if it exists
    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    
    switch ($Operation) {
        "create" {
            if ($existingTask) {
                Write-Output "ERROR: Task already exists. Use 'update' to modify it."
                exit 1
            }
            
            # Parse time (HH:MM format)
            if ($Time -notmatch '^([0-1][0-9]|2[0-3]):[0-5][0-9]$') {
                Write-Output "ERROR: Invalid time format. Use HH:MM (e.g., 02:00)"
                exit 1
            }
            
            # Create action
            $actionPath = "powershell.exe"
            $actionArgs = "-ExecutionPolicy Bypass -File `"$ProjectPath\scripts\run_backup.ps1`""
            $taskAction = New-ScheduledTaskAction -Execute $actionPath -Argument $actionArgs -WorkingDirectory $ProjectPath
            
            # Create trigger (daily at specified time)
            $taskTrigger = New-ScheduledTaskTrigger -Daily -At $Time
            
            # Create principal (run with current user)
            $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
            $taskPrincipal = New-ScheduledTaskPrincipal -UserId $currentUser -RunLevel Highest
            
            # Create settings
            $taskSettings = New-ScheduledTaskSettingsSet `
                -AllowStartIfOnBatteries `
                -DontStopIfGoingOnBatteries `
                -StartWhenAvailable `
                -RestartCount 3 `
                -RestartInterval (New-TimeSpan -Minutes 10)
            
            # Register the task
            Register-ScheduledTask `
                -TaskName $TaskName `
                -Action $taskAction `
                -Trigger $taskTrigger `
                -Principal $taskPrincipal `
                -Settings $taskSettings `
                -Description "Automated database backup for SmartISO system" | Out-Null
            
            Write-Output "SUCCESS: Scheduled task created successfully for $Time daily"
            exit 0
        }
        
        "update" {
            if (-not $existingTask) {
                Write-Output "INFO: Task doesn't exist. Creating new task..."
                & $PSCommandPath -Operation "create" -Time $Time -ProjectPath $ProjectPath
                exit $LASTEXITCODE
            }
            
            # Parse time
            if ($Time -notmatch '^([0-1][0-9]|2[0-3]):[0-5][0-9]$') {
                Write-Output "ERROR: Invalid time format. Use HH:MM (e.g., 02:00)"
                exit 1
            }
            
            # Update trigger
            $taskTrigger = New-ScheduledTaskTrigger -Daily -At $Time
            Set-ScheduledTask -TaskName $TaskName -Trigger $taskTrigger | Out-Null
            
            Write-Output "SUCCESS: Scheduled task updated to run at $Time daily"
            exit 0
        }
        
        "delete" {
            if (-not $existingTask) {
                Write-Output "INFO: Task doesn't exist, nothing to delete"
                exit 0
            }
            
            Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
            Write-Output "SUCCESS: Scheduled task deleted successfully"
            exit 0
        }
        
        "status" {
            if (-not $existingTask) {
                Write-Output "NOT_FOUND"
                exit 0
            }
            
            $state = $existingTask.State
            $enabled = $existingTask.Settings.Enabled
            $nextRun = (Get-ScheduledTaskInfo -TaskName $TaskName).NextRunTime
            $lastRun = (Get-ScheduledTaskInfo -TaskName $TaskName).LastRunTime
            $lastResult = (Get-ScheduledTaskInfo -TaskName $TaskName).LastTaskResult
            
            # Get trigger time
            $triggerTime = $existingTask.Triggers[0].StartBoundary
            if ($triggerTime) {
                $parsedTime = [DateTime]::Parse($triggerTime).ToString("HH:mm")
            } else {
                $parsedTime = "Unknown"
            }
            
            # Build status JSON
            $status = @{
                exists = $true
                state = $state.ToString()
                enabled = $enabled
                scheduledTime = $parsedTime
                nextRun = if ($nextRun) { $nextRun.ToString("yyyy-MM-dd HH:mm:ss") } else { "Not scheduled" }
                lastRun = if ($lastRun) { $lastRun.ToString("yyyy-MM-dd HH:mm:ss") } else { "Never" }
                lastResult = $lastResult
                lastResultText = if ($lastResult -eq 0) { "Success" } elseif ($lastResult -eq 267011) { "Task has not yet run" } else { "Error: $lastResult" }
            } | ConvertTo-Json -Compress
            
            Write-Output $status
            exit 0
        }
    }
    
} catch {
    Write-Output "ERROR: $($_.Exception.Message)"
    exit 1
}
