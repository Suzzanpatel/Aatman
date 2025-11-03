
document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".dashboard-nav button");
    const sections = document.querySelectorAll(".dashboard-section");

    buttons.forEach(button => {
        button.addEventListener("click", () => {
            // Remove 'active' from all buttons
            buttons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");

            // Hide all sections
            sections.forEach(section => section.classList.add("hidden-section"));

            // Show the selected section
            const sectionId = button.getAttribute("data-section");
            document.getElementById(sectionId).classList.remove("hidden-section");
        });
    });
});

