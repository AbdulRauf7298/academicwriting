(function () {
  function parseDateInput(value) {
    if (!value) return null;
    const dt = new Date(value);
    return Number.isNaN(dt.getTime()) ? null : dt;
  }

  function setDeadlineMin(input) {
    if (!input) return;
    const now = new Date();
    now.setHours(now.getHours() + 6);
    const pad = (n) => String(n).padStart(2, "0");
    const min = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(
      now.getDate()
    )}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    input.min = min;
  }

  function getUrgency(deadline) {
    if (!deadline) return "standard";
    const now = new Date();
    const diffHours = (deadline.getTime() - now.getTime()) / (1000 * 60 * 60);
    if (diffHours <= 72) return "urgent";
    if (diffHours <= 144) return "express";
    return "standard";
  }

  function calculate(form) {
    const level = form.querySelector('[name="academic_level"]')?.value || "undergraduate";
    const wordCount = Number(form.querySelector('[name="word_count"]')?.value || 0);
    const deadline = parseDateInput(form.querySelector('[name="deadline"]')?.value);
    const urgency = getUrgency(deadline);
    const pages = Math.max(1, Math.ceil(wordCount / 250));
    const perPage = awsConfig?.pricing?.[level]?.[urgency] ?? 0;
    const total = pages * Number(perPage);

    const output = form.querySelector(".aws-price-value");
    if (output) {
      output.textContent = `${awsConfig?.currency || "£"}${total.toFixed(2)}`;
    }
  }

  function wireOrderForms() {
    document.querySelectorAll(".aws-order-form").forEach((form) => {
      const deadlineInput = form.querySelector('[name="deadline"]');
      setDeadlineMin(deadlineInput);
      ["change", "input"].forEach((evt) => form.addEventListener(evt, () => calculate(form)));
      calculate(form);
    });
  }

  function wireMenu() {
    const button = document.querySelector(".aws-menu-toggle");
    const menu = document.querySelector(".aws-menu");
    if (!button || !menu) return;
    button.addEventListener("click", () => {
      const isOpen = menu.classList.toggle("is-open");
      button.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  function wireTabs() {
    const buttons = document.querySelectorAll(".aws-tabs button");
    if (!buttons.length) return;
    buttons.forEach((button) => {
      button.addEventListener("click", () => {
        buttons.forEach((b) => b.classList.remove("active"));
        button.classList.add("active");
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    wireOrderForms();
    wireMenu();
    wireTabs();
  });
})();
