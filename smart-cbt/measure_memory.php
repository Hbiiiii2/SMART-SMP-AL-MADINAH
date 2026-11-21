<?php
$workers = [];
$totalRss = 0;
foreach (glob('/proc/[0-9]*/status') as $file) {
    $content = @file_get_contents($file);
    if (!$content) continue;
    if (preg_match('/Name:\s+(.+)/', $content, $nameMatch) && preg_match('/VmRSS:\s+([0-9]+)\s+kB/', $content, $rssMatch)) {
        $name = trim($nameMatch[1]);
        $rssMb = round($rssMatch[1] / 1024, 2);
        $workers[] = ['name' => $name, 'rss_mb' => $rssMb];
        $totalRss += $rssMb;
    }
}
echo json_encode(['total_rss_mb' => $totalRss, 'processes' => $workers], JSON_PRETTY_PRINT) . PHP_EOL;
