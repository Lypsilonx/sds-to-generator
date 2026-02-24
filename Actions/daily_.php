<?php
// 0 0 * * * php ~/web/website/sds-to-generator/Actions/daily_.php > /dev/null 2>&1
// runs daily

// load the__DIR__ . /../Bot/chats.json
$chats = json_decode(file_get_contents(__DIR__ . '/../Bot/chats.json'), true);

// for every group check if the last plenum has already ha
foreach ($chats["groups"] as $chat) {
    // load__DIR__ . /../TOs/group/Plenum_to.json
    $plenum = json_decode(file_get_contents(__DIR__ . '/../TOs/' . $chat['name'] . '/Plenum_to.json'), true);

    if (strtotime($plenum['date']) < strtotime('today')) {
        // save file to__DIR__ . /../TOs/group/Plenum-<date>_to.json
        $date = $plenum['date'];
        file_put_contents(__DIR__ . '/../TOs/' . $chat['name'] . '/Plenum-' . $date . '_to.json', json_encode($plenum, JSON_PRETTY_PRINT));
        chmod(__DIR__ . '/../TOs/' . $chat['name'] . '/Plenum-' . $date . '_to.json', 0777);

        // reset the plenum
        // set date to next weekday
        $date = date('Y-m-d', strtotime('next ' . $chat['weekday']));
        // reset the TO
        $plenum = [
            'title' => 'Plenum',
            'date' => $date,
            'tops' => [],
        ];

        // save the file
        file_put_contents(__DIR__ . '/../TOs/' . $chat['name'] . '/Plenum_to.json', json_encode($plenum, JSON_PRETTY_PRINT));
        chmod(__DIR__ . '/../TOs/' . $chat['name'] . '/Plenum_to.json', 0777);
    }
}

// delete all tokens in__DIR__ .  /../Bot/tokens.json
file_put_contents(__DIR__ . '/../Bot/tokens.json', json_encode([], JSON_PRETTY_PRINT));

// delete all files in__DIR__ .  /../Bot/Files
$files = glob(__DIR__ . '/../Bot/Files/*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}

// file_get_contents() all urls in Bot/todelete.json
$todelete = json_decode(file_get_contents(__DIR__ . '/../Bot/todelete.json'), true);
foreach ($todelete as $url) {
    try {
        file_get_contents($url);
    } catch (Exception $e) {
        // do nothing
    }
}

// delete all files in Bot/todelete.json
file_put_contents(__DIR__ . '/../Bot/todelete.json', json_encode([], JSON_PRETTY_PRINT));

// open a new reion for the day in log.txt
file_put_contents(__DIR__ . '/../Bot/log.txt', '# endregion' . PHP_EOL . '# region ' . date('Y-m-d') . PHP_EOL, FILE_APPEND);