$d = $PSScriptRoot
Set-Location $d
$base = "http://127.0.0.1:8765"

function Test-Role {
    param([string]$Label, [string]$Jar, [string]$ExpectedH1Pattern, [string]$ExpectedUrlContains = "/dashboard")
    $r = Invoke-WebRequest -UseBasicParsing -Method Get -Uri "$base/dashboard" -WebSession $null `
        -Headers @{"Cookie" = (Get-Content $Jar -Raw) } -MaximumRedirection 0 -ErrorAction SilentlyContinue
    if (-not $r) {
        Write-Host "[$Label] NO RESPONSE (cookie may be malformed)"
        return
    }
    Write-Host "[$Label] GET /dashboard -> $($r.StatusCode) Location=$($r.Headers.Location)"
    if ($r.StatusCode -in 301,302) {
        # Follow once
        $loc = $r.Headers.Location
        $r2 = Invoke-WebRequest -UseBasicParsing -Method Get -Uri "$base$loc" `
            -Headers @{"Cookie" = (Get-Content $Jar -Raw) } -ErrorAction SilentlyContinue
        if ($r2) {
            $h1 = (Select-String -InputObject $r2.Content -Pattern "<h1[^>]*>([^<]+)" -AllMatches).Matches | Select-Object -First 1
            $h1text = if ($h1) { $h1.Groups[1].Value.Trim() } else { "(no h1)" }
            Write-Host "[$Label] followed -> $($r2.StatusCode) h1='$h1text'"
        }
    } else {
        $h1 = (Select-String -InputObject $r.Content -Pattern "<h1[^>]*>([^<]+)" -AllMatches).Matches | Select-Object -First 1
        $h1text = if ($h1) { $h1.Groups[1].Value.Trim() } else { "(no h1)" }
        Write-Host "[$Label] h1='$h1text'"
    }
}

Write-Host "=== Verifying forged cookies land on role-specific homes ==="
Test-Role -Label "customer (35)" -Jar "cookies_ramenizing.txt"
Test-Role -Label "tsp fse (36)"   -Jar "cookies_remial.busa.txt"
Test-Role -Label "admin (1)"      -Jar "cookies_admin.txt"
Test-Role -Label "superadmin (41)"-Jar "cookies_superadmin.txt"
