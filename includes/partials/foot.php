<?php
/**
 * Closing HTML. Optional before including:
 *   $page_js   (array) – script paths relative to public/
 *   $with_chat (bool)  – show the AI assistant widget (default true)
 */
$page_js   = $page_js ?? [];
$with_chat = $with_chat ?? true;
?>
<?php if ($with_chat): ?>
    <!-- Aatman AI assistant -->
    <div class="aatman-chat-widget" data-endpoint="<?= e(url('api/chatbot.php')) ?>">
        <button type="button" id="chat-toggle" class="chat-toggle" aria-controls="chat-container">💬 Chat with Aatman</button>
        <div class="chat-container hidden" id="chat-container" role="dialog" aria-label="Aatman assistant">
            <div class="chat-header">
                <span>🪷 Aatman Assistant</span>
                <button type="button" id="chat-close" class="chat-close" aria-label="Close chat">✕</button>
            </div>
            <div id="chat-box" class="chat-box" aria-live="polite"></div>
            <form id="chat-form" class="chat-form">
                <input type="text" id="chat-input" placeholder="Ask Aatman anything..." autocomplete="off"
                       maxlength="<?= (int) CHAT_MAX_LENGTH ?>">
                <button type="submit" class="send-btn">Send</button>
            </form>
        </div>
    </div>
    <script src="<?= e(url('assets/js/chat-widget.js')) ?>" defer></script>
<?php endif; ?>

<?php foreach ($page_js as $js): ?>
    <script src="<?= e(url($js)) ?>" defer></script>
<?php endforeach; ?>

<script src="<?= e(url('assets/js/translate.js')) ?>"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" defer></script>
</body>
</html>
