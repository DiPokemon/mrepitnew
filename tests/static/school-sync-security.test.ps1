$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$legacySync = Join-Path $root 'includes\users_controls\common\sync.php'

if (Test-Path $legacySync) {
    Write-Error 'Theme must not keep legacy parent/student sync logic; mrepit-school-core owns school sync.'
}

Write-Output 'Theme school sync cleanup static checks passed.'
