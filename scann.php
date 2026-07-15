<?php

$start_dir = __DIR__; 


$suspicious_patterns = [
    'eval(',
    'base64_decode(',
    'gzinflate(',
    'str_rot13(',
    'shell_exec(',
    'passthru(',
    'exec(',
    'system(',
    'popen(',
    'proc_open(',
];


$suspicious_files = [
    'html4.php',
    'css1.php',
    'temp.php',
    'class-cache.php',
];

echo "<h2>Scanning WordPress for suspicious PHP files...</h2>";
echo "<ul>";

function scan_dir($dir, $patterns, $filenames) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $filepath = $file->getPathname();
        $filename = $file->getFilename();

        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'php') continue;

        // Flag if filename matches
        if (in_array(strtolower($filename), $filenames)) {
            echo "<li><b>Suspicious filename:</b> $filepath</li>";
        }

        // Scan contents for suspicious patterns
        $contents = @file_get_contents($filepath);
        if ($contents === false) continue;

        foreach ($patterns as $pattern) {
            if (stripos($contents, $pattern) !== false) {
                echo "<li><b>Suspicious pattern '$pattern' found in:</b> $filepath</li>";
                break;
            }
        }
    }
}

scan_dir($start_dir, $suspicious_patterns, $suspicious_files);

echo "</ul>";
echo "<p>Scan complete. Delete this file after checking results!</p>";
?>