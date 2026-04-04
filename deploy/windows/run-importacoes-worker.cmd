@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "C:\xampp\htdocs\lukrato\deploy\windows\run-importacoes-worker.ps1" -AppDir "C:\xampp\htdocs\lukrato" -PhpBin "C:\xampp\php\php.exe" -QueueSleepSeconds 2 -QueueMaxAttempts 3 -QueueStaleTtlSeconds 900 -RestartDelaySeconds 5 -LogFile "C:\xampp\htdocs\lukrato\storage\logs\importacoes-worker.log"
