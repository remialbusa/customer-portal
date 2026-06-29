<?php
$path = 'C:\\Users\\USER\\Documents\\MONDAY.COM\\Web Side Project\\customer-portal\\portal\\resources\\views\\livewire\\tsp\\tickets\\create-service-report.blade.php';
$src = file_get_contents($path);

$tokens = token_get_all($src);
$bladeIf = 0; $bladeElseif = 0; $bladeElse = 0; $bladeEndif = 0;
foreach ($tokens as $tok) {
    if (!is_array($tok)) continue;
    if ($tok[0] === T_INLINE_HTML) {
        $html = $tok[1];
        $bladeIf += substr_count($html, '@if(');
        $bladeIf += substr_count($html, '@if (');
        $bladeElseif += substr_count($html, '@elseif');
        $bladeElse += substr_count($html, '@else');
        $bladeEndif += substr_count($html, '@endif');
    }
}
echo "Blade @if=$bladeIf  @elseif=$bladeElseif  @else=$bladeElse  @endif=$bladeEndif" . PHP_EOL;
echo "Diff (if-endif) = " . ($bladeIf - $bladeEndif) . PHP_EOL;
