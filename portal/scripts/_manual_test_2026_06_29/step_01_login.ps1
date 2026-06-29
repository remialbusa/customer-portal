$d = $PSScriptRoot
Set-Location $d
$base = "http://127.0.0.1:8765"

function Login-As {
    param([string]$Email, [string]$Jar, [string]$Password = "Password!123")
    Remove-Item $Jar -ErrorAction SilentlyContinue
    $html = curl.exe -sS -c $Jar -b $Jar "$base/login"
    if ($html -notmatch 'name="_token" value="([^"]+)"') {
        throw "Could not find CSRF token on /login"
    }
    $token = $Matches[1]
    Write-Host "[$Email] csrf token: $($token.Substring(0,16))..."

    # POST credentials with --max-redirs 0 so we can see the 302
    $null = curl.exe -sS -o login_post.html -w "POST /login -> %{http_code}`n" `
        -c $Jar -b $Jar `
        -X POST "$base/login" `
        -H "Referer: $base/login" `
        --max-redirs 0 `
        --data-urlencode "_token=$token" `
        --data-urlencode "email=$Email" `
        --data-urlencode "password=$Password"

    # Follow redirect (dashboard) to confirm session
    $body = curl.exe -sS -L -c $Jar -b $Jar "$base/dashboard"
    $h1 = (Select-String -InputObject $body -Pattern "<h1[^>]*>([^<]+)" -AllMatches).Matches | Select-Object -First 1
    $h1text = if ($h1) { $h1.Groups[1].Value } else { "(no h1)" }
    Write-Host "[$Email] after login dashboard h1: $h1text"
    return @{Jar=$Jar; H1=$h1text}
}

Write-Host "=== Customer login ==="
Login-As -Email "customer@example.com" -Jar "cookies_customer.txt"

Write-Host "`n=== TSP (FSE) login ==="
Login-As -Email "remial.busa@mcbtsi.com" -Jar "cookies_tsp.txt"

Write-Host "`n=== Admin login ==="
Login-As -Email "admin@example.com" -Jar "cookies_admin.txt"

Write-Host "`n=== Superadmin login ==="
# Try common superadmin emails
$superEmails = @("superadmin@portal.local", "superadmin@example.com")
$superJar = $null
foreach ($e in $superEmails) {
    try {
        $r = Login-As -Email $e -Jar "cookies_super.txt"
        if ($r.H1 -match "Invite|invite|admin") { $superJar = $r.Jar; break }
    } catch {
        Write-Host "[$e] failed: $_"
    }
}
