<?php

spl_autoload_register(function ($className) {
    // Convert namespace separators (\) to directory separators (/)
    $classFile = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    
    // Construct the full path to the class file
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . $classFile;
    
    // Check if the file exists before requiring it
    if (file_exists($filePath)) {
        require $filePath;
    }
});

