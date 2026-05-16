$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$patterns = @(
    ([char]0x0420 + [char]0x045F), # Рџ
    ([char]0x0420 + [char]0x045D), # Рќ
    ([char]0x0420 + [char]0x2014), # Р—
    ([char]0x0420 + [char]0x00A0), # Р<nbsp>
    ([char]0x0420 + [char]0x0408), # РЈ
    ([char]0x0420 + [char]0x0409), # РЎ
    ([char]0x0420 + [char]0x045A), # Рњ
    ([char]0x0420 + [char]0x040B), # Рћ
    ([char]0x0420 + [char]0x201D), # Р”
    ([char]0x0420 + [char]0x0098), # Р
    ([char]0x0432 + [char]0x0402), # вЂ
    ([char]0x0432 + [char]0x201E), # в„
    ([char]0x0440 + [char]0x045F)  # рџ
)
$extensions = @('.php', '.md', '.js', '.scss', '.css')
$ignoredParts = @('\vendor\', '\assets\fonts\')
$matches = @()

Get-ChildItem -Path $root -Recurse -File | ForEach-Object {
    $path = $_.FullName
    foreach ($ignored in $ignoredParts) {
        if ($path.Contains($ignored)) {
            return
        }
    }

    if ($extensions -notcontains $_.Extension) {
        return
    }

    $relativePath = Resolve-Path -Relative $path
    $content = Get-Content -Raw -Encoding UTF8 $path
    if ($null -eq $content) {
        return
    }

    foreach ($pattern in $patterns) {
        if ($content.Contains($pattern)) {
            $matches += "$relativePath contains mojibake marker '$pattern'"
            break
        }
    }
}

if ($matches.Count -gt 0) {
    Write-Error ("Mojibake markers found:`n" + ($matches -join "`n"))
}

Write-Output 'No mojibake markers found.'
