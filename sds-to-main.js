function resize() {
  if (window.innerWidth < 800) {
    document.querySelector("body").classList.add("hovermenu");
  } else {
    document.querySelector("body").classList.remove("hovermenu");
  }
}

resize();

// when the window is thin enough, hide the menu
window.addEventListener("resize", function () {
  resize();
});

// Change share icon on Apple devices
if (navigator.platform.match(/(Mac|iPhone|iPod|iPad)/i)) {
  for (var i = 0; i < document.querySelectorAll(".shareb").length; i++) {
    document.querySelectorAll(".shareb")[i].querySelector("i").innerText =
      "ios_share";
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

      // if so, toggle the .addto .hidden class
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

      // if so, toggle the .addevent .hidden class
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
      // if not, toggle the .addtop .hidden class
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
    var args = window.location.href.split("?")[1];
    // use regex to get the dir from the url
    var dir = args.match(/dir=([^&#]*)/)[1];

    window.location.href = "Actions/downloadto.php?dir=" + dir;

    // cahnge icon to tick for 3 seconds
    document.querySelectorAll(".downloadb").forEach(function (button) {
      button.querySelector("i").innerText = "check";
      setTimeout(function () {
        button.querySelector("i").innerText = "file_download";
      }, 3000);
    });
  });
});

document.querySelectorAll(".uploadb").forEach(function (button) {
  button.addEventListener("click", function () {
    var args = window.location.href.split("?")[1];
    // use regex to get the dir from the url
    var dir = args.match(/dir=([^&#]*)/)[1];

    fetch("Actions/uploadto.php?dir=" + dir, {
      method: "POST",
    });

    // cahnge icon to tick for 3 seconds
    document.querySelectorAll(".uploadb").forEach(function (button) {
      button.querySelector("i").innerText = "check";
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
