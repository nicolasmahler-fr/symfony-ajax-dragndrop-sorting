// import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import "./styles/app.css";
require("jquery-ui/ui/widgets/droppable");
require("jquery-ui/ui/widgets/sortable");
require("jquery-ui/ui/widgets/selectable");
const $ = require("jquery");

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");

//Set position on drag'n'drop for item entity
// Dependencies : jquery, jquery ui, axios, GEDMO doctrine package
$("#js-sort").sortable({
  stop: function (event, ui) {
    let base_url = window.location.origin;
    let element_id = ui.item[0].dataset.id; //Get li data-id (= product id)
    let position = ui.item.index();
    let link =
      base_url + "/reorder-items?id=" + element_id + "&position=" + position; //See route in item controller
    $.ajax({
      type: "POST",
      url: link,
      data: {
        position: position,
      },
      success: function (result) {
        console.log(result);
      },
      error: function (error) {
        console.log(error);
      },
    });
  },
});
