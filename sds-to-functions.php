<?php
require_once 'webdav-api.php';
function renderMarkDown($serverPath)
{
  // get the JSON from the directory and render it as markdown
  $data = json_decode(file_get_contents("../TOs/" . $serverPath . "_to.json"), true);
  $group = explode("/", $serverPath)[0];
  // get the perms from the directory
  $permanent = json_decode(file_get_contents("../TOs/" . $group . "/permanent.json"), true);
  // get the events from the directory
  $events = json_decode(file_get_contents("../TOs/" . $group . "/events.json"), true);
  // load Markdown/top-format.md
  $topFormat = file_get_contents("../Markdown/top-format.md");
  $topFormatOriginal = $topFormat;
  // load Markdown/day-format.md
  $dayFormat = file_get_contents("../Markdown/day-format.md");
  $dayFormatOriginal = $dayFormat;
  // load Markdown/event-format.md
  $eventFormat = file_get_contents("../Markdown/event-format.md");
  $eventFormatOriginal = $eventFormat;
  // load Markdown/perm-format.md
  $permFormat = file_get_contents("../Markdown/perm-format.md");
  $permFormatOriginal = $permFormat;
  // Format the date as DD.MM.YYYY
  $fdate = new DateTime($data["date"]);
  $fdate = $fdate->format("d.m.Y");
  // load Markdown/mask.md
  $mask = file_get_contents("../Markdown/mask.md");

  $j = 1;
  // for each item in the JSON, replace the %key% in mask.md with the value of the key
  foreach ($data as $key => $value) {
    // if the key is "tops"
    if ($key == "tops") {
      // for each item in the JSON, replace the %key% in top-format.md with the value of the key
      $allTops = [];
      foreach ($data[$key] as $key2 => $value) {
        foreach ($data[$key][$key2] as $key3 => $value) {
          // replace the %key% in top-format.md with the value of the key
          $topFormat = str_replace("%" . $key3 . "%", $data[$key][$key2][$key3], $topFormat);
        }
        array_push($allTops, $topFormat);
        $topFormat = $topFormatOriginal;
      }
      // replace the %tops% in mask.md with the value of topFormat
      $mask = str_replace("%tops%", "\n\n\n" . implode("\n\n\n", $allTops), $mask);
    } else if ($key == "date") {
      $mask = str_replace("%date%", $fdate, $mask);
    } else {
      // replace the %key% in mask.md with the value of the key
      $mask = str_replace("%" . $key . "%", $data[$key], $mask);
    }
  }

  $p = 1;
  $allPermTops = [
    "\n\n\n### %nn%. Laufende Arbeitsaufträge",
  ];
  $perm = false;
  // for each top in permanent.json
  foreach ($permanent["tops"] as $key2 => $value) {
    $perm = true;

    // if content begins with a list (* or 1.) remove the \ in permFormat
    if (substr($permanent["tops"][$key2]["content"], 0, 1) == "*" || substr($permanent["tops"][$key2]["content"], 0, 2) == "1.") {
      $permFormat = str_replace("\\", "", $permFormat);
    }

    // replace "<number>." with "   <number>." in content (except for the first line)
    $permanent["tops"][$key2]["content"] = preg_replace("/\r\n([0-9]+). /", "\r\n   $1. ", $permanent["tops"][$key2]["content"]);

    foreach ($permanent["tops"][$key2] as $key3 => $value) {
      // replace the %key% in top-format.md with the value of the key
      $permFormat = str_replace("%" . $key3 . "%", $permanent["tops"][$key2][$key3], $permFormat);
    }

    // replace the %num% in top-format.md with the value of
    $permFormat = str_replace("%num%", $p, $permFormat);

    // replace \r\n\r\n with \r\r\r\r in content
    $permFormat = str_replace("\r\n\r\n", "\r\r\r\r", $permFormat);

    // add "   " to the beginning of each line in content
    $permFormat = str_replace("\r\n", "\r\n   ", $permFormat);

    // replace \r\r\r\r with \r\n\r\n in content
    $permFormat = str_replace("\r\r\r\n", "\r\n\r\n", $permFormat);

    $p++;
    array_push($allPermTops, $permFormat);
    $permFormat = $permFormatOriginal;
  }

  if ($perm) {
    // replace the %permanent% in mask.md with the value of topFormat
    $mask = str_replace("%permanent%", implode("\n", $allPermTops), $mask);
  } else {
    $mask = str_replace("%permanent%", "", $mask);
  }

  $wrb = [];
  $wvs = [];

  //order events by date
  $eventlist = [];
  foreach ($events["events"] as $key2 => $value) {
    array_push($eventlist, $events["events"][$key2]);
  }
  usort($eventlist, function ($a, $b) {
    if ($a['date'] == $b['date']) {
      return 0;
    }
    return ($a['date'] < $b['date']) ? -1 : 1;
  });

  $date = strtotime($data["date"]);

  // for each day in events.json
  foreach ($eventlist as $event) {
    $date2 = strtotime($event['date']);

    // is the day within the last 7 days of data["date"] and not on the same day?
    if ($date2 >= strtotime('-7 days', $date) && $date2 < $date) {
      array_push($wrb, $event);
    }
    // is the day within the next 7 days of data["date"] or on the same day?
    if ($date2 >= $date && $date2 < strtotime('+7 days', $date)) {
      array_push($wvs, $event);
    }
  }

  // for every different day in wrb add a dayFormat to allDays
  $allDays = [];
  $lastDay = "";
  foreach ($wrb as $key2 => $value) {
    $day = $wrb[$key2]["date"];

    // if the day is not the same as the last day
    if ($day != $lastDay) {
      $lastDay = $day;
      // replace %day% with the weekday (in german)
      $dayFormat = str_replace("%day%", getWeekday($day), $dayFormat);

      // replace %date% with the date
      $dayFormat = str_replace("%date%", getDateFormat($day), $dayFormat);

      $allEvents = [];
      foreach ($wrb as $key2 => $value) {
        if ($wrb[$key2]["date"] == $day) {
          $eventFormat = str_replace("%title%", $wrb[$key2]["title"], $eventFormat);
          if ($wrb[$key2]["content"] == "" || $wrb[$key2]["content"] == " " || preg_match('/^\(?s(\.|iehe) TOP\)?$/i', $wrb[$key2]["content"])) {
            $eventFormat = str_replace("%content%", "", $eventFormat);
          } else {
            $eventFormat = str_replace("%content%", "\r\n      * " . $wrb[$key2]["content"], $eventFormat);
          }

          array_push($allEvents, $eventFormat);
          $eventFormat = $eventFormatOriginal;
        }
      }

      // replace %events% with all events of the day
      $dayFormat = str_replace("%events%", implode("\n", $allEvents), $dayFormat);

      array_push($allDays, $dayFormat);
      $dayFormat = $dayFormatOriginal;
    }
  }

  // replace %wochenrueckblick% with all days
  $mask = str_replace("%wochenrueckblick%", implode("\n\n", $allDays), $mask);

  // for every different day in wvs add a dayFormat to allDays
  $allDays = [];
  $lastDay = "";
  foreach ($wvs as $key2 => $value) {
    $day = $wvs[$key2]["date"];

    // if the day is not the same as the last day
    if ($day != $lastDay) {
      $lastDay = $day;
      // replace %day% with the weekday (in german)
      $dayFormat = str_replace("%day%", getWeekday($day), $dayFormat);

      // replace %date% with the date
      $dayFormat = str_replace("%date%", getDateFormat($day), $dayFormat);

      $allEvents = [];
      foreach ($wvs as $key2 => $value) {
        if ($wvs[$key2]["date"] == $day) {
          $eventFormat = str_replace("%title%", $wvs[$key2]["title"], $eventFormat);
          if ($wvs[$key2]["content"] == "" || $wvs[$key2]["content"] == " ") {
            $eventFormat = str_replace("%content%", "", $eventFormat);
          } else if (preg_match('/^\(?s(\.|iehe) TOP\)?$/i', $wvs[$key2]["content"])) {
            $content = $wvs[$key2]["content"];
            $topId = getTopId($wvs[$key2]["title"], $data["tops"]);
            if ($topId != "") {
              $topTitle = getTopTitle($topId, $data["tops"]);

              // get the number of the top
              $topNumber = 0;
              foreach ($data["tops"] as $key3 => $value) {
                if ($data["tops"][$key3]["id"] == $topId) {
                  $topNumber = $key3;
                  break;
                }
              }

              $link = strtolower($topTitle);
              $link = str_replace(" ", "-", $link);
              $link = preg_replace('/-+/', '-', $link);
              $link = str_replace("ü", "u", $link);
              $link = str_replace("ö", "o", $link);
              $link = str_replace("ä", "a", $link);
              $link = preg_replace('/[^a-zA-Z0-9-]/', '', $link);

              $content = "Siehe [" . $topTitle . "](#h-" . ($topNumber + 5) . "-"
                . $link
                . ")";
            }

            $eventFormat = str_replace("%content%", "\r\n      * " . $content, $eventFormat);
          } else {
            $eventFormat = str_replace("%content%", "\r\n      * " . $wvs[$key2]["content"], $eventFormat);
          }
          array_push($allEvents, $eventFormat);
          $eventFormat = $eventFormatOriginal;
        }
      }

      // replace %events% with all events of the day
      $dayFormat = str_replace("%events%", implode("\n", $allEvents), $dayFormat);

      array_push($allDays, $dayFormat);
      $dayFormat = $dayFormatOriginal;
    }
  }

  // replace %wochenvorschau% with all days
  $mask = str_replace("%wochenvorschau%", implode("\n\n", $allDays), $mask);

  // replace all occurences of %nn% in mask.md with numbers counting up from j
  while (strpos($mask, "%nn%") != "") {
    // replace the first occurence of %nn% with j
    $mask = preg_replace("/%nn%/", $j, $mask, 1);
    $j++;
  }

  // seed the random number generator with the date
  // hash the date
  $hdate = hash("sha256", $data["date"]);
  // convert the hash to an integer
  $seed = hexdec(substr($hdate, 0, 8));
  srand($seed);

  // replace %awarenessfrage% and %zusatzfrage% with a random question
  $mask = str_replace("%awarenessfrage%", generateQuestion(), $mask);
  $mask = str_replace("%zusatzfrage%", generateQuestion(), $mask);

  // replace &quot; with "
  $mask = str_replace("&quot;", '"', $mask);

  // replace &amp; with &
  $mask = str_replace("&amp;", "&", $mask);

  // replace &lt; with <
  $mask = str_replace("&lt;", "<", $mask);

  // replace &gt; with >
  $mask = str_replace("&gt;", ">", $mask);

  // replace [book-list:single:<type>|<title>] with link to book-list
  $mask = preg_replace('/\[book-list:single:([a-zA-Z0-9äüöß\-\'\’\´: ]*)\|([a-zA-Z0-9äüöß\-\'\’\´: ]*)\]/', "[$2](https://www.politischdekoriert.de/book-list/actions/single-view.php?type=$1&title=$2)", $mask);

  // replace [book-list:...] with link to book-list
  $mask = preg_replace('/\[book-list:([a-zA-Z0-9äüöß\-\'\’\´: ]*)\]/', "[book-list](https://www.politischdekoriert.de/book-list?dir=$1)", $mask);

  // replace \r\n not followed by ' ' or '\' with \r\n\\
  if (isset($_SERVER["HTTP_USER_AGENT"]) && strpos($_SERVER["HTTP_USER_AGENT"], "Safari") != -1) {
    // replace \r\n\r\n with \r\r\r\r
    $mask = str_replace("\r\n\r\n", "\r\r\r\r", $mask);

    // replace \r\n with \\r\n if not followed by ' ' or '\' (do not remove the character after \r\n)
    $mask = str_replace("\r\n(?![\\ *]|[0-9].)", "\\\r\n", $mask);

    // replace \r\r\r\r with \r\n\r\n
    $mask = str_replace("\r\r\r\r", "\r\n\r\n", $mask);
  } else {
    $mask = str_replace("(?<!\r\n)\r\n(?![\\ *]|[0-9].)", "\\\r\n", $mask);
  }

  return [
    "markdown" => $mask,
    "filename" => str_replace("-", "_", $data["date"])
  ];
}

function download($content, $filename)
{
  // download the rendered markdown as a .md file
  header("Content-type: text/markdown");
  header("Content-Disposition: attachment; filename=" . $filename);
  echo $content;
}
function getTopId($name, array $tops, $minSimilarity = 50)
{
  $topId = "";
  $similarity = 0;
  $closestId = "";
  foreach ($tops as $top) {
    if ($top['title'] == $name) {
      $topId = $top['id'];
      break;
    }

    similar_text($top['title'], $name, $percent);
    if ($percent > $similarity) {
      $closestId = $top['id'];
      $similarity = $percent;
    }
  }

  if ($topId == "" && $similarity >= $minSimilarity) {
    $topId = $closestId;
  }

  return $topId;
}

function getTopTitle($id, array $tops): string
{
  $topTitle = "";
  foreach ($tops as $top) {
    if ($top['id'] == $id) {
      $topTitle = $top['title'];
      break;
    }
  }

  return $topTitle;
}

function getCloudPath($serverPath, $date)
{
  $group = explode("/", $serverPath)[0];

  // load Bot/chats.json
  $chats = json_decode(file_get_contents("../Bot/chats.json"), true);

  //find chat where name is dir
  for ($i = 0; $i < count($chats["groups"]); $i++) {
    if ($chats["groups"][$i]["name"] == $group) {
      $cloudPath = convertCloudPath($chats["groups"][$i]["dir"], $date);
      break;
    }
  }

  // encode to get rid of spaces
  $cloudPath = str_replace(" ", "%20", $cloudPath);

  return $cloudPath;
}

function convertCloudPath(string $cloudPath, int $timestamp = null): string
{
  if ($timestamp == null) {
    $timestamp = time();
  }

  $yearOffset = 0;
  // replace %Semy% or %Sem% or %SemY% with semester
  $cloudPath = preg_replace_callback("/%Sem%/", function ($matches) use ($timestamp, &$yearOffset) {
    // WiSe or SoSe
    $semester = date("m", $timestamp) < 4 || date("m", $timestamp) > 9 ? "WiSe" : "SoSe";

    if (date("m", $timestamp) < 4) {
      $yearOffset = -1;
    }

    return $semester;
  }, $cloudPath);

  if ($yearOffset == -1) {
    $timestamp = strtotime("-1 year", $timestamp);
  }

  // replace %<date format>% with date
  $cloudPath = preg_replace_callback("/%.*%/", function ($matches) use ($timestamp) {
    $replacement = date(substr($matches[0], 1, -1), $timestamp);

    // translate month January to Januar etc.
    $replacement = preg_replace_callback("/[a-zA-Z]+/", function ($matches) {
      switch ($matches[0]) {
        case "January":
          return "Januar";
        case "February":
          return "Februar";
        case "March":
          return "März";
        case "April":
          return "April";
        case "May":
          return "Mai";
        case "June":
          return "Juni";
        case "July":
          return "Juli";
        case "August":
          return "August";
        case "September":
          return "September";
        case "October":
          return "Oktober";
        case "November":
          return "November";
        case "December":
          return "Dezember";
        default:
          return $matches[0];
      }
    }, $replacement);

    // translate weekday Monday to Montag etc.
    $replacement = preg_replace_callback("/[a-zA-Z]+/", function ($matches) {
      switch ($matches[0]) {
        case "Monday":
          return "Montag";
        case "Tuesday":
          return "Dienstag";
        case "Wednesday":
          return "Mittwoch";
        case "Thursday":
          return "Donnerstag";
        case "Friday":
          return "Freitag";
        case "Saturday":
          return "Samstag";
        case "Sunday":
          return "Sonntag";
        default:
          return $matches[0];
      }
    }, $replacement);

    // translate weekday Mon to Mo etc.
    $replacement = preg_replace_callback("/[a-zA-Z]+/", function ($matches) {
      switch ($matches[0]) {
        case "Mon":
          return "Mo";
        case "Tue":
          return "Di";
        case "Wed":
          return "Mi";
        case "Thu":
          return "Do";
        case "Fri":
          return "Fr";
        case "Sat":
          return "Sa";
        case "Sun":
          return "So";
        default:
          return $matches[0];
      }
    }, $replacement);

    // translate month Jan to Jan etc.
    $replacement = preg_replace_callback("/[a-zA-Z]+/", function ($matches) {
      switch ($matches[0]) {
        case "Jan":
          return "Jan";
        case "Feb":
          return "Feb";
        case "Mar":
          return "Mär";
        case "Apr":
          return "Apr";
        case "May":
          return "Mai";
        case "Jun":
          return "Jun";
        case "Jul":
          return "Jul";
        case "Aug":
          return "Aug";
        case "Sep":
          return "Sep";
        case "Oct":
          return "Okt";
        case "Nov":
          return "Nov";
        case "Dec":
          return "Dez";
        default:
          return $matches[0];
      }
    }, $replacement);

    return $replacement;
  }, $cloudPath);

  return $cloudPath;
}

function upload($markdown, $filename, $serverPath, bool $bot = false, bool $force = false)
{
  $date = new DateTime(str_replace("_", "-", $filename));
  $cloudPath = getCloudPath($serverPath, $date->getTimestamp());
  if ($bot) {
    $filename = $filename . ".md";
  } else {
    $filename = $filename . "-Tagesordnung.md";
  }

  $sdsCloud = new WebdavApi("../webdavuser.config");

  if ($bot && !$force) {
    if ($sdsCloud->fileExists($filename, $cloudPath)) {
      return false;
    }
  }

  return $sdsCloud->uploadFile($filename, $markdown, $cloudPath);
}

function getWeekday($date)
{
  $weekdays = [
    "Sonntag",
    "Montag",
    "Dienstag",
    "Mittwoch",
    "Donnerstag",
    "Freitag",
    "Samstag",
  ];
  return $weekdays[date("w", strtotime($date))];
}

function getDateFormat($date)
{
  $day = date("d", strtotime($date));
  $month = date("m", strtotime($date));
  return $day . "." . $month . ".";
}

function generateQuestion()
{
  switch (rand(0, 3)) {
    case 0:
      $things = [
        "Farbe",
        "Tier",
        "Film",
        "Schauspieler*in",
        "Buch",
        "Spiel",
        "Song",
        "Sänger*in",
        "Band",
        "Essen",
        "Getränk",
        "Sportart",
        "Sportler*in",
        "Theorie",
        "Wissenschaftler*in",
      ];
      $question =
        "Was ist dein(e)" . ((rand(0, 1) == 0)
          ? " lieblings"
          : " hass") .
        " " .
        $things[rand(0, count($things) - 1)] .
        "?";
      break;
    case 1:
      $things = [
        "Clowns",
        "Holzöfen",
        "England",
        "Kaffee",
        "Vögeln",
      ];
      $question =
        "Was ist deine Meinung zu " .
        $things[rand(0, count($things) - 1)] .
        "?";
      break;
    case 2:
      $things = [
        "Wort",
        "Konzept",
        "Mensch",
        "Sache",
        "Erfindung",
        "Spruch",
      ];
      $question =
        "Welche(s/r) " .
        $things[rand(0, count($things) - 1)] .
        " kommt dir gerade in den Sinn, und warum?";
      break;
    case 3:
      $comparables = [
        "Bier",
        "Kaffee",
        "Kuchen",
        "Brot",
        "Käse",
        "Wurst",
        "Schokolade",
        "Tische",
        "Stühle",
        "Bücher",
        "Filme",
        "Spielzeug",
        "Kinder",
        "Erwachsene",
        "Tiere",
        "Pflanzen",
        "Menschen",
        "Marmelade",
        "Ketchup",
        "Senf",
        "Mayonnaise",
        "Hunde",
        "Katzen",
        "Mäuse",
        "Fische",
        "Vögel",
        "Schafe",
        "Ziegen",
        "Kühe",
        "Pferde",
        "Elefanten",
        "Löwen",
        "Tiger",
        "Bären",
        "Krokodile",
      ];
      $question =
        "Was ist besser: " .
        $comparables[rand(0, count($comparables) - 1)] .
        " oder " .
        $comparables[rand(0, count($comparables) - 1)] .
        "?";
      break;
  }
  return $question;
}