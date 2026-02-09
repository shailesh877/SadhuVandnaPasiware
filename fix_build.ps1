$p = "android\gradle.properties"
$c = Get-Content $p -Raw
if ($c -notmatch "MYAPP_UPLOAD_STORE_FILE") {
    Add-Content $p "`nMYAPP_UPLOAD_STORE_FILE=upload-keystore.jks`nMYAPP_UPLOAD_KEY_ALIAS=my-key-alias`nMYAPP_UPLOAD_STORE_PASSWORD=123456`nMYAPP_UPLOAD_KEY_PASSWORD=123456"
    Write-Host "Updated gradle.properties"
} else {
    Write-Host "gradle.properties already updated"
}

$p2 = "android\app\build.gradle"
$c2 = Get-Content $p2 -Raw
$sigConfig = @"
    signingConfigs {
        release {
            if (project.hasProperty('MYAPP_UPLOAD_STORE_FILE')) {
                storeFile file(MYAPP_UPLOAD_STORE_FILE)
                storePassword MYAPP_UPLOAD_STORE_PASSWORD
                keyAlias MYAPP_UPLOAD_KEY_ALIAS
                keyPassword MYAPP_UPLOAD_KEY_PASSWORD
            }
        }
    }
"@

if ($c2 -notmatch "signingConfigs\s*\{\s*release") {
    $c2 = $c2 -replace "buildTypes \{", "$sigConfig`n    buildTypes {"
    Write-Host "Injected signingConfigs"
}

if ($c2 -notmatch "signingConfig signingConfigs.release") {
    $c2 = $c2 -replace "minifyEnabled enableMinifyInReleaseBuilds", "minifyEnabled enableMinifyInReleaseBuilds`n            signingConfig signingConfigs.release"
    Write-Host "Applied signingConfig to release"
}

Set-Content $p2 $c2
Write-Host "Done"
