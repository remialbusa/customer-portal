$d = $PSScriptRoot
Set-Location $d
$base = "http://127.0.0.1:8765"
$jar  = "cookies_ramenizing.txt"

function Call {
    param(
        [string]$Method = "GET",
        [string]$Path,
        [string]$Jar = $jar,
        [string]$Body = "",
        [int]$MaxRedir = 0,
        [string]$OutFile = ""
    )
    $args = @("-sS")
    if ($Jar) { $args += @("-b", $Jar, "-c", $Jar) }
    if ($OutFile) { $args += @("-o", $OutFile) }
    $args += @("-w", "%{http_code}|%{redirect_url}")
    $args += @("-X", $Method)
    $args += @("--max-redirs", "$MaxRedir")
    $args += @("-H", "Content-Type: application/x-www-form-urlencoded")
    if ($Body) { $args += @("--data-raw", $Body) }
    $args += "$base$Path"
    $output = & curl.exe @args 2>&1
    $parts = $output -split '\|'
    return [pscustomobject]@{ Status = [int]$parts[0]; Location = $parts[1] }
}

function Extract-Csrf { param([string]$File)
    if (-not (Test-Path $File)) { return $null }
    $m = Select-String -Path $File -Pattern 'name="_token" value="([^"]+)"' | Select-Object -First 1
    if ($m) { return $m.Matches[0].Groups[1].Value }
    return $null
}

Write-Host "`n========== STEP 2b: DUP GUARD TEST (real Monday round-trip) ==========" -ForegroundColor Cyan

# Use the existing subject from the previous run
$newSubj = "ManualTest-20260629-102940-444"

# Get a fresh CSRF token
Call -Path "/tickets/new" -OutFile "new.html" | Out-Null
$csrf = Extract-Csrf "new.html"

# Send a duplicate (same subject)
$body = "_token=$csrf&subject=$([uri]::EscapeDataString($newSubj))&description=$([uri]::EscapeDataString('dup attempt'))&request_type=Issue&priority=Medium&brand=Acme&model=ModelX&serial=SN-DUP"
$r = Call -Method POST -Path "/tickets" -Body $body -OutFile "dup.html"
Write-Host "[1] POST /tickets (DUP same subject)  -> $($r.Status)  loc=$($r.Location)"

# Look for the duplicate error in the response
$dupMsg = (Select-String -Path dup.html -Pattern "already have an? open ticket" -AllMatches).Matches
$hasDup = $null -ne $dupMsg
Write-Host "    duplicate error visible in response: $hasDup"

# Now test force=1 bypass
$bodyForce = $body + "&force=1"
$r = Call -Method POST -Path "/tickets" -Body $bodyForce -OutFile "force.html"
Write-Host "[2] POST /tickets (DUP+force=1)  -> $($r.Status)  loc=$($r.Location)"

# Check that the force=1 response is the dashboard redirect
$isDash = $r.Location -like "*/dashboard*"
Write-Host "    force=1 redirected to dashboard: $isDash"

# Hit the chat page on the original ticket to verify customer chat works
Call -Path "/tickets/2760488797" -OutFile "detail.html" | Out-Null
$csrfDetail = Extract-Csrf "detail.html"
$chatBody = "_token=$csrfDetail&body=$([uri]::EscapeDataString('Hello from manual test (customer)'))"
$r = Call -Method POST -Path "/tickets/2760488797/chat" -Body $chatBody -OutFile "chat_resp.html"
Write-Host "[3] POST /tickets/2760488797/chat -> $($r.Status)  loc=$($r.Location)"

# Verify the message was sent (look for it in detail)
Call -Path "/tickets/2760488797" -OutFile "detail2.html" | Out-Null
$msgVisible = Select-String -Path detail2.html -Pattern "Hello from manual test \(customer\)" -Quiet
Write-Host "    customer message visible in detail: $msgVisible"
