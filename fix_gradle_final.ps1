$propPath = "android\gradle.properties"
$propContent = "MYAPP_UPLOAD_STORE_FILE=upload-keystore.jks`nMYAPP_UPLOAD_KEY_ALIAS=my-key-alias`nMYAPP_UPLOAD_STORE_PASSWORD=123456`nMYAPP_UPLOAD_KEY_PASSWORD=123456"
Set-Content -Path $propPath -Value $propContent
Write-Host "Created android\gradle.properties"

$buildPath = "android\app\build.gradle"
$content = Get-Content $buildPath -Raw

# 1. Add release signing config
$signingParams = @"
        release {
            if (project.hasProperty('MYAPP_UPLOAD_STORE_FILE')) {
                storeFile file(MYAPP_UPLOAD_STORE_FILE)
                storePassword MYAPP_UPLOAD_STORE_PASSWORD
                keyAlias MYAPP_UPLOAD_KEY_ALIAS
                keyPassword MYAPP_UPLOAD_KEY_PASSWORD
            }
        }
"@

if ($content -notmatch "signingConfigs\s*\{\s*release") {
    # Insert 'release' block before the closing brace of 'signingConfigs'
    # We look for 'debug { ... }' and append after it, or just insert into signingConfigs
    
    # Simple regex replacement to append release config after debug config closing brace
    # Assuming standard indentation from the file check
    $content = $content -replace "(signingConfigs\s*\{\s*debug\s*\{[\s\S]*?\}\s*\}?)", "`$1`n$signingParams"
    Write-Host "Added signingConfigs.release"
}

# 2. Update release buildType to use release signing config
# Replace 'signingConfig signingConfigs.debug' inside release block
# We must be careful not to replace it in debug block
# The file has:
# release {
# ...
# signingConfig signingConfigs.debug

if ($content -match "release\s*\{[\s\S]*?signingConfig signingConfigs.debug") {
    # match release block start, then lazy match until signingConfig, ensure it is inside
    $content = [regex]::Replace($content, "(release\s*\{[\s\S]*?)signingConfig signingConfigs.debug", "`$1signingConfig signingConfigs.release")
    Write-Host "Updated release buildType to use signingConfigs.release"
}

Set-Content -Path $buildPath -Value $content
Write-Host "Updated android\app\build.gradle"
