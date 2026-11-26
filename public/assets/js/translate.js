// Multilingual support via the Google Translate website widget.
// Called by https://translate.google.com/translate_a/element.js once loaded.
function googleTranslateElementInit() {
  if (!document.getElementById("google_translate_element")) return;
  new google.translate.TranslateElement(
    {
      pageLanguage: "en",          // all Google-supported languages are offered
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
    },
    "google_translate_element"
  );
}
