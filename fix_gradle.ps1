$lines = Get-Content "temp_build_debug.txt"
# Fix line 118 (index 117): was 'signingConfig signingConfigs.release', needs 'signingConfig signingConfigs.debug'
$lines[117] = "            signingConfig signingConfigs.debug"
# Line 123 (index 122) was 'signingConfig signingConfigs.release', should remain so.
# Verify it wasn't changed in temp file (it wasn't).
Set-Content "android\app\build.gradle" -Value $lines
