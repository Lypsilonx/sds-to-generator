function resize() {
  if (window.innerWidth < 800) {
    document.querySelector("body").classList.add("hovermenu");
  } else {
    document.querySelector("body").classList.remove("hovermenu");
  }
}

resize();

// go back to scroll position when page is reloaded
window.addEventListener("beforeunload", function (event) {
  // get #main's scroll position and save it to sessionStorage
  sessionStorage.setItem("scroll", document.querySelector("#main").scrollTop);

  // get dir from url and save it to sessionStorage
  var args = window.location.href.split("?")[1];
  var dir = args.match(/dir=([^&#]*)/)[1];

  sessionStorage.setItem("scrollDir", dir);
});

window.addEventListener("DOMContentLoaded", function () {
  // set the scroll position to the stored value in sessionStorage if the dir is the same
  var args = window.location.href.split("?")[1];
  var dir = args.match(/dir=([^&#]*)/)[1];
  var scrollDir = sessionStorage.getItem("scrollDir");
  if (dir == scrollDir) {
    var scrollPosition = sessionStorage.getItem("scroll");
    if (scrollPosition) {
      document.querySelector("#main").scrollTop = scrollPosition;
    }
  }
});

// when the window is thin enough, hide the menu
window.addEventListener("resize", function () {
  resize();
});

// Change share icon on Apple devices
if (navigator.platform.match(/(Mac|iPhone|iPod|iPad)/i)) {
  for (var i = 0; i < document.querySelectorAll(".shareb").length; i++) {
    document
      .querySelectorAll(".shareb")
      [i].querySelector(".material-symbols-outlined").innerText = "ios_share";
  }
}

// when any of the .addtopb buttons are clicked, toggle the .addtop .hidden class
document.querySelectorAll(".addtopb").forEach(function (button) {
  button.addEventListener("click", function () {
    var form = document.querySelector(".addtop");

    form.classList.toggle("hidden");

    // change the text of the submit button to 'Add'
    form.querySelector(".submitbutton").value = "Add";
    // hide the delete button
    form.querySelector(".deletebutton").classList.add("hidden");

    //unhide #pfield
    form.querySelector("#pfield").classList.remove("hidden");
  });
});

// when the .addeevent buttons is clicked, toggle the .addevmnt .hidden class
document.querySelectorAll(".addeventb").forEach(function (button) {
  button.addEventListener("click", function () {
    var form = document.querySelector(".addevent");

    form.classList.toggle("hidden");

    // change the text of the submit button to 'Add'
    form.querySelector(".submitbutton").value = "Add";
    // hide the delete button
    form.querySelector(".deletebutton").classList.add("hidden");
  });
});

// when cancelbutton is clicked, add .hidden class to .addtopmenu + addtomenu
document
  .querySelectorAll(".cancelbutton, body > .menu")
  .forEach(function (button) {
    button.addEventListener("click", function (e) {
      if (e.target !== this) return;
      document.querySelectorAll(".menu").forEach(function (menu) {
        menu.classList.add("hidden");
      });
    });
  });

// when any .editbutton is clicked, toggle the .addtopmenu .hidden class
document.querySelectorAll(".editbutton").forEach(function (button) {
  button.addEventListener("click", function () {
    // is the edit button in the headerbuttons?
    if (button.parentElement.id == "headerbuttons") {
      var form = document.querySelector(".addto");

      form.querySelector("h2").innerText = "TO bearbeiten";

      form.classList.toggle("hidden");
      // set the edit field of the form to true
      form.querySelector("#editfield").value = "true";
      // set title and date fields to the values of the .editbuttons toptitle and topdate fields
      // #titlefield (text input) and #datefield (date input)
      form.querySelector("#titlefield").value = button.getAttribute("toptitle");
      form.querySelector("#datefield").value = button.getAttribute("topdate");

      // change the text of the submit button to 'Save'
      form.querySelector(".submitbutton").value = "Save";
      // unhide the delete button
      form.querySelector(".deletebutton").classList.remove("hidden");
    } else if (button.classList.contains("event")) {
      var form = document.querySelector(".addevent");

      form.querySelector("h2").innerText = "Event bearbeiten";

      form.classList.toggle("hidden");
      // set the edit field of the form to the value of the .editbuttons topid field
      form.querySelector("#editfield").value = button.getAttribute("eventid");
      // set title and date fields to the values of the .editbuttons toptitle and topdate fields
      // #titlefield (text input) and #datefield (date input)
      form.querySelector("#titlefield").value =
        button.getAttribute("eventtitle");
      form.querySelector("#datefield").value = button.getAttribute("eventdate");
      form.querySelector("#contentfield").value =
        button.getAttribute("eventcontent");

      // change the text of the submit button to 'Save'
      form.querySelector(".submitbutton").value = "Save";
      // unhide the delete button
      form.querySelector(".deletebutton").classList.remove("hidden");
    } else {
      var form = document.querySelector(".addtop");

      form.querySelector("h2").innerText = "TOP bearbeiten";

      form.classList.toggle("hidden");
      // set the edit field of the form to the value of the .editbuttons topid field
      form.querySelector("#editfield").value = button.getAttribute("topid");
      // set title and content fields to the values of the .editbuttons toptitle and topcontent fields
      // #titlefield (text input) and #contentfield (textarea)
      form.querySelector("#titlefield").value = button.getAttribute("toptitle");
      form.querySelector("#contentfield").value =
        button.getAttribute("topcontent");

      // change the text of the submit button to 'Save'
      form.querySelector(".submitbutton").value = "Save";
      // unhide the delete button
      form.querySelector(".deletebutton").classList.remove("hidden");

      // if toppermanent is true, set the permanentfield to true
      form.querySelector("#permanentfield").checked =
        button.getAttribute("toppermanent") == "true";

      // hide #pfield
      form.querySelector("#pfield").classList.add("hidden");
    }
  });
});

// when .deletebutton is clicked set the delete fields of the forms to true and submit the form
document.querySelectorAll(".deletebutton").forEach(function (button) {
  button.addEventListener("click", function () {
    if (
      button.parentElement.parentElement.parentElement.classList.contains(
        "addto"
      )
    ) {
      var form = document.querySelector(".addto");
      form.querySelector("#deletefield").value = "true";
      form.children[0].submit();
    } else if (
      button.parentElement.parentElement.parentElement.classList.contains(
        "addevent"
      )
    ) {
      var form = document.querySelector(".addevent");
      form.querySelector("#deletefield").value = "true";
      form.children[0].submit();
    } else {
      var form = document.querySelector(".addtop");
      form.querySelector("#deletefield").value = "true";
      form.children[0].submit();
    }
  });
});

// when .searchbutton is clicked set the dir in the url to the contents of the search field and go to that url
document
  .querySelector(".searchbutton")
  .addEventListener("click", function (event) {
    var search = document.querySelector("#searchfield").value;

    window.location.href =
      window.location.href.split("?")[0] + "?dir=" + search;
  });

// when .searchfield is focused and enter is pressed, set the dir in the url to the contents of the search field and go to that url
document
  .querySelector("#searchfield")
  .addEventListener("keyup", function (event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      document.querySelector(".searchbutton").click();
    }
  });

// upon pressing #menubutton, toggle body .hidemenu
document.querySelector("#menubutton").addEventListener("click", function () {
  document.querySelector("body").classList.toggle("hidemenu");

  // keep the menu button from being clicked again until the menu is hidden
  document.querySelector("#menubutton").disabled = true;
  // untrigger the :hover state by removing the :hover class from it
  document.querySelector("#menubutton").classList.remove("hover");
  // reenable the button after 500ms
  setTimeout(function () {
    document.querySelector("#menubutton").disabled = false;
  }, 500);
});

document.querySelectorAll(".downloadb").forEach(function (button) {
  button.addEventListener("click", function () {
    // cahnge icon to tick for 3 seconds
    document.querySelectorAll(".downloadb").forEach(function (button) {
      button.querySelector(".material-symbols-outlined").innerText = "check";
      setTimeout(function () {
        button.querySelector(".material-symbols-outlined").innerText =
          "file_download";
      }, 3000);
    });
  });
});

document.querySelectorAll(".uploadb").forEach(function (button) {
  // remove the href
  button.removeAttribute("href");

  button.addEventListener("click", function () {
    var args = window.location.href.split("?")[1];
    // use regex to get the dir from the url
    var dir = args.match(/dir=([^&#]*)/)[1];

    fetch("Actions/uploadto.php?dir=" + dir, {
      method: "POST",
    });

    // cahnge icon to tick for 3 seconds
    document.querySelectorAll(".uploadb").forEach(function (button) {
      button.querySelector(".material-symbols-outlined").innerText = "check";
      setTimeout(function () {
        button.querySelector(".material-symbols-outlined").innerText =
          "cloud_upload";
      }, 3000);
    });
  });
});

// upon pressing .shareb, share a link to the TO
document.querySelectorAll(".shareb").forEach(function (button) {
  button.addEventListener("click", function () {
    var dir = window.location.href.split("?")[1].split("=")[1];
    var url = window.location.href.split("?")[0] + "?dir=" + dir;

    if (navigator.share) {
      navigator.share({
        title: "TO " + dir,
        text: "TO " + dir,
        url: url,
      });
    }
  });
});

function autocomplete(inp, arr) {
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function (e) {
    var a,
      b,
      i,
      val = this.value;
    /*close any already open lists of autocompleted values*/
    closeAllLists();
    if (!val) {
      return false;
    }
    currentFocus = -1;
    /*create a DIV element that will contain the items (values):*/
    a = document.createElement("DIV");
    a.setAttribute("id", this.id + "autocomplete-list");
    a.setAttribute("class", "autocomplete-items");
    /*append the DIV element as a child of the autocomplete container:*/
    this.parentNode.appendChild(a);
    /*for each item in the array...*/
    for (i = 0; i < arr.length; i++) {
      /*check if the item starts with the same letters as the text field value:*/
      if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
        /*create a DIV element for each matching element:*/
        b = document.createElement("DIV");
        /*make the matching letters bold:*/
        b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
        b.innerHTML += arr[i].substr(val.length);
        /*insert a input field that will hold the current array item's value:*/
        b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
        /*execute a function when someone clicks on the item value (DIV element):*/
        b.addEventListener("click", function (e) {
          /*insert the value for the autocomplete text field:*/
          inp.value = this.getElementsByTagName("input")[0].value;
          /*close the list of autocompleted values,
              (or any other open lists of autocompleted values:*/
          closeAllLists();
        });
        a.appendChild(b);
      }
    }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function (e) {
    var x = document.getElementById(this.id + "autocomplete-list");
    if (x) x = x.getElementsByTagName("div");
    if (e.keyCode == 40) {
      /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
      currentFocus++;
      /*and and make the current item more visible:*/
      addActive(x);
    } else if (e.keyCode == 38) {
      //up
      /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
      currentFocus--;
      /*and and make the current item more visible:*/
      addActive(x);
    } else if (e.keyCode == 13) {
      /*If the ENTER key is pressed, prevent the form from being submitted,*/
      e.preventDefault();
      if (currentFocus > -1) {
        /*and simulate a click on the "active" item:*/
        if (x) x[currentFocus].click();
      }
    }

    if (currentFocus > -1) {
      x[currentFocus].scrollIntoView({
        behavior: "smooth",
        block: "nearest",
        inline: "start",
      });
    }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = x.length - 1;
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
        x[i].parentNode.removeChild(x[i]);
      }
    }
  }
  /*execute a function when someone clicks in the document:*/
  document.addEventListener("click", function (e) {
    closeAllLists(e.target);
  });
}

var tos = [];
// Actions/gettos.php sends a json array of all the tos
var url = "Actions/gettos.php";
var xhr = new XMLHttpRequest();
xhr.open("GET", url, true);
xhr.responseType = "json";
xhr.onload = function () {
  var status = xhr.status;
  if (status === 200) {
    tos = xhr.response;
    autocomplete(document.querySelector("#searchfield"), tos);
  } else {
    console.log("Error getting tos");
  }
};
xhr.send();
