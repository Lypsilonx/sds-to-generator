resize();

// when the window is thin enough, hide the menu
window.addEventListener('resize', function () {
    resize();
});

// when any of the .addtopb buttons are clicked, toggle the .addtop .hidden class
document.querySelectorAll('.addtopb').forEach(function (button) {
  button.addEventListener('click', function () {
    var form = document.querySelector('.addtop')
    
    form.classList.toggle('hidden');

    // change the text of the submit button to "Add"
    form.querySelector('.submitbutton').value = "Add";
    // hide the delete button
    form.querySelector('.deletebutton').classList.add('hidden');

    //unhide #pfield
    form.querySelector('#pfield').classList.remove('hidden');
  });
});

// when the .addeevent buttons is clicked, toggle the .addevmnt .hidden class
document.querySelector('.addeventb').addEventListener('click', function () {
    var form = document.querySelector('.addevent');

    form.classList.toggle('hidden');

    // change the text of the submit button to "Add"
    form.querySelector('.submitbutton').value = "Add";
    // hide the delete button
    form.querySelector('.deletebutton').classList.add('hidden');
});

// when cancelbutton is clicked, add .hidden class to .addtopmenu + addtomenu 
document.querySelectorAll('.cancelbutton').forEach(function (button) {
    button.addEventListener('click', function () {
        document.querySelectorAll('.menu').forEach(function (menu) {
            menu.classList.add('hidden');
        });
    });
});

// when any .editbutton is clicked, toggle the .addtopmenu .hidden class
document.querySelectorAll('.editbutton').forEach(function (button) {
  button.addEventListener('click', function () {
    // is the edit button in the headerbuttons?
    if (button.parentElement.id == "headerbuttons") {
        var form = document.querySelector('.addto')

        // if so, toggle the .addto .hidden class
        form.classList.toggle('hidden');
        // set the edit field of the form to true
        form.querySelector('#editfield').value = "true";
        // set title and date fields to the values of the .editbuttons toptitle and topdate fields
        // #titlefield (text input) and #datefield (date input)
        form.querySelector('#titlefield').value = button.getAttribute('toptitle');
        form.querySelector('#datefield').value = button.getAttribute('topdate');

        // change the text of the submit button to "Edit"
        form.querySelector('.submitbutton').value = "Edit";
        // unhide the delete button
        form.querySelector('.deletebutton').classList.remove('hidden');
    } else if (button.classList.contains('event')) {
        var form = document.querySelector('.addevent')

        // if so, toggle the .addevent .hidden class
        form.classList.toggle('hidden');
        // set the edit field of the form to the value of the .editbuttons topid field
        form.querySelector('#editfield').value = button.getAttribute('eventid');
        // set title and date fields to the values of the .editbuttons toptitle and topdate fields
        // #titlefield (text input) and #datefield (date input)
        form.querySelector('#titlefield').value = button.getAttribute('eventtitle');
        form.querySelector('#datefield').value = button.getAttribute('eventdate');
        form.querySelector('#contentfield').value = button.getAttribute('eventcontent');

        // change the text of the submit button to "Edit"
        form.querySelector('.submitbutton').value = "Save";
        // unhide the delete button
        form.querySelector('.deletebutton').classList.remove('hidden');
    } else {
        var form = document.querySelector('.addtop')
        // if not, toggle the .addtop .hidden class
        form.classList.toggle('hidden');
        // set the edit field of the form to the value of the .editbuttons topid field
        form.querySelector('#editfield').value = button.getAttribute('topid');
        // set title and content fields to the values of the .editbuttons toptitle and topcontent fields
        // #titlefield (text input) and #contentfield (textarea)
        form.querySelector('#titlefield').value = button.getAttribute('toptitle');
        form.querySelector('#contentfield').value = button.getAttribute('topcontent');

        // change the text of the submit button to "Edit"
        form.querySelector('.submitbutton').value = "Save";
        // unhide the delete button
        form.querySelector('.deletebutton').classList.remove('hidden');

        // if toppermanent is true, set the permanentfield to true
        form.querySelector('#permanentfield').checked = button.getAttribute('toppermanent') == "true";

        // hide #pfield
        form.querySelector('#pfield').classList.add('hidden');
    }
  });
});

// when .deletebutton is clicked set the delete fields of the forms to true and submit the form
document.querySelectorAll('.deletebutton').forEach(function (button) {
    button.addEventListener('click', function () {
        if (button.parentElement.parentElement.parentElement.classList.contains('addto')) {
            var form = document.querySelector('.addto');
            form.querySelector('#deletefield').value = "true";
            form.children[0].submit();
            
        } else if (button.parentElement.parentElement.parentElement.classList.contains('addevent')) {
            var form = document.querySelector('.addevent');
            form.querySelector('#deletefield').value = "true";
            form.children[0].submit();
        }
        else {
            var form = document.querySelector('.addtop');
            form.querySelector('#deletefield').value = "true";
            form.children[0].submit();
        }
    });
});

// when .searchbutton is clicked set the dir in the url to the contents of the search field and go to that url
document.querySelector('.searchbutton').addEventListener('click', function () {
    var search = document.querySelector('#searchfield').value;

    window.location.href = window.location.href.split('?')[0] + '?dir=' + search;
});

// when .searchfield is focused and enter is pressed, set the dir in the url to the contents of the search field and go to that url
document.querySelector('#searchfield').addEventListener('keyup', function (event) {
    if (event.keyCode === 13) {
        event.preventDefault();
        document.querySelector('.searchbutton').click();
    }
});

// upon pressing #menubutton, toggle body .hidemenu
document.querySelector('#menubutton').addEventListener('click', function () {
    document.querySelector('body').classList.toggle('hidemenu');
});

// upon pressing .downloadb, download the TO using the renderMarkDown function
document.querySelector('.downloadb').addEventListener('click', function () {
    var dir = window.location.href.split('?')[1].split('=')[1];
    renderMarkDown(dir);
});

function resize() {
    if (window.innerWidth < 800) {
        document.querySelector('body').classList.add('hidemenu');
    } else {
        document.querySelector('body').classList.remove('hidemenu');
    }
}

function renderMarkDown(dir) {
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
                                                                        if (date2.getTime() - date.getTime() < 604800000) {
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

                                                                        // replace %wochenrueckblick% with all days
                                                                        mask = mask.replace(new RegExp('%wochenrueckblick%', 'g'), allDays.join('\n\n'));
                                                                    }


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

                                                                        // replace %wochenrueckblick% with all days
                                                                        mask = mask.replace(new RegExp('%wochenvorschau%', 'g'), allDays.join('\n\n'));
                                                                    }
                                                                    
                                                                    // replace all occurences of %nn% in mask.md with numbers counting up from j
                                                                    while (mask.indexOf('%nn%') != -1) {
                                                                        mask = mask.replace(new RegExp('%nn%', 'm'), j);
                                                                        j++;
                                                                    }

                                                                    // replace %awarenessfrage% and %zusatzfrage% with a random question
                                                                    mask = mask.replace(new RegExp('%awarenessfrage%', 'g'), generateQuestion());
                                                                    mask = mask.replace(new RegExp('%zusatzfrage%', 'g'), generateQuestion());

                                                                    return mask;
                                                                }
                                                            ).then(
                                                                function (mask) {
                                                                    // download the rendered markdown as a .md file
                                                                    var element = document.createElement('a');
                                                                    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(mask));
                                                                    element.setAttribute('download', dir.replace("_to", "").replace("/"," ") + " " + data["date"] + ".md");

                                                                    element.style.display = 'none';
                                                                    document.body.appendChild(element);

                                                                    element.click();

                                                                    document.body.removeChild(element);

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
