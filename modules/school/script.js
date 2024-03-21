function initSchoolImportStudent() {
  callClick("example", function() {
    var q = "module=school-export&type=student";
    forEach($G("setup_frm").elems("select"), function() {
      q += "&" + this.name + "=" + this.value;
    });
    this.href = WEB_URL + "export.php?" + q;
  });
}

function initSchoolImportgrade() {
  callClick("example", function() {
    if ($E("year").value.toInt() == 0) {
      alert(trans("Please fill in") + " " + $E("year").title);
      $G("year").highlight().focus();
      return false;
    } else {
      var q = "module=school-export&type=grade";
      q += "&course=" + $E("course").value;
      q += "&room=" + $E("room").value;
      q += "&year=" + $E("year").value;
      q += "&term=" + $E("term").value;
      this.href = WEB_URL + "export.php?" + q;
    }
  });
}

function initSchoolCourse() {
  var getQuery = function() {
    return "course_code=" + encodeURIComponent($E("course_code").value);
  };
  initAutoComplete(
    "course_code",
    WEB_URL + "index.php/school/model/autocomplete/findCourse",
    "course_code,course_name",
    "elearning", { get: getQuery }
  );
  initTeacher();
}

function initTeacher() {
  var doTeacher = function() {
    $E("term").disabled = this.value == 0;
    $E("year").disabled = this.value == 0;
  };
  $G("teacher_id").addEvent("change", doTeacher);
  doTeacher.call($E("teacher_id"));
}

function initSchoolImportcourse() {
  callClick("example", function() {
    if ($E("year").value.toInt() == 0) {
      alert(trans("Please fill in") + " " + $E("year").title);
      $G("year").highlight().focus();
      return false;
    } else {
      var q = "module=school-export&type=course";
      q += "&teacher_id=" + $E("teacher_id").value;
      q += "&year=" + $E("year").value;
      q += "&term=" + $E("term").value;
      q += "&typ=" + $E("typ").value;
      q += "&class=" + $E("class").value;
      this.href = WEB_URL + "export.php?" + q;
    }
  });
  initTeacher();
}
