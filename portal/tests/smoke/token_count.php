<?php
$path = 'C:\\Users\\USER\\Documents\\MONDAY.COM\\Web Side Project\\customer-portal\\portal\\storage\\framework\\views\\6e9bb08a6cb7804b53fb1e7ed0609a66.php';
$src = file_get_contents($path);
$tokens = token_get_all($src);
$line = 1;
$ifStmts = [];  // stack of open if line numbers
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $newlines = substr_count($tok[1], "\n");
        $line += $newlines;
        if ($tok[0] === T_IF) {
            $ifStmts[] = $line;
        } elseif ($tok[0] === T_ENDIF) {
            if (!empty($ifStmts)) {
                array_pop($ifStmts);
            } else {
                echo "Stray endif at line $line\n";
            }
        }
    } elseif ($tok === "\n") {
        $line++;
    }
}
echo "Open @ifs left at EOF: " . count($ifStmts) . "\n";
foreach ($ifStmts as $l) echo "  - unclosed IF at line $l\n";
