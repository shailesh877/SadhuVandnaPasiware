$path = "android\app\build.gradle"
$lines = [System.Collections.Generic.List[string]](Get-Content $path)

# 0-based index means line 107 is index 106
$indexToRemove = 106

if ($lines.Count -gt $indexToRemove) {
    $line = $lines[$indexToRemove]
    if ($line.Trim() -eq "}") {
        $lines.RemoveAt($indexToRemove)
        Write-Host "Removed premature closing brace at line 107."
        
        # After removal, everything shifts up by 1.
        # Original 'buildTypes {' was at line 117 (index 116) based on findstr?
        # findstr said:
        # 116:    buildTypes {
        # So index was 115.
        # After removing index 106, index 115 becomes 114.
        
        $insertionIndex = 114
        if ($lines[$insertionIndex].Trim().StartsWith("buildTypes")) {
            $lines.Insert($insertionIndex, "    }")
            Write-Host "Inserted closing brace before buildTypes."
            Set-Content $path $lines
            Write-Host "Success: build.gradle structure fixed."
        }
        else {
            Write-Host "Error: Expected 'buildTypes {' at index $insertionIndex but found: '$($lines[$insertionIndex])'"
            # Fallback: scan for buildTypes
            for ($i = $indexToRemove; $i -lt $lines.Count; $i++) {
                if ($lines[$i].Trim().StartsWith("buildTypes")) {
                    $lines.Insert($i, "    }")
                    Write-Host "Inserted closing brace found by scan at index $i."
                    Set-Content $path $lines
                    Write-Host "Success: build.gradle structure fixed (scanned)."
                    break
                }
            }
        }
    }
    else {
        Write-Host "Error: Line 107 '$line' is not just a closing brace. Aborting."
    }
}
else {
    Write-Host "Error: File too short."
}
