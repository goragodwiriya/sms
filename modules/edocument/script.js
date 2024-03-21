function initEdocumentView(id) {
  callClick(id, function(e) {
    if (confirm(trans("Downloading is a signed document"))) {
      var req = new GAjax({asynchronous: false});
      req.send(WEB_URL + "index.php/edocument/model/download", "id=" + id);
      var datas = req.responseText.toJSON();
      if (datas) {
        if (datas.alert) {
          alert(datas.alert);
        }
        if (datas.modal && datas.modal == "close") {
          if (modal) {
            modal.hide();
          }
        }
        if (datas.target && datas.target == 1) {
          this.set("target", "download");
        }
        if (datas.url) {
          this.href = datas.url;
        }
      } else if (req.responseText != "") {
        alert(req.responseText);
      }
    }
  });
}

function edocumentFileChanged() {
  var topic = $E('topic'),
    hs = /(.*)\.([a-z0-9]+)$/.exec(this.value);
  if (hs && ($E('id').value == 0 || topic.value == '')) {
    topic.value = hs[1];
  }
}
