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

Write-Host "`n========== STEP 2: CUSTOMER FLOW (corrected) ==========" -ForegroundColor Cyan

# 1) Get a fresh CSRF token from /tickets/new
Call -Path "/tickets/new" -OutFile "new.html" | Out-Null
$csrf = Extract-Csrf "new.html"
Write-Host "[1] /tickets/new csrf = $($csrf.Substring(0,16))..."

# 2) POST a new ticket with valid values
$newSubj = "ManualTest-$(Get-Date -Format 'yyyyMMdd-HHmmss')-$((Get-Random) % 1000)"
$body = "_token=$csrf&subject=$([uri]::EscapeDataString($newSubj))&description=$([uri]::EscapeDataString('E2E manual test description'))&request_type=Issue&priority=Medium&brand=Acme&model=ModelX&serial=SN-$((Get-Random))"
$r = Call -Method POST -Path "/tickets" -Body $body -OutFile "created.html"
Write-Host "[2] POST /tickets '$newSubj'  -> $($r.Status)  loc=$($r.Location)"

# 3) Capture the new ticket id from the redirect (e.g. /tickets/123)
$newId = $null
if ($r.Location -match '/tickets/(\d+)') { $newId = [int]$Matches[1] }
if (-not $newId) {
    # Maybe it ended up in session — but typically redirects to detail
    Write-Host "    No /tickets/N in redirect. Trying dashboard..." -ForegroundColor Yellow
}
Write-Host "[3] New ticket id = $newId"

# 4) Hit the ticket detail
if ($newId) {
    $r = Call -Path "/tickets/$newId" -OutFile "detail.html"
    Write-Host "[4] GET /tickets/$newId  -> $($r.Status)"
    $h1 = (Select-String -Path detail.html -Pattern '<h1[^>]*>([^<]+)' -AllMatches).Matches | Select-Object -First 1
    $h1text = if ($h1) { $h1.Groups[1].Value.Trim() } else { "(no h1)" }
    Write-Host "    h1='$h1text'"

    # 5) Send a chat message from customer side
    $csrfDetail = Extract-Csrf "detail.html"
    $chatBody = "_token=$csrfDetail&body=$([uri]::EscapeDataString('Hello from manual test (customer)'))"
    $r = Call -Method POST -Path "/tickets/$newId/chat" -Body $chatBody -OutFile "chat_resp.html"
    Write-Host "[5] POST /tickets/$newId/chat  -> $($r.Status)  loc=$($r.Location)"
}

# 6) Try duplicate guard: same subject again
Call -Path "/tickets/new" -OutFile "new2.html" | Out-Null
$csrf2 = Extract-Csrf "new2.html"
$bodyDup = "_token=$csrf2&subject=$([uri]::EscapeDataString($newSubj))&description=$([uri]::EscapeDataString('dup attempt'))&request_type=Issue&priority=Medium&brand=Acme&model=ModelX&serial=SN-DUP"
$r = Call -Method POST -Path "/tickets" -Body $bodyDup -OutFile "dup.html"
Write-Host "[6] POST /tickets (DUP same subject)  -> $($r.Status)  loc=$($r.Location)"

# 7) Check that the dup response is the form again with errors flashed
$dupHasError = $false
if (Select-String -Path dup.html -Pattern "already have an? open ticket|duplicate|errors\.duplicate" -Quiet) { $dupHasError = $true }
Write-Host "    dup page has duplicate error visible: $dupHasError"

# 8) force=1 bypass
$bodyForce = $bodyDup + "&force=1"
$r = Call -Method POST -Path "/tickets" -Body $bodyForce -OutFile "force.html"
Write-Host "[8] POST /tickets (DUP+force=1)  -> $($r.Status)  loc=$($r.Location)"

# 9) Confirm dashboard now shows the new ticket
Call -Path "/dashboard" -OutFile "dash_after.html" | Out-Null
$countM = Select-String -Path dash_after.html -Pattern "(\d+) ticket" -AllMatches
$ticketCount = if ($countM) { $countM.Matches[0].Groups[1].Value } else { "?" }
Write-Host "[9] dashboard ticket count = $ticketCount"
