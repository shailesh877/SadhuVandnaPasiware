Set-Location android
./gradlew clean
if (Test-Path "app/.cxx") { Remove-Item -Recurse -Force "app/.cxx" }
if (Test-Path ".cxx") { Remove-Item -Recurse -Force ".cxx" }
if (Test-Path "build") { Remove-Item -Recurse -Force "build" }
if (Test-Path "app/build") { Remove-Item -Recurse -Force "app/build" }
./gradlew bundleRelease --info | Out-File "..\\build_final_retry.txt"
