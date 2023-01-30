function resize() {
  if (window.innerWidth < 800) {
    document.querySelector("body").classList.add("hovermenu");
  } else {
    document.querySelector("body").classList.remove("hovermenu");
  }
}

function renderMarkDown(dir, process = () => {}, extern = "", cid = "") {
  // get the JSON from the directory and render it as markdown
  fetch(extern + "TOs/" + dir + "_to.json")
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      // get the perms from the directory
      var dir2 = dir.split("/")[0];
      fetch(extern + "TOs/" + dir2 + "/permanent.json")
        .then(function (response) {
          return response.json();
        })
        .then(function (permanent) {
          // get the events from the directory
          var dir2 = dir.split("/")[0];
          fetch(extern + "TOs/" + dir2 + "/events.json")
            .then(function (response) {
              return response.json();
            })
            .then(function (events) {
              // load Markdown/top-format.md
              fetch(extern + "Markdown/top-format.md")
                .then(function (response) {
                  return response.text();
                })
                .then(function (topFormat) {
                  var topFormatOriginal = topFormat;
                  // load Markdown/day-format.md
                  fetch(extern + "Markdown/day-format.md")
                    .then(function (response) {
                      return response.text();
                    })
                    .then(function (dayFormat) {
                      var dayFormatOriginal = dayFormat;
                      // load Markdown/event-format.md
                      fetch(extern + "Markdown/event-format.md")
                        .then(function (response) {
                          return response.text();
                        })
                        .then(function (eventFormat) {
                          var eventFormatOriginal = eventFormat;
                          // load Markdown/perm-format.md
                          fetch(extern + "Markdown/perm-format.md")
                            .then(function (response) {
                              return response.text();
                            })
                            .then(function (permFormat) {
                              var permFormatOriginal = permFormat;
                              // Format the date as DD.MM.YYYY
                              var fdate = new Date(data["date"]);
                              var day = fdate.getDate();
                              var month = fdate.getMonth() + 1;
                              var year = fdate.getFullYear();
                              if (day < 10) {
                                day = "0" + day;
                              }
                              if (month < 10) {
                                month = "0" + month;
                              }
                              fdate = day + "." + month + "." + year;
                              // load Markdown/mask.md
                              fetch(extern + "Markdown/mask.md")
                                .then(function (response) {
                                  return response.text();
                                })
                                .then(function (mask) {
                                  var j = 1;
                                  // for each item in the JSON, replace the %key% in mask.md with the value of the key
                                  for (var key in data) {
                                    // if the key is "tops"
                                    if (key == "tops") {
                                      // for each item in the JSON, replace the %key% in top-format.md with the value of the key
                                      var allTops = [];
                                      for (var key2 in data[key]) {
                                        for (var key3 in data[key][key2]) {
                                          // replace the %key% in top-format.md with the value of the key
                                          topFormat = topFormat.replace(
                                            new RegExp("%" + key3 + "%", "g"),
                                            data[key][key2][key3]
                                          );
                                        }
                                        allTops.push(topFormat);
                                        topFormat = topFormatOriginal;
                                      }
                                      // replace the %tops% in mask.md with the value of topFormat
                                      mask = mask.replace(
                                        new RegExp("%tops%", "g"),
                                        "\n\n\n" + allTops.join("\n\n\n")
                                      );
                                    } else if (key == "date") {
                                      mask = mask.replace(
                                        new RegExp("%date%", "g"),
                                        fdate
                                      );
                                    } else {
                                      // replace the %key% in mask.md with the value of the key
                                      mask = mask.replace(
                                        new RegExp("%" + key + "%", "g"),
                                        data[key]
                                      );
                                    }
                                  }

                                  var p = 1;
                                  var allPermTops = [
                                    "\n\n\n### %nn%. Laufende Arbeitsaufträge",
                                  ];
                                  var perm = false;
                                  // for each top in permanent.json
                                  for (var key2 in permanent["tops"]) {
                                    perm = true;

                                    // if content begins with a list (* or 1.) remove the \ in permFormat
                                    if (
                                      permanent["tops"][key2][
                                        "content"
                                      ].startsWith("*") ||
                                      permanent["tops"][key2][
                                        "content"
                                      ].startsWith("1.")
                                    ) {
                                      permFormat = permFormat.replace(
                                        new RegExp("\\\\", "g"),
                                        ""
                                      );
                                    }

                                    // replace "<number>." with "   <number>." in content (except for the first line)
                                    permanent["tops"][key2]["content"] =
                                      permanent["tops"][key2][
                                        "content"
                                      ].replace(
                                        new RegExp("\r\n([0-9]+). ", "g"),
                                        "\r\n   $1. "
                                      );

                                    for (var key3 in permanent["tops"][key2]) {
                                      // replace the %key% in top-format.md with the value of the key
                                      permFormat = permFormat.replace(
                                        new RegExp("%" + key3 + "%", "g"),
                                        permanent["tops"][key2][key3]
                                      );
                                    }
                                    // replace the %num% in top-format.md with the value of
                                    permFormat = permFormat.replace(
                                      new RegExp("%num%", "g"),
                                      p
                                    );
                                    p++;
                                    allPermTops.push(permFormat);
                                    permFormat = permFormatOriginal;
                                  }

                                  if (perm) {
                                    // replace the %permanent% in mask.md with the value of topFormat
                                    mask = mask.replace(
                                      new RegExp("%permanent%", "g"),
                                      allPermTops.join("\n")
                                    );
                                  } else {
                                    mask = mask.replace(
                                      new RegExp("%permanent%", "g"),
                                      ""
                                    );
                                  }

                                  var wrb = [];
                                  var wvs = [];

                                  //order events by date
                                  var eventlist = [];
                                  for (var key2 in events["events"]) {
                                    eventlist.push(events["events"][key2]);
                                  }
                                  eventlist.sort(function (a, b) {
                                    return new Date(a.date) - new Date(b.date);
                                  });

                                  // for each day in events.json
                                  for (var event of eventlist) {
                                    var date = new Date(data["date"]);
                                    var date2 = new Date(event["date"]);
                                    // is the day within the last 7 days of data["date"] and not on the same day?
                                    if (
                                      date.getTime() - date2.getTime() <=
                                        604800000 &&
                                      date.getTime() - date2.getTime() > 0
                                    ) {
                                      wrb.push(event);
                                    }
                                    // is the day within the next 7 days of data["date"] or on the same day?
                                    if (
                                      date2.getTime() - date.getTime() <
                                        604800000 &&
                                      date2.getTime() - date.getTime() >= 0
                                    ) {
                                      wvs.push(event);
                                    }
                                  }

                                  // for every different day in wrb add a dayFormat to allDays
                                  var allDays = [];
                                  var lastDay = "";
                                  for (var key2 in wrb) {
                                    var day = wrb[key2]["date"];

                                    // if the day is not the same as the last day
                                    if (day != lastDay) {
                                      lastDay = day;
                                      // replace %day% with the weekday (in german)
                                      dayFormat = dayFormat.replace(
                                        new RegExp("%day%", "g"),
                                        getWeekday(day)
                                      );

                                      // replace %date% with the date
                                      dayFormat = dayFormat.replace(
                                        new RegExp("%date%", "g"),
                                        getDate(day)
                                      );

                                      var allEvents = [];
                                      for (var key2 in wrb) {
                                        if (wrb[key2]["date"] == day) {
                                          eventFormat = eventFormat.replace(
                                            new RegExp("%title%", "g"),
                                            wrb[key2]["title"]
                                          );
                                          if (
                                            wrb[key2]["content"] == "" ||
                                            wrb[key2]["content"] == " "
                                          ) {
                                            eventFormat = eventFormat.replace(
                                              new RegExp("%content%", "g"),
                                              ""
                                            );
                                          } else {
                                            eventFormat = eventFormat.replace(
                                              new RegExp("%content%", "g"),
                                              "\r\n      * " +
                                                wrb[key2]["content"]
                                            );
                                          }

                                          allEvents.push(eventFormat);
                                          eventFormat = eventFormatOriginal;
                                        }
                                      }

                                      // replace %events% with all events of the day
                                      dayFormat = dayFormat.replace(
                                        new RegExp("%events%", "g"),
                                        allEvents.join("\n")
                                      );

                                      allDays.push(dayFormat);
                                      dayFormat = dayFormatOriginal;
                                    }
                                  }

                                  // replace %wochenrueckblick% with all days
                                  mask = mask.replace(
                                    new RegExp("%wochenrueckblick%", "g"),
                                    allDays.join("\n\n")
                                  );

                                  // for every different day in wvs add a dayFormat to allDays
                                  var allDays = [];
                                  var lastDay = "";
                                  for (var key2 in wvs) {
                                    var day = wvs[key2]["date"];

                                    // if the day is not the same as the last day
                                    if (day != lastDay) {
                                      lastDay = day;
                                      // replace %day% with the weekday (in german)
                                      dayFormat = dayFormat.replace(
                                        new RegExp("%day%", "g"),
                                        getWeekday(day)
                                      );

                                      // replace %date% with the date
                                      dayFormat = dayFormat.replace(
                                        new RegExp("%date%", "g"),
                                        getDate(day)
                                      );

                                      var allEvents = [];
                                      for (var key2 in wvs) {
                                        if (wvs[key2]["date"] == day) {
                                          eventFormat = eventFormat.replace(
                                            new RegExp("%title%", "g"),
                                            wvs[key2]["title"]
                                          );
                                          eventFormat = eventFormat.replace(
                                            new RegExp("%content%", "g"),
                                            wvs[key2]["content"]
                                          );
                                          allEvents.push(eventFormat);
                                          eventFormat = eventFormatOriginal;
                                        }
                                      }

                                      // replace %events% with all events of the day
                                      dayFormat = dayFormat.replace(
                                        new RegExp("%events%", "g"),
                                        allEvents.join("\n")
                                      );

                                      allDays.push(dayFormat);
                                      dayFormat = dayFormatOriginal;
                                    }
                                  }

                                  // replace %wochenvorschau% with all days
                                  mask = mask.replace(
                                    new RegExp("%wochenvorschau%", "g"),
                                    allDays.join("\n\n")
                                  );

                                  // replace all occurences of %nn% in mask.md with numbers counting up from j
                                  while (mask.indexOf("%nn%") != -1) {
                                    mask = mask.replace(
                                      new RegExp("%nn%", "m"),
                                      j
                                    );
                                    j++;
                                  }

                                  // seed the random number generator with the date
                                  Math.seedrandom(data["date"]);

                                  // replace %awarenessfrage% and %zusatzfrage% with a random question
                                  mask = mask.replace(
                                    new RegExp("%awarenessfrage%", "g"),
                                    generateQuestion()
                                  );
                                  mask = mask.replace(
                                    new RegExp("%zusatzfrage%", "g"),
                                    generateQuestion()
                                  );

                                  // replace &quot; with "
                                  mask = mask.replace(
                                    new RegExp("&quot;", "g"),
                                    '"'
                                  );

                                  // replace &amp; with &
                                  mask = mask.replace(
                                    new RegExp("&amp;", "g"),
                                    "&"
                                  );

                                  // replace &lt; with <
                                  mask = mask.replace(
                                    new RegExp("&lt;", "g"),
                                    "<"
                                  );

                                  // replace &gt; with >
                                  mask = mask.replace(
                                    new RegExp("&gt;", "g"),
                                    ">"
                                  );

                                  // replace [book-list:...] with link to book-list
                                  mask = mask.replace(
                                    new RegExp("\\[book-list:(.*)\\]", "g"),
                                    "[book-list](https://www.politischdekoriert.de/book-list?dir=$1)"
                                  );

                                  // replace [book-list:single:<type>|<title>] with link to book-list
                                  mask = mask.replace(
                                    new RegExp(
                                      "\\[book-list:single:(.*)\\|(.*?)\\]",
                                      "g"
                                    ),
                                    "$2"
                                  );

                                  // replace \r\n not followed by ' ' or '\' with \r\n\\
                                  // if in safari
                                  if (
                                    navigator.userAgent.indexOf("Safari") != -1
                                  ) {
                                    // replace \r\n\r\n with \r\r\r\r
                                    mask = mask.replace(
                                      new RegExp("\r\n\r\n", "g"),
                                      "\r\r\r\r"
                                    );

                                    // replace \r\n with \\r\n if not followed by ' ' or '\' (do not remove the character after \r\n)
                                    mask = mask.replace(
                                      new RegExp("\r\n(?![\\ *]|[0-9].)", "g"),
                                      "\\\r\n"
                                    );

                                    // replace \r\r\r\r with \r\n\r\n
                                    mask = mask.replace(
                                      new RegExp("\r\r\r\r", "g"),
                                      "\r\n\r\n"
                                    );
                                  } else {
                                    mask = mask.replace(
                                      new RegExp(
                                        "(?<!\r\n)\r\n(?![\\ *]|[0-9].)",
                                        "g"
                                      ),
                                      "\\\r\n"
                                    );
                                  }

                                  return mask;
                                })
                                .then(function (mask) {
                                  if (cid != "") {
                                    process(
                                      mask,
                                      data["date"].replace(
                                        new RegExp("-", "g"),
                                        "_"
                                      ),
                                      cid,
                                      extern
                                    );
                                  } else {
                                    // replace - with _ in date
                                    process(
                                      mask,
                                      data["date"].replace(
                                        new RegExp("-", "g"),
                                        "_"
                                      ),
                                      dir2,
                                      extern
                                    );
                                  }
                                });
                            });
                        });
                    });
                });
            });
        });
    });
}

function download(markdown, filename, cid, extern = "") {
  // add .md
  filename = filename + ".md";

  // when cid is a number
  if (cid != "" && !isNaN(cid)) {
    // go to Actions/botDownload.php
    // put arguments in a form
    var form = new FormData();
    form.append("markdown", markdown);
    form.append("filename", filename);
    form.append("chatid", cid);

    // post by going to Actions/botDownload.php
    fetch(extern + "Actions/botDownload.php", {
      method: "POST",
      body: form,
    }).then(function (response) {
      console.log(response);
    });
  } else {
    // download the rendered markdown as a .md file
    var element = document.createElement("a");
    element.setAttribute(
      "href",
      "data:text/plain;charset=utf-8," + encodeURIComponent(markdown)
    );
    element.setAttribute("download", filename);

    element.style.display = "none";
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
  }
}

function upload(markdown, filename, dir, extern = "") {
  // prevent overwriting and add .md
  filename = filename + "-Tagesordnung.md";

  // load webdavuser.config from the same directory as this script
  fetch(extern + "webdavuser.config")
    .then(function (response) {
      return response.json();
    })
    .then(function (webdavuser) {
      // load Bot/chats.json
      fetch(extern + "Bot/chats.json")
        .then(function (response) {
          return response.json();
        })
        .then(function (chats) {
          //find chat where name is dir
          for (var i = 0; i < chats["groups"].length; i++) {
            if (chats["groups"][i]["name"] == dir) {
              dir = chats["groups"][i]["dir"];
              break;
            }
          }

          // go to webdavUpload.php and post username and password
          // put arguments in a form
          var form = new FormData();
          form.append("user", webdavuser["user"]);
          form.append("password", webdavuser["password"]);
          form.append("dir", dir);
          form.append("filename", filename);
          form.append("markdown", markdown);

          // post by going to webdavUpload.php
          fetch(extern + "Actions/webdavUpload.php", {
            method: "POST",
            body: form,
          });
        });
    });
}

// get the weekday of a date
function getWeekday(date) {
  var weekdays = [
    "Sonntag",
    "Montag",
    "Dienstag",
    "Mittwoch",
    "Donnerstag",
    "Freitag",
    "Samstag",
  ];
  return weekdays[new Date(date).getDay()];
}

// get the date in the format dd.mm.
function getDate(date) {
  var day = new Date(date).getDate();
  var month = new Date(date).getMonth() + 1;
  return day + "." + month + ".";
}

function generateQuestion() {
  switch (Math.floor(Math.random() * 4)) {
    case 0:
      var question = "Was ist dein(e)";
      question += Math.floor(Math.random() * 2) == 0 ? " lieblings" : " hass";
      var things = [
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
      question += " " + things[Math.floor(Math.random() * things.length)] + "?";
      break;
    case 1:
      var question = "Was ist deine Meinung zu ";
      var things = [
        "Clowns",
        "Oliver Pocher",
        "Holzöfen",
        "England",
        "Kaffee",
        "Vögeln",
      ];
      question += things[Math.floor(Math.random() * things.length)] + "?";
      break;
    case 2:
      var question = "Welche(s/r) ";
      var things = [
        "Wort",
        "Konzept",
        "Mensch",
        "Sache",
        "Erfindung",
        "Spruch",
      ];
      question +=
        things[Math.floor(Math.random() * things.length)] +
        " kommt dir gerade in den Sinn, und warum?";
      break;
    case 3:
      var comparables = [
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
      var question =
        "Was ist besser: " +
        comparables[Math.floor(Math.random() * comparables.length)] +
        " oder " +
        comparables[Math.floor(Math.random() * comparables.length)] +
        "?";
      break;
  }
  return question;
}
