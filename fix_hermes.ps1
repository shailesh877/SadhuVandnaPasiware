$file = "android\app\build.gradle"
$lines = Get-Content $file
$newList = new-object System.Collections.Generic.List[string]
foreach ($line in $lines) {
    $newList.Add($line)
    if ($line -match "def jscFlavor") {
        $newList.Add("def hermesEnabled = findProperty('hermesEnabled') ?: `"true`"")
    }
}
Set-Content $file -Value $newList
