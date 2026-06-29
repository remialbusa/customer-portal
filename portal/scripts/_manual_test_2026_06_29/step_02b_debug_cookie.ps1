$d = $PSScriptRoot
Set-Location $d
$base = "http://127.0.0.1:8765"

# Show what's in the cookie jar
Write-Host "=== cookies_ramenizing.txt (Netscape format) ==="
Get-Content "cookies_ramenizing.txt"
Write-Host ""

# Try sending the cookie explicitly
$cookieLine = Get-Content "cookies_ramenizing.txt"
$cookieValue = ($cookieLine -split "`t")[6]
$cookieName  = ($cookieLine -split "`t")[5]
Write-Host "Sending cookie: $cookieName = $($cookieValue.Substring(0,30))..."

$headers = @{"Cookie" = "$cookieName=$cookieValue"}
$r = Invoke-WebRequest -UseBasicParsing -Uri "$base/dashboard" -Headers $headers -MaximumRedirection 0
Write-Host "Status: $($r.StatusCode)"
Write-Host "Location: $($r.Headers.Location)"
Write-Host "Body length: $($r.Content.Length)"
