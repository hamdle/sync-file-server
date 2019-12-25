<?php

if (count($argv) < 3) {
    print "SCRIPT FAILED.\nNot enough arguments.\n";
    print "Example: php script.php <filename> <directory>\n";
    print "Example: php /home/eric/scripts/sync-file-server.php checksum.chk /media/eric/PIE/2600\n";
    die();
}

$file_name = $argv[1];
$dir_name = $argv[2];
$file_count = null;

// (1) This first check will test for file changes.
if (sync_files_test($file_name, $file_count)) {
    sync_files();
    update_checksum($file_name, $dir_name);
} else {
    update_checksum($file_name, $dir_name);

    // (2) This second check will test for new files.
    if (sync_files_test($file_name, $file_count)) {
        sync_files();
    } else {
        print "Do not sync files.\n";
    }
}

// Functions
function sync_files_test($file_name, &$file_count) {
    $cmd = 'md5sum -c /tmp/'.$file_name.' | awk \'{print $2}\'';
    $results = shell_exec($cmd);
    
    $results_as_numeric = array_map(
        function ($item) {
            if ($item == "OK") {
                return 0;
            }
            return 1;
        },
        explode("\n", $results)
    );
    
    $new_file_count = count($results_as_numeric);
    if ($file_count == null) {
        $file_count = $new_file_count;
    } else if ($file_count != $new_file_count) {
        return true;
    }

    if (array_sum($results_as_numeric) > 1) {
        return true;
    }
    
    return false;
}

function update_checksum($file_name, $dir_name) {
    $cmd = 'find '.$dir_name.' -type f -exec md5sum {} \; > /tmp/'.$file_name;
    shell_exec($cmd);
}

function sync_files() {
    $cmd1 = "bash /home/eric/scripts/copy-notes.sh";
    shell_exec($cmd1);
    $cmd1 = "bash /home/eric/scripts/upload-notes.sh";
    shell_exec($cmd1);
    print "Sync files.\n";
}
?>
