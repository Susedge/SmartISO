# Registers a Windows Task Scheduler task to run the cleanup_temp.php daily
# Usage (PowerShell as Admin): .\register_cleanup_task.ps1 -UserAction Create
param(
    [ValidateSet('Create','Remove')]
    [string]$UserAction = 'Create'
)

$phpPath = 'C:\\xampp\\php\\php.exe'
$scriptPath = Join-Path -Path (Get-Location) -ChildPath 'tools\\cleanup_temp.php'
$taskName = 'SmartISO_CleanupTemp'

if ($UserAction -eq 'Create') {
    $action = New-ScheduledTaskAction -Execute $phpPath -Argument $scriptPath
    $trigger = New-ScheduledTaskTrigger -Daily -At 3am
    $principal = New-ScheduledTaskPrincipal -UserId "NT AUTHORITY\\SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Description "Cleanup SmartISO temp files daily"
    Write-Output "Task $taskName registered to run daily at 3:00 AM"
} else {
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Write-Output "Task $taskName removed"
}
