// Shared dashboard behaviour: tab navigation and "Edit profile" toggle.
// Loaded with `defer`, so the DOM is ready when this runs.
(function () {
  "use strict";

  // --- Tabs ---------------------------------------------------------------
  const buttons = document.querySelectorAll(".dashboard-nav button[data-section]");
  const sections = document.querySelectorAll(".dashboard-section");

  function show(sectionId) {
    if (!document.getElementById(sectionId)) return;
    buttons.forEach((b) => b.classList.toggle("active", b.dataset.section === sectionId));
    sections.forEach((s) => s.classList.toggle("hidden-section", s.id !== sectionId));
    try { history.replaceState(null, "", "#" + sectionId); } catch (_) { /* ignore */ }
  }

  buttons.forEach((b) => b.addEventListener("click", () => show(b.dataset.section)));

  // Open the tab named in the URL hash (e.g. after a redirect), else the first one.
  const initial = (location.hash || "").slice(1);
  if (initial && document.getElementById(initial)) {
    show(initial);
  } else if (buttons.length) {
    show(buttons[0].dataset.section);
  }

  // --- Editable profile form ----------------------------------------------
  const editBtn = document.getElementById("editBtn");
  const saveBtn = document.getElementById("saveBtn");
  const cancelBtn = document.getElementById("cancelBtn");
  const editForm = document.getElementById("editForm");

  if (editBtn && saveBtn && editForm) {
    const fields = editForm.querySelectorAll("input:not([type=hidden]), select, textarea");
    const setEditing = (on) => {
      fields.forEach((f) => (f.disabled = !on));
      editBtn.classList.toggle("hidden", on);
      saveBtn.classList.toggle("hidden", !on);
      if (cancelBtn) cancelBtn.classList.toggle("hidden", !on);
    };
    editBtn.addEventListener("click", () => setEditing(true));
    if (cancelBtn) cancelBtn.addEventListener("click", () => { editForm.reset(); setEditing(false); });
  }

  // --- Auto-submit selects (e.g. campaign status) --------------------------
  document.querySelectorAll("select[data-autosubmit]").forEach((sel) => {
    sel.addEventListener("change", () => sel.form && sel.form.requestSubmit());
  });

  // --- Confirm dialogs for destructive buttons ------------------------------
  document.querySelectorAll("form[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (ev) => {
      if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });
})();
