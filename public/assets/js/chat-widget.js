// Aatman AI assistant widget.
// - Opens/closes the floating chat panel.
// - Sends messages to api/chatbot.php with the page's CSRF token.
// - Renders every message with textContent (never innerHTML) so neither the
//   user nor the AI model can inject HTML/JS into the page.
(function () {
  "use strict";

  const widget = document.querySelector(".aatman-chat-widget");
  if (!widget) return;

  const toggle = document.getElementById("chat-toggle");
  const panel = document.getElementById("chat-container");
  const closeBtn = document.getElementById("chat-close");
  const form = document.getElementById("chat-form");
  const input = document.getElementById("chat-input");
  const box = document.getElementById("chat-box");
  const endpoint = widget.dataset.endpoint || "api/chatbot.php";
  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = tokenMeta ? tokenMeta.content : "";

  if (!toggle || !panel || !form || !input || !box) return;

  function open() {
    panel.classList.remove("hidden");
    toggle.style.display = "none";
    if (!box.childElementCount) {
      append("Namaste! I'm the Aatman assistant. Ask me how to donate, volunteer, register an NGO or request help.", "bot");
    }
    input.focus();
  }

  function close() {
    panel.classList.add("hidden");
    toggle.style.display = "";
  }

  function append(text, sender) {
    const el = document.createElement("div");
    el.className = "message " + sender + "-message";
    el.textContent = text;          // safe: no HTML interpretation
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
    return el;
  }

  toggle.addEventListener("click", open);
  if (closeBtn) closeBtn.addEventListener("click", close);

  form.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    const message = input.value.trim();
    if (!message) return;

    append(message, "user");
    input.value = "";
    input.disabled = true;
    const typing = append("Typing…", "bot");

    try {
      const res = await fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-Token": csrfToken,
        },
        body: JSON.stringify({ message }),
      });
      const data = await res.json().catch(() => ({}));
      typing.remove();
      append(data.reply || data.error || "Sorry, I couldn't answer that right now.", "bot");
    } catch (err) {
      typing.remove();
      append("Server error, please try again later.", "bot");
    } finally {
      input.disabled = false;
      input.focus();
    }
  });
})();
