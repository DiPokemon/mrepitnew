$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')

function Read-ProjectFile($relativePath) {
    $path = Join-Path $root $relativePath
    return Get-Content -Raw -Encoding UTF8 $path
}

$capabilities = Read-ProjectFile 'includes\users_controls\common\capabilities.php'
$helpers = Read-ProjectFile 'includes\users_controls\common\helpers.php'
$parentsHandler = Read-ProjectFile 'includes\users_controls\parents_controls\handlers.php'
$studentsHandler = Read-ProjectFile 'includes\users_controls\students_controls\handlers.php'
$teachersHandler = Read-ProjectFile 'includes\users_controls\teachers_controls\handlers.php'

if ($capabilities -notmatch "add_filter\('editable_roles'") {
    Write-Error 'Manager role restrictions must filter editable_roles.'
}

foreach ($role in @('teacher', 'student', 'parent')) {
    if ($capabilities -notmatch "'$role'") {
        Write-Error "Allowed school role '$role' is missing from capabilities.php."
    }
}

foreach ($blockedRole in @('administrator', 'manager')) {
    if ($capabilities -notmatch "in_array\('$blockedRole'") {
        Write-Error "Manager protection must explicitly block target role '$blockedRole'."
    }
}

if ($capabilities -notmatch 'do_not_allow') {
    Write-Error 'Manager protection must return do_not_allow for blocked user targets.'
}

if ($helpers -notmatch 'function\s+school_filter_user_ids_by_role') {
    Write-Error 'Relationship handlers must use school_filter_user_ids_by_role() before saving linked user IDs.'
}

foreach ($entry in @(
    @{ Name = 'parent create/update'; Source = $parentsHandler; Role = 'student'; Field = 'parent_children' },
    @{ Name = 'student create/update'; Source = $studentsHandler; Role = 'parent'; Field = 'student_parents' }
)) {
    if ($entry.Source -notmatch "school_filter_user_ids_by_role\(\s*[^,]+,\s*'$($entry.Role)'\s*\)") {
        Write-Error "$($entry.Name) must filter $($entry.Field) IDs to role '$($entry.Role)' before saving."
    }
}

foreach ($entry in @(
    @{ Name = 'parents'; Source = $parentsHandler; Create = 'school_parent_create'; Update = 'school_parent_update'; Role = 'parent' },
    @{ Name = 'students'; Source = $studentsHandler; Create = 'school_student_create'; Update = 'school_student_update'; Role = 'student' },
    @{ Name = 'teachers'; Source = $teachersHandler; Create = 'school_teacher_create'; Update = 'school_teacher_update'; Role = 'teacher' }
)) {
    if ($entry.Source -notmatch "check_admin_referer\('$($entry.Create)'\)") {
        Write-Error "$($entry.Name) create handler must verify its nonce."
    }
    if ($entry.Source -notmatch "check_admin_referer\('$($entry.Update)'\)") {
        Write-Error "$($entry.Name) update handler must verify its nonce."
    }
    if ($entry.Source -notmatch "school_assign_role_on_current_blog\([^,]+,\s*'$($entry.Role)'\)") {
        Write-Error "$($entry.Name) handler must force the expected role '$($entry.Role)'."
    }
    if ($entry.Source -notmatch 'school_admin_can_manage_users') {
        Write-Error "$($entry.Name) handlers must check school_admin_can_manage_users()."
    }
}

Write-Output 'School users security static checks passed.'
