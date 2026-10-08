
$malaPath = 'C:\Users\Pasiware\.gemini\antigravity\brain\b144c601-5dcf-4d5c-991c-e51956a4a5ed\v_shaped_rose_garland_white_bg_1773924238808.png'
$diyaPath = 'C:\Users\Pasiware\.gemini\antigravity\brain\b144c601-5dcf-4d5c-991c-e51956a4a5ed\media__1773921879204.png'

$malaBase64 = [Convert]::ToBase64String([IO.File]::ReadAllBytes($malaPath))
$diyaBase64 = [Convert]::ToBase64String([IO.File]::ReadAllBytes($diyaPath))

$assetsFile = 'd:\bangosambadApp\src\screens\shok\obituary_assets.ts'

$newContent = "export const MALA = 'data:image/png;base64,$malaBase64';`n"
$newContent += "export const DIYA = 'data:image/png;base64,$diyaBase64';`n"
$newContent += "export const INCENSE = ''; // Removed per user request`n"
$newContent += "export const obituaryAssets = { mala: MALA, diya: DIYA, incenseStick: INCENSE };`n"

$newContent | Set-Content $assetsFile
