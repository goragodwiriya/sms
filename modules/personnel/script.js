function initPerson(id) {
  var patt = /order_([0-9]+)/;
  forEach($G(id).elems("input"), function() {
    if (patt.test(this.id)) {
      $G(this).addEvent("change", function() {
        var hs = patt.exec(this.id);
        if (hs) {
          send("index.php/personnel/model/setup/action", "action=order&id=" + hs[1] + "&value=" + this.value, doFormSubmit);
        }
      });
    }
  });
}
