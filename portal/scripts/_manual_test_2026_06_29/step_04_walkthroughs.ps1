$d = $PSScriptRoot
Set-Location $d
$base = "http://127.0.0.1:8765"

# ============================================================
# Test runner — wraps curl.exe with cookie jar + saves body to file
# ============================================================
function Call {
    param(
        [string]$Method = "GET",
        [string]$Path,
        [string]$Jar = "",
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
    $status = $output -split '\|' | Select-Object -First 1
    $loc    = $output -split '\|' | Select-Object -Skip 1 -First 1
    return [pscustomobject]@{ Status = [int]$status; Location = $loc; Raw = $output }
}

# ============================================================
# Helper: get the first <h1> in a saved HTML file
# ============================================================
function Get-H1 {
    param([string]$File)
    if (-not (Test-Path $File)) { return "(no file)" }
    $line = Select-String -Path $File -Pattern "<h1[^>]*>([^<]+)" | Select-Object -First 1
    if ($line) { return ($line.Matches[0].Groups[1].Value -replace "<.*", "").Trim() }
    return "(no h1)"
}

function Get-H2 {
    param([string]$File, [int]$Index = 0)
    if (-not (Test-Path $File)) { return "(no file)" }
    $line = Select-String -Path $File -Pattern "<h2[^>]*>([^<]+)" | Select-Object -First ($Index + 1)
    if ($line) { return ($line.Matches[0].Groups[1].Value -replace "<.*", "").Trim() }
    return "(no h2)"
}

# ============================================================
# 1) Verify each role can hit /dashboard
# ============================================================
Write-Host "`n========== STEP 1: ROLE LANDING ==========" -ForegroundColor Cyan
foreach ($r in @(
    @{ Role="customer";   Email="ramenizing@gmail.com";       Jar="cookies_ramenizing.txt" }
    @{ Role="fse (tsp)";  Email="remial.busa@mcbtsi.com";     Jar="cookies_remial.busa.txt" }
    @{ Role="admin";      Email="admin@example.com";          Jar="cookies_admin.txt" }
    @{ Role="superadmin"; Email="superadmin@portal.local";    Jar="cookies_superadmin.txt" }
)) {
    $r.Call = Call -Path "/dashboard" -Jar $r.Jar -OutFile "dash_$($r.Role -replace ' ','_').html"
    $h1 = Get-H1 "dash_$($r.Role -replace ' ','_').html"
    Write-Host ("[{0,-12}] {1,-32} /dashboard -> {2}  h1='{3}'" -f $r.Role, $r.Email, $r.Call.Status, $h1)
}
