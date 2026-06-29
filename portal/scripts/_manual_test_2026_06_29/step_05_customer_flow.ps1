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
        [string]$ContentType = "application/x-www-form-urlencoded",
        [int]$MaxRedir = 0,
        [string]$OutFile = ""
    )
    $args = @("-sS")
    if ($Jar) { $args += @("-b", $Jar, "-c", $Jar) }
    if ($OutFile) { $args += @("-o", $OutFile) }
    $args += @("-w", "%{http_code}|%{redirect_url}")
    $args += @("-X", $Method)
    if ($MaxRedir -gt 0) { $args += @("-L", "--max-redirs", "$MaxRedir") } else { $args += @("--max-redirs", "0") }
    if ($ContentType) { $args += @("-H", "Content-Type: $ContentType") }
    if ($Body) { $args += @("--data-raw", $Body) }
    $args += "$base$Path"
    $output = & curl.exe @args 2>&1
    $status = ($output -split '\|')[0]
    $loc    = ($output -split '\|')[1]
    return [pscustomobject]@{ Status = [int]$status; Location = $loc }
}

function Get-H { param([string]$File, [int]$Nth = 1)
    if (-not (Test-Path $File)) { return "(no file)" }
    $m = Select-String -Path $File -Pattern "<h[1-3][^>]*>([^<]+)" | Select-Object -First $Nth
    if ($m) { return ($m.Matches[0].Groups[1].Value -replace "<.*", "").Trim() }
    return "(no h)"
}

function Extract-Csrf { param([string]$File)
    $m = Select-String -Path $File -Pattern 'name="csrf-token" content="([^"]+)"'
    if ($m) { return $m.Matches[0].Groups[1].Value }
    $m = Select-String -Path $File -Pattern 'name="_token" value="([^"]+)"'
    if ($m) { return $m.Matches[0].Groups[1].Value }
    return $null
}

Write-Host "`n========== STEP 2: CUSTOMER FLOW ==========" -ForegroundColor Cyan

# 1) Dashboard
$r = Call -Path "/dashboard" -OutFile "cust_dashboard.html"
Write-Host "[1] GET /dashboard                        -> $($r.Status)"

# 2) Tickets index (customer)
$r = Call -Path "/tickets" -OutFile "cust_tickets.html"
$h1 = Get-H "cust_tickets.html" 1
Write-Host "[2] GET /tickets                         -> $($r.Status)  h1='$h1'"

# 3) New ticket form
$r = Call -Path "/tickets/new" -OutFile "cust_new_ticket.html"
$h1 = Get-H "cust_new_ticket.html" 1
$csrf = Extract-Csrf "cust_new_ticket.html"
$hasCsrf = if ($csrf) { "yes" } else { "no" }
Write-Host "[3] GET /tickets/new                     -> $($r.Status)  h1='$h1'  csrf=$hasCsrf"

# 4) POST a new ticket (will hit real Monday)
$newSubj = "Manual Test 2026-06-29 $(Get-Random)"
$body = "_token=$csrf&subject=$([uri]::EscapeDataString($newSubj))&description=$([uri]::EscapeDataString('E2E manual test description'))&priority=normal&request_type=service&brand=Acme&model=ModelX&serial=SN-$((Get-Random))"
$r = Call -Method POST -Path "/tickets" -Body $body -OutFile "cust_create_resp.html"
Write-Host "[4] POST /tickets '$newSubj'  -> $($r.Status)  loc=$($r.Location)"

# 5) Try the duplicate guard — same subject again
$r2 = Call -Method POST -Path "/tickets" -Body $body -OutFile "cust_dup_resp.html"
Write-Host "[5] POST /tickets (DUP)                   -> $($r2.Status)  loc=$($r2.Location)"

# 6) With force=1, should bypass
$bodyForce = $body + "&force=1"
$r3 = Call -Method POST -Path "/tickets" -Body $bodyForce -OutFile "cust_force_resp.html"
Write-Host "[6] POST /tickets (DUP+force=1)           -> $($r3.Status)  loc=$($r3.Location)"

# 7) Now visit the most recent ticket (the one we just created, or pick latest from /tickets)
$bodyHtml = Get-Content "cust_tickets.html" -Raw
$ids = [regex]::Matches($bodyHtml, '/tickets/(\d+)') | ForEach-Object { [int]$_.Groups[1].Value } | Sort-Object -Unique -Descending
if ($ids.Count -gt 0) {
    $ticketId = $ids[0]
    Write-Host "[7] Latest ticket id from list: $ticketId"
    $r = Call -Path "/tickets/$ticketId" -OutFile "cust_ticket_detail.html"
    Write-Host "[7] GET /tickets/$ticketId                -> $($r.Status)"

    # 8) Chat send
    $csrfDetail = Extract-Csrf "cust_ticket_detail.html"
    $chatBody = "_token=$csrfDetail&body=$([uri]::EscapeDataString('Hello from manual test'))"
    $r = Call -Method POST -Path "/tickets/$ticketId/chat" -Body $chatBody -OutFile "cust_chat_resp.html"
    Write-Host "[8] POST /tickets/$ticketId/chat         -> $($r.Status)  loc=$($r.Location)"
} else {
    Write-Host "[7] No ticket IDs in list - skipping detail/chat" -ForegroundColor Yellow
}
