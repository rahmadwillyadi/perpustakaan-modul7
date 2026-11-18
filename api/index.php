<?php
require __DIR__ . '/../public/index.php';

// Paksa penyimpanan cache view ke /tmp
app()->useStoragePath('/tmp');
putenv('VIEW_COMPILED_PATH=/tmp');
