# start_servers.ps1

Write-Host "Starting Symfony server..."
Start-Process "php" "-S 127.0.0.1:8000 -t public/" -NoNewWindow

Write-Host "Starting FastAPI server..."
Set-Location "api"
Start-Process "uvicorn" "main:app --reload --port 8001" -NoNewWindow