# Generates PDFs for the KP-HUB manuals using headless Chrome/Edge.
# Usage: run from project root: .\generate_manual_pdfs.ps1
# Requires Chrome or Edge installed. Will try common install paths.

$projectRoot = (Get-Location).Path
$phpServerCmd = "php -S localhost:8000 -t $projectRoot"

# Try to find browser executable
$possiblePaths = @(
  "C:\Program Files\Google\Chrome\Application\chrome.exe",
  "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
  "C:\Program Files\Microsoft\Edge\Application\msedge.exe",
  "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
)

$browser = $null
foreach ($p in $possiblePaths) {
  if (Test-Path $p) { $browser = $p; break }
}

if (-not $browser) {
  Write-Host "No Chrome or Edge executable found in common locations. Please install Chrome/Edge or adjust the script to point to the binary." -ForegroundColor Yellow
  exit 1
}

# Start PHP server in background
Write-Host "Starting PHP built-in server on http://localhost:8000/ ..." -ForegroundColor Cyan
$phpProc = Start-Process -FilePath php -ArgumentList "-S localhost:8000 -t $projectRoot" -NoNewWindow -PassThru
Start-Sleep -Seconds 1

try {
  $manuals = @{
    "manual_user.html" = "KP-HUB_User_Manual.pdf";
    "manual_admin.html" = "KP-HUB_Admin_Manual.pdf";
  }

  foreach ($m in $manuals.GetEnumerator()) {
    $url = "http://localhost:8000/$($m.Key)"
    $out = Join-Path $projectRoot $m.Value
    Write-Host "Printing $url -> $out" -ForegroundColor Green
    & "$browser" --headless --disable-gpu --print-to-pdf="$out" $url
    Start-Sleep -Milliseconds 500
    if (Test-Path $out) {
      Write-Host "Created $out" -ForegroundColor Green
    } else {
      Write-Host "Failed to create $out" -ForegroundColor Red
    }
  }
} finally {
  if ($phpProc -and !$phpProc.HasExited) {
    Write-Host "Stopping PHP dev server" -ForegroundColor Cyan
    $phpProc | Stop-Process
  }
}

Write-Host "Done." -ForegroundColor Cyan
