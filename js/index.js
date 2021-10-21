/*global $, dotclear */
'use strict';

$(function () {
  $('#part').on('change', function () {
    this.form.submit();
  });
});