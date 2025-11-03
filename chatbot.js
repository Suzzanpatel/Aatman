// chatbot.js
document.addEventListener("DOMContentLoaded", () => {
  console.log("✅ Chatbot script loaded");

  const chatForm = document.getElementById("chat-form");
  const chatInput = document.getElementById("chat-input");
  const chatBox = document.getElementById("chat-box");

  if (!chatForm || !chatInput || !chatBox) {
    console.error("❌ Chat elements not found in DOM!");
    return;
  }

  console.log("✅ All chat elements found");

  // Handle form submit
  chatForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const message = chatInput.value.trim();
    if (!message) return;

    appendMessage("You", message);
    chatInput.value = "";

    console.log("🟢 Send button clicked");
    console.log("📤 Sending to chatbot.php...");

    try {
      const res = await fetch("chatbot.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message }),
      });

      console.log("📥 Raw response:", res);

      const data = await res.json();
      console.log("✅ Parsed JSON:", data);

      appendMessage("Aatman Bot", data.reply || "⚠️ No reply received.");
    } catch (err) {
      console.error("❌ Error sending message:", err);
      appendMessage("Aatman Bot", "⚠️ Something went wrong. Please try again.");
    }
  });

  // Function to append messages to chat window
  function appendMessage(sender, text) {
    const msg = document.createElement("div");
    msg.classList.add("message");

    // Different style for user and bot
    msg.innerHTML = `
      <strong>${sender}:</strong> 
      <span>${text}</span>
    `;

    chatBox.appendChild(msg);
    chatBox.scrollTop = chatBox.scrollHeight;
  }
});
const chatToggle = document.getElementById("chat-toggle");
const chatContainer = document.getElementById("chat-container");
const chatClose = document.getElementById("chat-close");
const chatForm = document.getElementById("chat-form");
const chatBox = document.getElementById("chat-box");
const chatInput = document.getElementById("chat-input");

chatToggle.addEventListener("click", () => {
  chatContainer.classList.remove("hidden");
  chatToggle.style.display = "none";
});

chatClose.addEventListener("click", () => {
  chatContainer.classList.add("hidden");
  chatToggle.style.display = "inline-block";
});

chatForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  const message = chatInput.value.trim();
  if (!message) return;

  appendMessage(message, "user");
  chatInput.value = "";

  appendMessage("Typing...", "bot");

  try {
    const response = await fetch("chatbot.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message }),
    });

    const data = await response.json();
    chatBox.lastChild.remove(); // remove "Typing..."
    appendMessage(data.reply, "bot");
  } catch (error) {
    chatBox.lastChild.remove();
    appendMessage("⚠️ Server error, please try again later.", "bot");
  }
});

function appendMessage(text, sender) {
  const messageElem = document.createElement("div");
  messageElem.classList.add("message", `${sender}-message`);
  messageElem.textContent = text;
  chatBox.appendChild(messageElem);
  chatBox.scrollTop = chatBox.scrollHeight;
}
