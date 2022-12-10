resize();

// when the window is thin enough, hide the menu
window.addEventListener('resize', function () {
    resize();
});

// when any of the .addtopbutton buttons are clicked, toggle the .addtopmenu .hidden class
document.querySelectorAll('.addtopbutton').forEach(function (button) {
  button.addEventListener('click', function () {
    document.querySelector('.addtopmenu').classList.toggle('hidden');

    // cahnge the text of the submit button to "Add"
    document.querySelector('.submitbutton').value = "Add";
    // hide the delete button
    document.querySelector('.deletebutton').classList.add('hidden');

    //unhide #pfield
    document.querySelector('#pfield').classList.remove('hidden');
  });
});

// when cancelbutton is clicked, add .hidden class to .addtopmenu + addtomenu 
document.querySelectorAll('.cancelbutton').forEach(function (button) {
    button.addEventListener('click', function () {
        document.querySelector('.addtopmenu').classList.add('hidden');
        document.querySelector('.addtomenu').classList.add('hidden');
    });
});

// when any .editbutton is clicked, toggle the .addtopmenu .hidden class
document.querySelectorAll('.editbutton').forEach(function (button) {
  button.addEventListener('click', function () {
    // is the edit button in the headerbuttons?
    if (button.parentElement.id == "headerbuttons") {
        // if so, toggle the .addtomenu .hidden class
        document.querySelector('.addtomenu').classList.toggle('hidden');
        // set the edit field of the form to true
        document.querySelectorAll('#editfield')[1].value = "true";
        // set title and date fields to the values of the .editbuttons toptitle and topdate fields
        // #titlefield (text input) and #datefield (date input)
        document.querySelectorAll('#titlefield')[1].value = button.getAttribute('toptitle');
        document.querySelectorAll('#datefield')[0].value = button.getAttribute('topdate');

        // change the text of the submit button to "Edit"
        document.querySelectorAll('.submitbutton')[1].value = "Edit";
        // unhide the delete button
        document.querySelectorAll('.deletebutton')[1].classList.remove('hidden');
    } else {
        // if not, toggle the .addtopmenu .hidden class
        document.querySelector('.addtopmenu').classList.toggle('hidden');
        // set the edit field of the form to the value of the .editbuttons topid field
        document.querySelectorAll('#editfield')[0].value = button.getAttribute('topid');
        // set title and content fields to the values of the .editbuttons toptitle and topcontent fields
        // #titlefield (text input) and #contentfield (textarea)
        document.querySelectorAll('#titlefield')[0].value = button.getAttribute('toptitle');
        document.querySelectorAll('#contentfield')[0].value = button.getAttribute('topcontent');

        // change the text of the submit button to "Edit"
        document.querySelectorAll('.submitbutton')[0].value = "Save";
        // unhide the delete button
        document.querySelectorAll('.deletebutton')[0].classList.remove('hidden');

        // if toppermanent is true, set the permanentfield to true
        document.querySelectorAll('#permanentfield')[0].checked = button.getAttribute('toppermanent') == "true";

        // hide #pfield
        document.querySelectorAll('#pfield')[0].classList.add('hidden');
    }
  });
});

// when .deletebutton is clicked set the delete fields of the forms to true and simulate a submit
document.querySelectorAll('.deletebutton').forEach(function (button) {
    button.addEventListener('click', function () {
        if (button.parentElement.parentElement.id == "addtoform") {
            console.log(button.parentElement.parentElement.id);
            document.querySelectorAll('#deletefield')[1].value = "true";
            document.querySelectorAll('.submitbutton')[1].click();
        } else {
            document.querySelectorAll('#deletefield')[0].value = "true";
            document.querySelectorAll('.submitbutton')[0].click();
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

// upon pressing #downloadbutton, download the TO using the renderMarkDown function
document.querySelector('#downloadbutton').addEventListener('click', function () {
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
            // get the json form the second directory
            var dir2 = dir.split('/')[0];
            fetch('TOs/' + dir2 + '/permanent.json').then(
                function (response) {
                    return response.json();
                }
            ).then(
                function (permanent) {
                    // load Markdown/top-format.md
                    fetch('Markdown/top-format.md').then(
                        function (response) {
                            return response.text();
                        }
                    ).then(
                        function (topFormat) {
                            var topFormatOriginal = topFormat;
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
                                                    mask = mask.replace(new RegExp('%tops%', 'g'), allTops.join('\n\n'));
                                                } else {
                                                    // replace the %key% in mask.md with the value of the key
                                                    mask = mask.replace(new RegExp('%' + key + '%', 'g'), data[key]);
                                                }
                                            }

                                            var p = 1;
                                            var allPermTops = ["\n\n### %nn%. Laufende Arbeitsaufträge"];
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
                                                mask = mask.replace(new RegExp('%permanent%', 'g'), allPermTops.join('\n\n'));
                                            } else {
                                                mask = mask.replace(new RegExp('%permanent%', 'g'), "");
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
