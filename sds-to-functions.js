function resize() {
    if (window.innerWidth < 800) {
        document.querySelector('body').classList.add('hidemenu');
    } else {
        document.querySelector('body').classList.remove('hidemenu');
    }
}

function renderMarkDown(dir, process = () => { }) {
    // get the JSON from the directory and render it as markdown
    fetch('TOs/' + dir + '_to.json').then(
        function (response) {
            return response.json();
        }
    ).then(
        function (data) {
            // get the perms from the directory
            var dir2 = dir.split('/')[0];
            fetch('TOs/' + dir2 + '/permanent.json').then(
                function (response) {
                    return response.json();
                }
            ).then(
                function (permanent) {
                    // get the events from the directory
                    var dir2 = dir.split('/')[0];
                    fetch('TOs/' + dir2 + '/events.json').then(
                        function (response) {
                            return response.json();
                        }
                    ).then(
                        function (events) {
                            // load Markdown/top-format.md
                            fetch('Markdown/top-format.md').then(
                                function (response) {
                                    return response.text();
                                }
                            ).then(
                                function (topFormat) {
                                    var topFormatOriginal = topFormat;
                                    // load Markdown/day-format.md
                                    fetch('Markdown/day-format.md').then(
                                        function (response) {
                                            return response.text();
                                        }
                                    ).then(
                                        function (dayFormat) {
                                            var dayFormatOriginal = dayFormat;
                                            // load Markdown/event-format.md
                                            fetch('Markdown/event-format.md').then(
                                                function (response) {
                                                    return response.text();
                                                }
                                            ).then(
                                                function (eventFormat) {
                                                    var eventFormatOriginal = eventFormat;
                                                    // load Markdown/perm-format.md
                                                    fetch('Markdown/perm-format.md').then(
                                                        function (response) {
                                                            return response.text();
                                                        }
                                                    ).then(
                                                        function (permFormat) {
                                                            var permFormatOriginal = permFormat;
                                                            // Format the date as DD.MM.YYYY
                                                            var fdate = new Date(data["date"]);
                                                            var day = fdate.getDate();
                                                            var month = fdate.getMonth() + 1;
                                                            var year = fdate.getFullYear();
                                                            if (day < 10) {
                                                                day = '0' + day;
                                                            }
                                                            if (month < 10) {
                                                                month = '0' + month;
                                                            }
                                                            fdate = day + '.' + month + '.' + year;
                                                            // load Markdown/mask.md
                                                            fetch('Markdown/mask.md').then(
                                                                function (response) {
                                                                    return response.text();
                                                                }
                                                            ).then(
                                                                function (mask) {
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
                                                                                    topFormat = topFormat.replace(new RegExp('%' + key3 + '%', 'g'), data[key][key2][key3]);
                                                                                }
                                                                                allTops.push(topFormat);
                                                                                topFormat = topFormatOriginal;
                                                                            }
                                                                            // replace the %tops% in mask.md with the value of topFormat
                                                                            mask = mask.replace(new RegExp('%tops%', 'g'), "\n\n\n" + allTops.join('\n\n\n'));
                                                                        } else if (key == "date") {
                                                                            mask = mask.replace(new RegExp('%date%', 'g'), fdate);
                                                                        } else {
                                                                            // replace the %key% in mask.md with the value of the key
                                                                            mask = mask.replace(new RegExp('%' + key + '%', 'g'), data[key]);
                                                                        }
                                                                    }

                                                                    var p = 1;
                                                                    var allPermTops = ["\n\n\n### %nn%. Laufende Arbeitsaufträge"];
                                                                    var perm = false;
                                                                    // for each top in permanent.json
                                                                    for (var key2 in permanent["tops"]) {
                                                                        perm = true
                                                                        for (var key3 in permanent["tops"][key2]) {
                                                                            // replace the %key% in top-format.md with the value of the key
                                                                            permFormat = permFormat.replace(new RegExp('%' + key3 + '%', 'g'), permanent["tops"][key2][key3]);
                                                                        }
                                                                        // replace the %num% in top-format.md with the value of 
                                                                        permFormat = permFormat.replace(new RegExp('%num%', 'g'), p);
                                                                        p++;
                                                                        allPermTops.push(permFormat);
                                                                        permFormat = permFormatOriginal;
                                                                    }
                                                                    if (perm) {
                                                                        // replace the %permanent% in mask.md with the value of topFormat
                                                                        mask = mask.replace(new RegExp('%permanent%', 'g'), allPermTops.join('\n\n\n'));
                                                                    } else {
                                                                        mask = mask.replace(new RegExp('%permanent%', 'g'), "");
                                                                    }

                                                                    var wrb = [];
                                                                    var wvs = [];
                                                                    // for each day in events.json
                                                                    for (var key2 in events["events"]) {
                                                                        var date = new Date(data["date"]);
                                                                        var date2 = new Date(events["events"][key2]["date"]);
                                                                        // is the day within the last 7 days of data["date"] and not on the same day?
                                                                        if (date.getTime() - date2.getTime() < 604800000 && date.getTime() - date2.getTime() > 0) {
                                                                            wrb.push(events["events"][key2]);
                                                                        }
                                                                        // is the day within the next 7 days of data["date"] or on the same day?
                                                                        if (date2.getTime() - date.getTime() < 604800000 && date2.getTime() - date.getTime() >= 0) {
                                                                            wvs.push(events["events"][key2]);
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
                                                                            dayFormat = dayFormat.replace(new RegExp('%day%', 'g'), getWeekday(day));

                                                                            // replace %date% with the date
                                                                            dayFormat = dayFormat.replace(new RegExp('%date%', 'g'), getDate(day));

                                                                            var allEvents = [];
                                                                            for (var key2 in wrb) {
                                                                                if (wrb[key2]["date"] == day) {
                                                                                    eventFormat = eventFormat.replace(new RegExp('%title%', 'g'), wrb[key2]["title"]);
                                                                                    eventFormat = eventFormat.replace(new RegExp('%content%', 'g'), wrb[key2]["content"]);
                                                                                    allEvents.push(eventFormat);
                                                                                    eventFormat = eventFormatOriginal;
                                                                                }
                                                                            }

                                                                            // replace %events% with all events of the day
                                                                            dayFormat = dayFormat.replace(new RegExp('%events%', 'g'), allEvents.join('\n'));

                                                                            allDays.push(dayFormat);
                                                                            dayFormat = dayFormatOriginal;
                                                                        }
                                                                    }

                                                                    // replace %wochenrueckblick% with all days
                                                                    mask = mask.replace(new RegExp('%wochenrueckblick%', 'g'), allDays.join('\n\n'));


                                                                    // for every different day in wvs add a dayFormat to allDays
                                                                    var allDays = [];
                                                                    var lastDay = "";
                                                                    for (var key2 in wvs) {
                                                                        var day = wvs[key2]["date"];

                                                                        // if the day is not the same as the last day
                                                                        if (day != lastDay) {
                                                                            lastDay = day;
                                                                            // replace %day% with the weekday (in german)
                                                                            dayFormat = dayFormat.replace(new RegExp('%day%', 'g'), getWeekday(day));

                                                                            // replace %date% with the date
                                                                            dayFormat = dayFormat.replace(new RegExp('%date%', 'g'), getDate(day));

                                                                            var allEvents = [];
                                                                            for (var key2 in wvs) {
                                                                                if (wvs[key2]["date"] == day) {
                                                                                    eventFormat = eventFormat.replace(new RegExp('%title%', 'g'), wvs[key2]["title"]);
                                                                                    eventFormat = eventFormat.replace(new RegExp('%content%', 'g'), wvs[key2]["content"]);
                                                                                    allEvents.push(eventFormat);
                                                                                    eventFormat = eventFormatOriginal;
                                                                                }
                                                                            }

                                                                            // replace %events% with all events of the day
                                                                            dayFormat = dayFormat.replace(new RegExp('%events%', 'g'), allEvents.join('\n'));

                                                                            allDays.push(dayFormat);
                                                                            dayFormat = dayFormatOriginal;
                                                                        }
                                                                    }

                                                                    // replace %wochenvorschau% with all days
                                                                    mask = mask.replace(new RegExp('%wochenvorschau%', 'g'), allDays.join('\n\n'));
                                                                    
                                                                    // replace all occurences of %nn% in mask.md with numbers counting up from j
                                                                    while (mask.indexOf('%nn%') != -1) {
                                                                        mask = mask.replace(new RegExp('%nn%', 'm'), j);
                                                                        j++;
                                                                    }

                                                                    // seed the random number generator with the date
                                                                    Math.seedrandom(data["date"]);

                                                                    // replace %awarenessfrage% and %zusatzfrage% with a random question
                                                                    mask = mask.replace(new RegExp('%awarenessfrage%', 'g'), generateQuestion());
                                                                    mask = mask.replace(new RegExp('%zusatzfrage%', 'g'), generateQuestion());

                                                                    return mask;
                                                                }
                                                            ).then(
                                                                function (mask) {
                                                                    process(mask, dir.replace("_to", "").replace("/"," ") + " " + fdate + ".md");
                                                                }
                                                            );
                                                        }
                                                    );
                                                }
                                            );
                                        }
                                    );
                                }
                            );
                        }
                    );
                }
            );
        }
    );
}

function download(mask, filename) {
    // download the rendered markdown as a .md file
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(mask));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}

function upload(mask, filename) {
    // load webdavuser.config from the same directory as this script
    fetch('webdavuser.config').then(
        function (response) {
            return response.json();
        }
    ).then(
        function (webdavuser) {
            // upload the rendered markdown as a .md file
            // using webDAV endpoint "https://cloud.linke-sds.org/remote.php/dav/files/Lyx/"

            var request = new XMLHttpRequest();
            var url = 'https://cloud.linke-sds.org/remote.php/dav/files/' + webdavuser["user"] + '/Ortsgruppe ' + dir2 + '/' + filename;

            request.open('PUT', url, false, webdavuser["user"], webdavuser["password"]);
            request.setRequestHeader('Content-Type', 'text/plain', 'charset=utf-8');
            // ! NOT WORKING ! 
            request.setRequestHeader('Access-Control-Allow-Origin', '*');
            request.send(mask);
        }
    );
}

// get the weekday of a date
function getWeekday(date) {
    var weekdays = ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"];
    return weekdays[new Date(date).getDay()];
}

// get the date in the format dd.mm.
function getDate(date) {
    var day = new Date(date).getDate();
    var month = new Date(date).getMonth() + 1;
    return day + "." + month + ".";
}

function generateQuestion() {
    switch (Math.floor(Math.random() * 3)) {
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
                "Wissenschaftler*in"
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
                "Spruch"
            ];
            question += things[Math.floor(Math.random() * things.length)] + " kommt dir gerade in den Sinn, und warum?";
            break;
    }
    return question;
}