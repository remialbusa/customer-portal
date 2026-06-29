$d = $PSScriptRoot
Set-Location $d
$base = "http://127.0.0.1:8765"

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
    $status = ($output -split '\|')[0]
    $loc    = ($output -split '\|')[1]
    return [pscustomobject]@{ Status = [int]$status; Location = $loc }
}

function Get-H1 { param([string]$File)
    if (-not (Test-Path $File)) { return "(no file)" }
    $m = Select-String -Path $File -Pattern "<h1[^>]*>([^<]+)" | Select-Object -First 1
    if ($m) { return ($m.Matches[0].Groups[1].Value -replace "<.*", "").Trim() }
    return "(no h1)"
}

# Each role: home route + jar
$roles = @(
    @{ Role="customer";    Path="/dashboard";          Jar="cookies_ramenizing.txt" }
    @{ Role="fse (tsp)";   Path="/tsp/dashboard";      Jar="cookies_remial.busa.txt" }
    @{ Role="admin";       Path="/admin/kpi";          Jar="cookies_admin.txt" }
    @{ Role="superadmin";  Path="/admin/invites";      Jar="cookies_superadmin.txt" }
)

Write-Host "`n========== STEP 1: ROLE-SPECIFIC HOME ==========" -ForegroundColor Cyan
foreach ($r in $roles) {
    $slug = $r.Role -replace ' ','_' -replace '[()]',''
    $r.Call = Call -Path $r.Path -Jar $r.Jar -OutFile "home_$slug.html"
    $h1 = Get-H1 "home_$slug.html"
    Write-Host ("[{0,-12}] {1,-22} -> {2}  h1='{3}'" -f $r.Role, $r.Path, $r.Call.Status, $h1)
}
