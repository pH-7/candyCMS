function show(sDivId) {
  $(sDivId).slideDown();

  if($('#js-flash_success') || $('#js-flash_error')) {
    hide(sDivId, 10000);
  }
}

/* Hide div */
function hide(sDivId, iDelay) {
  $(sDivId).delay(iDelay).slideUp();
}

/* ToggleOpacity */
$.fn.toggleOpacity = function (t) {
  if(t)
    this.stop(true,true).animate({
      opacity:1
    });
  else
    this.stop(true,true).animate({
      opacity:0.25
    });
}

/* Quote comment */
function quote(sName, sDivId) {
  var oTextField = $('#js-create_commment_text');
  var sOldMessage = oTextField.val();
  var sQuote = $('#' + sDivId).html();
  var sNewMessage = "[quote=" + sName + "]" + sQuote + "[/quote]\n";
  oTextField.val(sOldMessage + sNewMessage);
  if ($.mobile)
    $.mobile.silentScroll(oTextField.offset().top);
  return false;
}

function stripNoAlphaChars(sValue) {
  sValue = sValue.replace(/ /g, "_");
  sValue = sValue.replace(/Ä/g, "Ae");
  sValue = sValue.replace(/ä/g, "ae");
  sValue = sValue.replace(/Ö/g, "Oe");
  sValue = sValue.replace(/ö/g, "oe");
  sValue = sValue.replace(/Ü/g, "Ue");
  sValue = sValue.replace(/ü/g, "ue");
  sValue = sValue.replace(/ß/g, "ss");
  sValue = sValue.replace(/\W/g, "_");
  return sValue;
}

function confirmDestroy(sUrl) {
  if( confirm(lang.confirm_destroy) )
    parent.location.href = sUrl;
}

function countCharLength(sDiv, iLen) {
  var iLength = iLen - $(sDiv).val().length;
  $(sDiv).next().html(iLength);
}

/* Show success and error messages */
if($('#js-flash_success') || $('#js-flash_error')) {
  show('#js-flash_message');
}

$('#js-flash_success').click(function() {
  hide(this, 0);
});

$('#js-flash_error').click(function() {
  hide(this, 0);
});

/* Show tooltips */
$('.js-tooltip').tooltip();
$('p.error').tooltip();