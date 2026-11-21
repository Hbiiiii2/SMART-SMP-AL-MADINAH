<?php
$s=microtime(true);
file_get_contents("http://127.0.0.1:8000/api/e-rappor/siswa/Azna-Helva-Ramadhania");
echo "Time: ".(microtime(true)-$s)."s\n";
