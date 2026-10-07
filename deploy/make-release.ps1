<#
.SYNOPSIS
    Builds an upload-ready release for cPanel shared hosting (Biznet Gio NEO Web Hosting).

.DESCRIPTION
    Produces two archives in deploy\release:
      everynation.zip  -> extract in /home/USER        (the Laravel app, outside public_html)
      public_html.zip  -> extract in /home/USER/public_html (public assets + index.php)

    Assets are built locally (no Node.js needed on the server) and Composer dependencies are
    installed without dev packages. Local .env, logs, uploads and caches are never included.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deploy\make-release.ps1
#>
param(
    [string]$AppFolder = 'everynation'
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$release = Join-Path $root 'deploy\release'
$stage = Join-Path $env:TEMP 'enb-release'
$app = Join-Path $stage $AppFolder
$publicHtml = Join-Path $stage 'public_html'

# Laragon tools when they are not on PATH.
$php = Get-ChildItem 'C:\laragon\bin\php' -Directory -Filter 'php-8.4*' -ErrorAction SilentlyContinue | Select-Object -Last 1
$node = Get-ChildItem 'C:\laragon\bin\nodejs' -Directory -ErrorAction SilentlyContinue | Select-Object -Last 1
foreach ($dir in @($php.FullName, 'C:\laragon\bin\composer', $node.FullName)) {
    if ($dir -and (Test-Path $dir)) { $env:Path = "$dir;$env:Path" }
}

function Invoke-Step([string]$Title, [scriptblock]$Action) {
    Write-Host "==> $Title" -ForegroundColor Cyan
    & $Action
    if ($LASTEXITCODE -and $LASTEXITCODE -ne 0) { throw "$Title failed (exit $LASTEXITCODE)" }
}

Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $app, $publicHtml, $release | Out-Null

Invoke-Step 'Building front-end assets (npm run build)' {
    Push-Location $root
    try { npm run build } finally { Pop-Location }
}

Invoke-Step 'Copying application files' {
    robocopy $root $app /E /XJ /NFL /NDL /NJH /NJS /NP `
        /XD (Join-Path $root '.git') (Join-Path $root 'node_modules') (Join-Path $root 'vendor') (Join-Path $root 'tests') `
            (Join-Path $root 'deploy') (Join-Path $root '.claude') (Join-Path $root '.ai') (Join-Path $root '.phpunit.cache') `
        /XF .env .env.backup .env.production *.log database.sqlite | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed ($LASTEXITCODE)" }
    $global:LASTEXITCODE = 0
}

Invoke-Step 'Removing local uploads, logs and caches' {
    $runtime = 'storage\app\public', 'storage\app\private', 'storage\framework\cache\data', 'storage\framework\sessions',
        'storage\framework\views', 'storage\framework\testing', 'storage\logs', 'bootstrap\cache'
    foreach ($dir in $runtime) {
        $path = Join-Path $app $dir
        New-Item -ItemType Directory -Force -Path $path | Out-Null
        Get-ChildItem $path -Force | Where-Object Name -ne '.gitignore' | Remove-Item -Recurse -Force
    }
    Remove-Item (Join-Path $app 'public\storage'), (Join-Path $app 'public\hot') -Recurse -Force -ErrorAction SilentlyContinue
    Copy-Item (Join-Path $root 'deploy\biznet\env.production.example') (Join-Path $app '.env.production.example') -Force
}

Invoke-Step 'Installing production Composer dependencies' {
    Push-Location $app
    try { composer install --no-dev --optimize-autoloader --no-interaction --no-progress } finally { Pop-Location }
}

Invoke-Step 'Preparing public_html' {
    robocopy (Join-Path $app 'public') $publicHtml /E /XJ /NFL /NDL /NJH /NJS /NP /XF hot | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed ($LASTEXITCODE)" }
    $global:LASTEXITCODE = 0
    (Get-Content (Join-Path $root 'deploy\biznet\index.php') -Raw) `
        -replace "/\.\./everynation'", "/../$AppFolder'" |
        Set-Content (Join-Path $publicHtml 'index.php') -Encoding utf8 -NoNewline
}

Invoke-Step 'Creating archives' {
    Remove-Item (Join-Path $release '*.zip') -ErrorAction SilentlyContinue
    # Windows' bsdtar writes real .zip files; a Git Bash tar earlier on PATH cannot.
    $tar = Join-Path $env:SystemRoot 'System32\tar.exe'
    & $tar -a -c -f (Join-Path $release "$AppFolder.zip") -C $stage $AppFolder
    & $tar -a -c -f (Join-Path $release 'public_html.zip') -C $publicHtml .
}

Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue
Write-Host ''
Write-Host 'Release ready:' -ForegroundColor Green
Get-ChildItem $release -Filter *.zip | ForEach-Object { '  {0}  ({1:N1} MB)' -f $_.FullName, ($_.Length / 1MB) }
