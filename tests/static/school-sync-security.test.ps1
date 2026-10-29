$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$sync = Get-Content -Raw -Encoding UTF8 (Join-Path $root 'includes\users_controls\common\sync.php')

foreach ($functionName in @(
    'school_user_ids_to_cf_assoc',
    'school_get_user_ids_by_role',
    'school_remove_parent_from_unlinked_students',
    'school_remove_student_from_unlinked_parents'
)) {
    if ($sync -notmatch "function\s+$functionName\s*\(") {
        Write-Error "sync.php must define $functionName() to keep parent/student links bidirectional."
    }
}

if ($sync -notmatch 'school_remove_parent_from_unlinked_students\(\s*\(int\)\s*\$user_id,\s*\$children_ids\s*\)') {
    Write-Error 'Parent sync must remove this parent from students no longer listed in parent_children.'
}

if ($sync -notmatch 'school_remove_student_from_unlinked_parents\(\s*\(int\)\s*\$user_id,\s*\$parents_ids\s*\)') {
    Write-Error 'Student sync must remove this student from parents no longer listed in student_parents.'
}

if ($sync -notmatch "carbon_set_user_meta\([^,]+,\s*'student_parents'") {
    Write-Error 'Parent sync must write student_parents.'
}

if ($sync -notmatch "carbon_set_user_meta\([^,]+,\s*'parent_children'") {
    Write-Error 'Student sync must write parent_children.'
}

Write-Output 'School parent/student sync static checks passed.'
