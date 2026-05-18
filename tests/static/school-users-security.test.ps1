$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')

function Read-ProjectFile($relativePath) {
    $path = Join-Path $root $relativePath
    return Get-Content -Raw -Encoding UTF8 $path
}

$themeFunctions = Read-ProjectFile 'functions.php'
$legacyModulePath = Join-Path $root 'includes\users_controls'

if ($themeFunctions -match 'users_controls/users-controls\.php') {
    Write-Error 'Theme functions.php must not load the legacy school users module; mrepit-school-core is the single source of school business logic.'
}

if ((Test-Path $legacyModulePath) -and (Get-ChildItem -Path $legacyModulePath -Recurse -File -Filter '*.php')) {
    Write-Error 'Theme must not keep legacy includes/users_controls PHP files after Phase 1 cleanup.'
}

Write-Output 'Theme school users cleanup static checks passed.'
